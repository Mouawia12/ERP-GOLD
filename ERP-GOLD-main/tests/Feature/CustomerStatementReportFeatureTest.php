<?php

namespace Tests\Feature;

use App\Models\Branch;
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

class CustomerStatementReportFeatureTest extends TestCase
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

    public function test_customer_statement_report_displays_transaction_and_carat_aggregates(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل التقرير',
            'phone' => '0557000000',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$carat21Id, $carat18Id] = $this->prepareReportDimensions();

        $saleInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-1001',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-20',
            'time' => '10:15:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $saleInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 10,
            'line_total' => 1000,
            'line_tax' => 150,
            'net_total' => 1150,
            'date' => '2026-03-20',
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $saleInvoiceId,
            'gold_carat_id' => $carat18Id,
            'out_weight' => 5,
            'line_total' => 400,
            'line_tax' => 60,
            'net_total' => 460,
            'date' => '2026-03-20',
        ]);

        $returnInvoiceId = $this->insertInvoice([
            'bill_number' => 'SRET-1001',
            'type' => 'sale_return',
            'payment_type' => 'bank_transfer',
            'date' => '2026-03-21',
            'time' => '11:45:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $returnInvoiceId,
            'gold_carat_id' => $carat21Id,
            'in_weight' => 2,
            'line_total' => 200,
            'line_tax' => 30,
            'net_total' => 230,
            'date' => '2026-03-21',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', ['id' => $customerId], false));

        $response->assertOk();
        $response->assertSee('كشف العميل التفصيلي');
        $response->assertSee('SALE-1001');
        $response->assertSee('SRET-1001');
        $response->assertSee('بيع');
        $response->assertSee('مرتجع بيع');
        $response->assertSee('نقدي');
        $response->assertSee('تحويل بنكي');
        $response->assertSee('عيار 21');
        $response->assertSee('عيار 18');
        $response->assertSee('1,610.00');
        $response->assertSee('230.00');
        $response->assertSee('10.000');
        $response->assertSee('2.000');
    }

    public function test_customer_statement_report_respects_carat_and_date_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل الفلترة',
            'phone' => '0557111111',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$carat21Id, $carat18Id] = $this->prepareReportDimensions();

        $oldInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-OLD',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-18',
            'time' => '09:00:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $oldInvoiceId,
            'gold_carat_id' => $carat18Id,
            'out_weight' => 3,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
            'date' => '2026-03-18',
        ]);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-MATCH',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '09:30:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 4,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', [
                'id' => $customerId,
                'from_date' => '2026-03-20',
                'carat_id' => $carat21Id,
            ], false));

        $response->assertOk();
        $response->assertSee('SALE-MATCH');
        $response->assertDontSee('SALE-OLD');
        $response->assertSee('عيار 21');
        $response->assertSee('575.00');
        $response->assertSee('4.000');
        $response->assertDontSee('345.00');
        $response->assertDontSee('3.000');
    }

    public function test_customer_statement_report_respects_user_time_and_invoice_number_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        $otherBranch = Branch::create([
            'name' => ['ar' => 'فرع آخر', 'en' => 'Other Branch'],
            'phone' => '987654321',
            'status' => true,
        ]);

        $otherUser = User::create([
            'name' => 'Other Report User',
            'email' => 'customer-report-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $admin->branch_id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $branchUser = User::create([
            'name' => 'Other Branch User',
            'email' => 'customer-report-branch-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $otherBranch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل فلترة متقدمة',
            'phone' => '0557222222',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$carat21Id] = $this->prepareReportDimensions();

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-MATCH',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '15:30:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 4,
            'line_total' => 600,
            'line_tax' => 90,
            'net_total' => 690,
            'date' => '2026-03-22',
        ]);

        $otherUserInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-OTHER-USER',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '15:35:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $otherUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherUserInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 5,
            'line_total' => 800,
            'line_tax' => 120,
            'net_total' => 920,
            'date' => '2026-03-22',
        ]);

        $otherTimeInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-OTHER-TIME',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '14:10:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherTimeInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 2,
            'line_total' => 250,
            'line_tax' => 37.5,
            'net_total' => 287.5,
            'date' => '2026-03-22',
        ]);

        $otherBranchInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-OTHER-BRANCH',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '15:40:00',
            'customer_id' => $customerId,
            'branch_id' => $otherBranch->id,
            'user_id' => $branchUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherBranchInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 6,
            'line_total' => 950,
            'line_tax' => 142.5,
            'net_total' => 1092.5,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', [
                'id' => $customerId,
                'from_date' => '2026-03-22',
                'to_date' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '16:00',
                'branch_id' => $admin->branch_id,
                'user_id' => $admin->id,
                'invoice_number' => 'SALE-FILTER-MATCH',
            ], false));

        $response->assertOk();
        $response->assertSee('SALE-FILTER-MATCH');
        $response->assertDontSee('SALE-FILTER-OTHER-USER');
        $response->assertDontSee('SALE-FILTER-OTHER-TIME');
        $response->assertDontSee('SALE-FILTER-OTHER-BRANCH');
        $response->assertSee('690.00');
        $response->assertSee('4.000');
        $response->assertDontSee('920.00');
        $response->assertDontSee('287.50');
        $response->assertDontSee('1,092.50');
    }

    public function test_supplier_statement_report_displays_purchase_operations(): void
    {
        $admin = $this->createAdminUser([
            'employee.suppliers.show',
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد التقرير',
            'phone' => '0567000000',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$carat21Id] = $this->prepareReportDimensions();

        $purchaseInvoiceId = $this->insertInvoice([
            'bill_number' => 'PUR-9001',
            'type' => 'purchase',
            'payment_type' => 'credit_card',
            'date' => '2026-03-22',
            'time' => '14:00:00',
            'customer_id' => $supplierId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $purchaseInvoiceId,
            'gold_carat_id' => $carat21Id,
            'in_weight' => 7,
            'line_total' => 900,
            'line_tax' => 135,
            'net_total' => 1035,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', ['id' => $supplierId], false));

        $response->assertOk();
        $response->assertSee('كشف المورد التفصيلي');
        $response->assertSee('PUR-9001');
        $response->assertSee('شراء');
        $response->assertSee('شبكة / بطاقة');
        $response->assertSee('1,035.00');
        $response->assertSee('7.000');
    }

    public function test_customer_statement_report_includes_receipt_and_payment_vouchers_with_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        $customerAccountId = $this->insertAccount('ذمم عميل الحركات', 'CR-1001');
        $safeAccountId = $this->insertAccount('الصندوق', 'SAFE-1001');
        $financialYearId = DB::table('financial_years')->insertGetId([
            'description' => 'السنة المالية للتقرير',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل السندات',
            'phone' => '0557333333',
            'type' => 'customer',
            'account_id' => $customerAccountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherUser = User::create([
            'name' => 'Voucher Other User',
            'email' => 'customer-voucher-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $admin->branch_id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $adminShiftId = $this->insertShift($admin->branch_id, $admin->id, '2026-03-22 15:00:00');
        $otherUserShiftId = $this->insertShift($admin->branch_id, $otherUser->id, '2026-03-22 15:00:00');

        $this->insertFinancialVoucher([
            'bill_number' => 'R-1-00001',
            'type' => 'receipt',
            'branch_id' => $admin->branch_id,
            'financial_year' => $financialYearId,
            'from_account_id' => $customerAccountId,
            'to_account_id' => $safeAccountId,
            'date' => '2026-03-22',
            'total_amount' => 500,
            'shift_id' => $adminShiftId,
            'created_at' => '2026-03-22 15:20:00',
            'updated_at' => '2026-03-22 15:20:00',
        ]);

        $this->insertFinancialVoucher([
            'bill_number' => 'P-1-00001',
            'type' => 'payment',
            'branch_id' => $admin->branch_id,
            'financial_year' => $financialYearId,
            'from_account_id' => $safeAccountId,
            'to_account_id' => $customerAccountId,
            'date' => '2026-03-22',
            'total_amount' => 200,
            'shift_id' => $adminShiftId,
            'created_at' => '2026-03-22 15:35:00',
            'updated_at' => '2026-03-22 15:35:00',
        ]);

        $this->insertFinancialVoucher([
            'bill_number' => 'R-1-00002',
            'type' => 'receipt',
            'branch_id' => $admin->branch_id,
            'financial_year' => $financialYearId,
            'from_account_id' => $customerAccountId,
            'to_account_id' => $safeAccountId,
            'date' => '2026-03-22',
            'total_amount' => 300,
            'shift_id' => $otherUserShiftId,
            'created_at' => '2026-03-22 15:50:00',
            'updated_at' => '2026-03-22 15:50:00',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', ['id' => $customerId], false));

        $response->assertOk();
        $response->assertSee('R-1-00001');
        $response->assertSee('P-1-00001');
        $response->assertSee('R-1-00002');
        $response->assertSee('سند قبض');
        $response->assertSee('سند صرف');
        $response->assertSee('من ذمم عميل الحركات إلى الصندوق');
        $response->assertSee('من الصندوق إلى ذمم عميل الحركات');
        $response->assertSee('500.00');
        $response->assertSee('200.00');

        $filteredResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', [
                'id' => $customerId,
                'from_date' => '2026-03-22',
                'to_date' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '15:30',
                'user_id' => $admin->id,
                'operation_type' => 'receipt',
            ], false));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('R-1-00001');
        $filteredResponse->assertDontSee('P-1-00001');
        $filteredResponse->assertDontSee('R-1-00002');
        $filteredResponse->assertSee('500.00');
        $filteredResponse->assertDontSee('200.00');
        $filteredResponse->assertDontSee('300.00');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع التقارير', 'en' => 'Reports Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير النظام', 'en' => 'System Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::create([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'name' => 'Reports Admin',
            'email' => 'customer-report-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<int, int>
     */
    private function prepareReportDimensions(): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gold_carat_types')->insert([
            'id' => 1,
            'title' => 'مشغول',
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carat21Id = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carat18Id = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 18', 'en' => '18K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C18',
            'tax_id' => $taxId,
            'transform_factor' => '0.857',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$carat21Id, $carat18Id];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoice(array $attributes): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'bill_number' => null,
            'serial' => null,
            'financial_year' => null,
            'branch_id' => null,
            'warehouse_id' => null,
            'customer_id' => null,
            'bill_client_phone' => null,
            'bill_client_name' => null,
            'parent_id' => null,
            'type' => 'sale',
            'account_id' => null,
            'sale_type' => 'simplified',
            'purchase_type' => null,
            'purchase_carat_type_id' => null,
            'supplier_bill_number' => null,
            'notes' => null,
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '00:00:00',
            'lines_total' => 0,
            'discount_total' => 0,
            'lines_total_after_discount' => 0,
            'taxes_total' => 0,
            'net_total' => 0,
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function insertAccount(string $name, string $code): int
    {
        return DB::table('accounts')->insertGetId([
            'name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'code' => $code,
            'account_type' => config('settings.accounts_types')[0],
            'transfer_side' => config('settings.transfers_sides')[0],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertShift(int $branchId, int $userId, string $openedAt): int
    {
        return DB::table('shifts')->insertGetId([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'status' => 'open',
            'opened_at' => $openedAt,
            'opening_cash' => 0,
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertFinancialVoucher(array $attributes): int
    {
        return DB::table('financial_vouchers')->insertGetId(array_merge([
            'branch_id' => null,
            'financial_year' => null,
            'bill_number' => null,
            'serial' => null,
            'type' => 'receipt',
            'from_account_id' => null,
            'to_account_id' => null,
            'date' => '2026-03-22',
            'total_amount' => 0,
            'description' => null,
            'shift_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoiceDetail(array $attributes): int
    {
        return DB::table('invoice_details')->insertGetId(array_merge([
            'invoice_id' => null,
            'warehouse_id' => null,
            'parent_id' => null,
            'item_id' => null,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'unit_id' => null,
            'gold_carat_id' => null,
            'gold_carat_type_id' => 1,
            'date' => '2026-03-22',
            'in_quantity' => 0,
            'out_quantity' => 0,
            'in_weight' => 0,
            'out_weight' => 0,
            'unit_cost' => 0,
            'labor_cost_per_gram' => 0,
            'unit_price' => 0,
            'unit_discount' => 0,
            'unit_tax' => 0,
            'unit_tax_rate' => 15,
            'unit_tax_id' => null,
            'line_total' => 0,
            'line_discount' => 0,
            'line_tax' => 0,
            'net_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
