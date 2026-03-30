<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchDataIsolationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_branch_user_sales_endpoints_are_scoped_to_his_branch(): void
    {
        $financialYear = $this->createFinancialYear();
        $ownBranch = $this->createBranch('فرع البيع الأساسي');
        $foreignBranch = $this->createBranch('فرع البيع الخارجي');
        $customerId = $this->createParty('customer', 'عميل عزل المبيعات', '0551000001');

        $user = $this->createUser($ownBranch, 'sales-scope-user@example.com', [
            'employee.simplified_tax_invoices.show',
            'employee.simplified_tax_invoices.add',
            'employee.sales_returns.add',
            'employee.sales_returns.show',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'sales-foreign-user@example.com');

        $ownInvoice = $this->createInvoice($financialYear, $ownBranch, $user, $customerId, 'sale', [
            'sale_type' => 'simplified',
            'date' => '2026-03-23',
            'time' => '09:15:00',
        ]);
        $foreignInvoice = $this->createInvoice($financialYear, $foreignBranch, $foreignUser, $customerId, 'sale', [
            'sale_type' => 'simplified',
            'date' => '2026-03-23',
            'time' => '10:30:00',
        ]);

        $indexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('sales.index', ['type' => 'simplified'], false));

        $indexResponse->assertOk();
        $indexResponse->assertJsonFragment([
            'bill_number' => $ownInvoice->bill_number,
        ]);
        $indexResponse->assertJsonMissing([
            'bill_number' => $foreignInvoice->bill_number,
        ]);

        $createResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $createResponse->assertOk();
        $createResponse->assertSee('فرع البيع الأساسي');
        $createResponse->assertDontSee('فرع البيع الخارجي');

        $this->actingAs($user, 'admin-web')
            ->get(route('sales.show', $foreignInvoice->id, false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->get(route('sales_return.create', ['type' => 'simplified', 'id' => $foreignInvoice->id], false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->post(route('sales.payments', [], false), [
                'branch_id' => $foreignBranch->id,
                'net_after_discount' => 100,
                'document_type' => 'sale',
            ])
            ->assertForbidden();
    }

    public function test_branch_user_purchase_endpoints_are_scoped_to_his_branch(): void
    {
        $financialYear = $this->createFinancialYear();
        $ownBranch = $this->createBranch('فرع الشراء الأساسي');
        $foreignBranch = $this->createBranch('فرع الشراء الخارجي');
        $supplierId = $this->createParty('supplier', 'مورد عزل المشتريات', '0552000001');

        $user = $this->createUser($ownBranch, 'purchase-scope-user@example.com', [
            'employee.purchase_invoices.show',
            'employee.purchase_invoices.add',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'purchase-foreign-user@example.com');

        $ownInvoice = $this->createInvoice($financialYear, $ownBranch, $user, $supplierId, 'purchase', [
            'purchase_type' => 'normal',
            'date' => '2026-03-23',
            'time' => '11:00:00',
        ]);
        $foreignInvoice = $this->createInvoice($financialYear, $foreignBranch, $foreignUser, $supplierId, 'purchase', [
            'purchase_type' => 'normal',
            'date' => '2026-03-23',
            'time' => '12:15:00',
        ]);

        $indexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('purchases.index', [], false));

        $indexResponse->assertOk();
        $indexResponse->assertJsonFragment([
            'bill_number' => $ownInvoice->bill_number,
        ]);
        $indexResponse->assertJsonMissing([
            'bill_number' => $foreignInvoice->bill_number,
        ]);

        $createResponse = $this->actingAs($user, 'admin-web')
            ->get(route('purchases.create', [], false));

        $createResponse->assertOk();
        $createResponse->assertSee('فرع الشراء الأساسي');
        $createResponse->assertDontSee('فرع الشراء الخارجي');

        $this->actingAs($user, 'admin-web')
            ->get(route('purchases.show', $foreignInvoice->id, false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->get(route('purchase_return.create', ['id' => $foreignInvoice->id], false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->post(route('purchases.payments', [], false), [
                'branch_id' => $foreignBranch->id,
                'net_after_discount' => 100,
                'document_type' => 'purchase',
            ])
            ->assertForbidden();
    }

    public function test_sales_list_accepts_branch_and_date_filters_without_leaking_other_records(): void
    {
        $financialYear = $this->createFinancialYear();
        $ownBranch = $this->createBranch('فرع فلاتر المبيعات');
        $foreignBranch = $this->createBranch('فرع خارجي لفلاتر المبيعات');
        $customerId = $this->createParty('customer', 'عميل فلاتر المبيعات', '0551999999');

        $user = $this->createUser($ownBranch, 'sales-filters-user@example.com', [
            'employee.simplified_tax_invoices.show',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'sales-filters-foreign@example.com');

        $matchingInvoice = $this->createInvoice($financialYear, $ownBranch, $user, $customerId, 'sale', [
            'sale_type' => 'simplified',
            'date' => '2026-03-25',
            'time' => '09:15:00',
        ]);
        $otherDateInvoice = $this->createInvoice($financialYear, $ownBranch, $user, $customerId, 'sale', [
            'sale_type' => 'simplified',
            'date' => '2026-03-20',
            'time' => '09:20:00',
        ]);
        $foreignInvoice = $this->createInvoice($financialYear, $foreignBranch, $foreignUser, $customerId, 'sale', [
            'sale_type' => 'simplified',
            'date' => '2026-03-25',
            'time' => '11:30:00',
        ]);

        $htmlResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.index', [
                'type' => 'simplified',
                'branch_id' => $ownBranch->id,
                'date_from' => '2026-03-25',
                'date_to' => '2026-03-25',
            ], false));

        $htmlResponse->assertOk();
        $htmlResponse->assertSee('name="branch_id"', false);
        $htmlResponse->assertSee('name="date_from"', false);
        $htmlResponse->assertSee('name="date_to"', false);

        $ajaxResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('sales.index', [
                'type' => 'simplified',
                'branch_id' => $ownBranch->id,
                'date_from' => '2026-03-25',
                'date_to' => '2026-03-25',
            ], false));

        $ajaxResponse->assertOk();
        $ajaxResponse->assertJsonFragment([
            'bill_number' => $matchingInvoice->bill_number,
        ]);
        $ajaxResponse->assertJsonMissing([
            'bill_number' => $otherDateInvoice->bill_number,
        ]);
        $ajaxResponse->assertJsonMissing([
            'bill_number' => $foreignInvoice->bill_number,
        ]);
    }

    public function test_branch_user_financial_vouchers_are_scoped_to_his_branch(): void
    {
        $financialYear = $this->createFinancialYear();
        $ownBranch = $this->createBranch('فرع السندات الأساسي');
        $foreignBranch = $this->createBranch('فرع السندات الخارجي');
        $user = $this->createUser($ownBranch, 'voucher-scope-user@example.com');

        $cashAccount = $this->createAccount('الصندوق', '9100');
        $ownBankLedger = $this->createAccount('بنك الفرع الأساسي', '9101');
        $foreignBankLedger = $this->createAccount('بنك الفرع الخارجي', '9102');

        $ownBankAccount = BankAccount::create([
            'branch_id' => $ownBranch->id,
            'ledger_account_id' => $ownBankLedger->id,
            'account_name' => 'حساب بنك الفرع الأساسي',
            'bank_name' => 'بنك الفرع الأساسي',
            'supports_credit_card' => true,
            'supports_bank_transfer' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $foreignBankAccount = BankAccount::create([
            'branch_id' => $foreignBranch->id,
            'ledger_account_id' => $foreignBankLedger->id,
            'account_name' => 'حساب بنك الفرع الخارجي',
            'bank_name' => 'بنك الفرع الخارجي',
            'supports_credit_card' => true,
            'supports_bank_transfer' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $ownVoucher = FinancialVoucher::create([
            'branch_id' => $ownBranch->id,
            'financial_year' => $financialYear->id,
            'type' => 'receipt',
            'from_account_id' => $cashAccount->id,
            'to_account_id' => $ownBankLedger->id,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $ownBankAccount->id,
            'reference_no' => 'OWN-BNK-001',
            'date' => '2026-03-23',
            'total_amount' => 125,
            'description' => 'سند الفرع الأساسي',
        ]);

        $foreignVoucher = FinancialVoucher::create([
            'branch_id' => $foreignBranch->id,
            'financial_year' => $financialYear->id,
            'type' => 'receipt',
            'from_account_id' => $cashAccount->id,
            'to_account_id' => $foreignBankLedger->id,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $foreignBankAccount->id,
            'reference_no' => 'FOR-BNK-001',
            'date' => '2026-03-23',
            'total_amount' => 225,
            'description' => 'سند الفرع الخارجي',
        ]);

        $indexResponse = $this->actingAs($user, 'admin-web')
            ->get(route('financial_vouchers', ['type' => 'receipt'], false));

        $indexResponse->assertOk();
        $indexResponse->assertSee($ownVoucher->bill_number);
        $indexResponse->assertDontSee($foreignVoucher->bill_number);
        $indexResponse->assertSee('حساب بنك الفرع الأساسي');
        $indexResponse->assertDontSee('حساب بنك الفرع الخارجي');

        $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'receipt'], false), [
                'date' => '2026-03-23',
                'branch_id' => $foreignBranch->id,
                'from_account_id' => $cashAccount->id,
                'to_account_id' => $foreignBankLedger->id,
                'total_amount' => 50,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }

    private function createBranch(string $name): Branch
    {
        $sequence = Branch::query()->count() + 1;

        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => 'branch-'.$sequence.'@example.com',
            'phone' => '0550000000',
            'tax_number' => str_pad((string) $sequence, 15, '1', STR_PAD_LEFT),
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email, array $permissions = [], bool $isAdmin = false): User
    {
        $user = User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => $isAdmin,
            'profile_pic' => 'default.png',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin-web');
        }

        if ($permissions !== []) {
            $role = Role::create([
                'name' => [
                    'ar' => 'دور اختبار '.$user->id,
                    'en' => 'Test Role '.$user->id,
                ],
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permissions);
            $user->assignRole($role);
        }

        return $user;
    }

    private function createFinancialYear(): FinancialYear
    {
        return FinancialYear::create([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
        ]);
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }

    private function createParty(string $type, string $name, string $phone): int
    {
        $account = $this->createAccount($name.' الحساب', 'A'.random_int(1000, 9999));

        return (int) DB::table('customers')->insertGetId([
            'name' => $name,
            'phone' => $phone,
            'account_id' => $account->id,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createInvoice(
        FinancialYear $financialYear,
        Branch $branch,
        User $user,
        int $customerId,
        string $type,
        array $overrides = []
    ): Invoice {
        $baseData = [
            'financial_year' => $financialYear->id,
            'branch_id' => $branch->id,
            'customer_id' => $customerId,
            'type' => $type,
            'sale_type' => 'simplified',
            'purchase_type' => 'normal',
            'payment_type' => 'cash',
            'date' => '2026-03-23',
            'time' => '08:00:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
            'user_id' => $user->id,
        ];

        if ($type === 'sale') {
            $baseData['purchase_type'] = null;
        }

        return Invoice::create(array_merge($baseData, $overrides));
    }
}
