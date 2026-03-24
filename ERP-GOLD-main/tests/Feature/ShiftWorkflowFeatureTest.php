<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Models\Permission;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShiftWorkflowFeatureTest extends TestCase
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

    public function test_user_can_open_shift_create_vouchers_close_it_and_view_shift_report(): void
    {
        $branch = $this->createBranch('فرع الشفتات');
        $user = $this->createUser($branch, 'shift-owner@example.com');
        $financialYear = $this->createFinancialYear();
        $cashAccount = $this->createAccount('الصندوق', '1000');
        $bankAccount = $this->createAccount('البنك', '2000');

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 100,
                'opening_notes' => 'عهدة افتتاح اليوم',
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();
        $this->assertSame('open', $shift->status);
        $this->assertEquals(100.0, (float) $shift->opening_cash);

        $this->insertInvoice($financialYear, $branch, $user, $shift, [
            'bill_number' => 'SALE-CASH-1001',
            'serial' => '00001',
            'type' => 'sale',
            'payment_type' => 'cash',
            'lines_total' => 100,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
            'date' => '2026-03-22',
            'time' => '10:00:00',
        ]);

        $this->insertInvoice($financialYear, $branch, $user, $shift, [
            'bill_number' => 'SALE-CARD-1001',
            'serial' => '00002',
            'type' => 'sale',
            'payment_type' => 'credit_card',
            'lines_total' => 200,
            'lines_total_after_discount' => 200,
            'taxes_total' => 30,
            'net_total' => 230,
            'date' => '2026-03-22',
            'time' => '11:00:00',
        ]);

        $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'receipt'], false), [
                'date' => '2026-03-22',
                'branch_id' => $branch->id,
                'from_account_id' => $cashAccount->id,
                'to_account_id' => $bankAccount->id,
                'total_amount' => 50,
                'description' => 'سند قبض مرتبط بالشفت',
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $receiptVoucher = FinancialVoucher::query()->where('type', 'receipt')->firstOrFail();
        $this->assertSame($shift->id, $receiptVoucher->shift_id);

        $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'payment'], false), [
                'date' => '2026-03-22',
                'branch_id' => $branch->id,
                'from_account_id' => $bankAccount->id,
                'to_account_id' => $cashAccount->id,
                'total_amount' => 20,
                'description' => 'سند صرف مرتبط بالشفت',
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $paymentVoucher = FinancialVoucher::query()->where('type', 'payment')->firstOrFail();
        $this->assertSame($shift->id, $paymentVoucher->shift_id);

        $this->actingAs($user, 'admin-web')
            ->patch(route('admin.shifts.close', $shift, false), [
                'closing_cash' => 250,
                'closing_notes' => 'تمت المطابقة وإغلاق الشفت',
            ])
            ->assertRedirect(route('admin.shifts.show', $shift, false))
            ->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertSame('closed', $shift->status);
        $this->assertEquals(245.0, (float) $shift->expected_cash);
        $this->assertEquals(250.0, (float) $shift->closing_cash);
        $this->assertEquals(5.0, (float) $shift->cash_difference);

        $response = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $response->assertOk();
        $response->assertSee('تفاصيل الشفت');
        $response->assertSee('SALE-CASH-1001');
        $response->assertSee('SALE-CARD-1001');
        $response->assertSee($receiptVoucher->bill_number);
        $response->assertSee($paymentVoucher->bill_number);
        $response->assertSee('245.00');
        $response->assertSee('5.00');
        $response->assertSee('تمت المطابقة وإغلاق الشفت');
    }

    public function test_user_cannot_open_second_shift_while_current_shift_is_still_active(): void
    {
        $branch = $this->createBranch('فرع المنع');
        $user = $this->createUser($branch, 'prevent-second-shift@example.com');

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 70,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false));

        $this->from(route('admin.shifts.index', [], false))
            ->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 80,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_financial_voucher_creation_requires_an_active_shift_on_the_selected_branch(): void
    {
        $branch = $this->createBranch('فرع السندات');
        $user = $this->createUser($branch, 'voucher-without-shift@example.com');
        $this->createFinancialYear();
        $cashAccount = $this->createAccount('الصندوق', '1001');
        $bankAccount = $this->createAccount('البنك', '2001');

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'receipt'], false), [
                'date' => '2026-03-22',
                'branch_id' => $branch->id,
                'from_account_id' => $cashAccount->id,
                'to_account_id' => $bankAccount->id,
                'total_amount' => 150,
                'description' => 'يجب أن يفشل هذا السند',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
        ]);
        $response->assertJsonFragment([
            'يجب فتح شفت نشط على هذا الفرع قبل تسجيل العملية.',
        ]);
        $this->assertDatabaseCount('financial_vouchers', 0);
    }

    public function test_non_cash_financial_voucher_uses_real_bank_account_and_does_not_change_expected_shift_cash(): void
    {
        $branch = $this->createBranch('فرع السند البنكي');
        $user = $this->createUser($branch, 'bank-voucher-user@example.com');
        $this->createFinancialYear();
        $customerAccount = $this->createAccount('ذمم تحصيل بنكي', '3000');
        $bankLedgerAccount = $this->createAccount('حساب بنك تشغيلي', '3001');

        $bankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $bankLedgerAccount->id,
            'account_name' => 'حساب الأهلي التشغيلي',
            'bank_name' => 'البنك الأهلي',
            'supports_credit_card' => false,
            'supports_bank_transfer' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 100,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'receipt'], false), [
                'date' => '2026-03-22',
                'branch_id' => $branch->id,
                'from_account_id' => $customerAccount->id,
                'to_account_id' => $bankLedgerAccount->id,
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $bankAccount->id,
                'reference_no' => 'BNK-REF-1',
                'total_amount' => 60,
                'description' => 'سند قبض بنكي',
            ]);

        $response->assertOk()->assertJson([
            'status' => true,
        ]);

        $voucher = FinancialVoucher::query()->with(['bankAccount', 'journalEntry.documents'])->firstOrFail();

        $this->assertSame($shift->id, $voucher->shift_id);
        $this->assertSame('bank_transfer', $voucher->payment_method);
        $this->assertSame($bankAccount->id, $voucher->bank_account_id);
        $this->assertSame('BNK-REF-1', $voucher->reference_no);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $voucher->journalEntry?->id,
            'account_id' => $customerAccount->id,
            'credit' => 60,
            'debit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $voucher->journalEntry?->id,
            'account_id' => $bankLedgerAccount->id,
            'debit' => 60,
            'credit' => 0,
        ]);

        $this->actingAs($user, 'admin-web')
            ->patch(route('admin.shifts.close', $shift, false), [
                'closing_cash' => 100,
            ])
            ->assertRedirect(route('admin.shifts.show', $shift, false))
            ->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertEquals(100.0, (float) $shift->expected_cash);
        $this->assertEquals(0.0, (float) $shift->cash_difference);

        $reportResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee('قبض غير نقدي');
        $reportResponse->assertSee('60.00');
        $reportResponse->assertSee('حساب الأهلي التشغيلي');
        $reportResponse->assertSee('BNK-REF-1');
    }

    public function test_non_cash_financial_voucher_requires_bank_account_matching_one_side_of_journal_entry(): void
    {
        $branch = $this->createBranch('فرع تحقق الحساب البنكي');
        $user = $this->createUser($branch, 'voucher-bank-mismatch@example.com');
        $this->createFinancialYear();
        $fromAccount = $this->createAccount('طرف أول', '3100');
        $toAccount = $this->createAccount('طرف ثان', '3101');
        $bankLedgerAccount = $this->createAccount('بنك غير مستخدم في القيد', '3102');

        $bankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $bankLedgerAccount->id,
            'account_name' => 'حساب بنكي غير مطابق',
            'bank_name' => 'بنك الاختبار',
            'supports_credit_card' => false,
            'supports_bank_transfer' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ]);

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('financial_vouchers.store', ['type' => 'receipt'], false), [
                'date' => '2026-03-22',
                'branch_id' => $branch->id,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $bankAccount->id,
                'reference_no' => 'BAD-REF-1',
                'total_amount' => 75,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'status' => false,
        ]);
        $response->assertJsonPath('errors.0', 'الحساب البنكي المحدد يجب أن يكون أحد طرفي السند المحاسبي.');
        $this->assertDatabaseCount('financial_vouchers', 0);
    }

    public function test_user_sees_only_his_own_shifts_and_cannot_open_another_users_shift_report(): void
    {
        $branch = $this->createBranch('فرع التقييد');
        $firstUser = $this->createUser($branch, 'first-shift-user@example.com');
        $secondUser = $this->createUser($branch, 'second-shift-user@example.com');

        $firstShift = $this->createShift($branch, $firstUser, [
            'opened_at' => now()->subHours(4),
            'opening_cash' => 100,
        ]);

        $secondShift = $this->createShift($branch, $secondUser, [
            'opened_at' => now()->subHours(2),
            'opening_cash' => 200,
        ]);

        $response = $this->actingAs($firstUser, 'admin-web')
            ->get(route('admin.shifts.index', [], false));

        $response->assertOk();
        $response->assertSee('<td>'.$firstUser->name.'</td>', false);
        $response->assertDontSee('<td>'.$secondUser->name.'</td>', false);

        $this->actingAs($firstUser, 'admin-web')
            ->get(route('admin.shifts.show', $secondShift, false))
            ->assertForbidden();
    }

    public function test_admin_can_filter_shift_history_by_user_and_status(): void
    {
        $branch = $this->createBranch('فرع الإدارة');
        $admin = $this->createUser($branch, 'admin-shift@example.com');
        $admin->givePermissionTo(Permission::findOrCreate('employee.users.show', 'admin-web'));
        $firstUser = $this->createUser($branch, 'admin-filter-user-1@example.com');
        $secondUser = $this->createUser($branch, 'admin-filter-user-2@example.com');

        $this->createShift($branch, $firstUser, [
            'status' => 'open',
            'opened_at' => now()->subDay(),
            'opening_cash' => 120,
        ]);

        $targetShift = $this->createShift($branch, $secondUser, [
            'status' => 'closed',
            'opened_at' => now()->subHours(5),
            'closed_at' => now()->subHours(1),
            'opening_cash' => 90,
            'expected_cash' => 130,
            'closing_cash' => 128,
            'cash_difference' => -2,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->get(route('admin.shifts.index', [
                'user_id' => $secondUser->id,
                'status' => 'closed',
            ], false));

        $response->assertOk();
        $response->assertSee('name="user_id"', false);
        $response->assertSee('<td>'.$secondUser->name.'</td>', false);
        $response->assertSee('<td>'.$targetShift->id.'</td>', false);
        $response->assertDontSee('<td>'.$firstUser->name.'</td>', false);
    }

    public function test_sales_store_links_created_invoice_to_active_shift_and_appears_in_shift_report(): void
    {
        $branch = $this->createBranch('فرع بيع الشفت');
        $user = $this->createUser($branch, 'sales-shift-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId] = $this->createInventoryFixture($branch);
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 50,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 15:30:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل الشفت',
                'bill_client_phone' => '0550001111',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $invoice = DB::table('invoices')->where('type', 'sale')->first();

        $this->assertNotNull($invoice);
        $this->assertSame($shift->id, $invoice->shift_id);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame($branch->id, $invoice->branch_id);

        $reportResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($invoice->bill_number);
        $reportResponse->assertSee('115.00');
    }

    public function test_purchases_store_links_created_invoice_to_active_shift_and_appears_in_shift_report(): void
    {
        $branch = $this->createBranch('فرع شراء الشفت');
        $user = $this->createUser($branch, 'purchase-shift-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId, $caratId] = $this->createInventoryFixture($branch);
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 30,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('purchases.store', [], false), [
                'bill_date' => '2026-03-22 16:10:00',
                'branch_id' => $branch->id,
                'carat_type' => 'crafted',
                'purchase_type' => 'normal',
                'supplier_id' => $supplierId,
                'bill_client_name' => 'مورد الشفت',
                'bill_client_phone' => '0550002222',
                'unit_id' => [$itemUnitId],
                'weight' => [2],
                'item_total_labor_cost' => [20],
                'item_total_cost' => [200],
                'discount' => [0],
                'carats_id' => [$caratId],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $invoice = DB::table('invoices')->where('type', 'purchase')->first();

        $this->assertNotNull($invoice);
        $this->assertSame($shift->id, $invoice->shift_id);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame($branch->id, $invoice->branch_id);

        $reportResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($invoice->bill_number);
        $reportResponse->assertSee('253.00');
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '0550000000',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email, bool $isAdmin = false): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => $isAdmin,
            'profile_pic' => 'default.png',
        ]);
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

    private function createShift(Branch $branch, User $user, array $attributes = []): Shift
    {
        return Shift::create(array_merge([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_cash' => 0,
        ], $attributes));
    }

    /**
     * @return array{0:int,1:int}
     */
    private function createTradingParties(): array
    {
        $customerAccount = $this->createAccount('حساب العملاء', '3000');
        $supplierAccount = $this->createAccount('حساب الموردين', '4000');

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل الشفت',
            'phone' => '0551111111',
            'account_id' => $customerAccount->id,
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد الشفت',
            'phone' => '0552222222',
            'account_id' => $supplierAccount->id,
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$customerId, $supplierId];
    }

    private function createWarehouse(Branch $branch): int
    {
        return DB::table('warehouses')->insertGetId([
            'name' => 'مخزن الاختبار',
            'code' => 'WH-1',
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function createInventoryFixture(Branch $branch): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratTypeId = DB::table('gold_carat_types')->insertGetId([
            'title' => 'مشغول',
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'قطعة ذهب', 'en' => 'Gold Piece'], JSON_UNESCAPED_UNICODE),
            'code' => '000001',
            'description' => null,
            'category_id' => null,
            'branch_id' => $branch->id,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 10,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemUnitId = DB::table('item_units')->insertGetId([
            'item_id' => $itemId,
            'initial_cost_per_gram' => 80,
            'average_cost_per_gram' => 80,
            'current_cost_per_gram' => 80,
            'barcode' => '0000011000',
            'weight' => 1,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$itemUnitId, $caratId];
    }

    private function createBranchAccountSettings(Branch $branch, int $customerId, int $supplierId): void
    {
        $safeAccount = $this->createAccount('الصندوق التشغيلي', '5000');
        $bankAccount = $this->createAccount('البنك التشغيلي', '5001');
        $salesAccount = $this->createAccount('المبيعات', '5002');
        $returnSalesAccount = $this->createAccount('مرتجعات المبيعات', '5003');
        $stockCraftedAccount = $this->createAccount('مخزون المشغول', '5004');
        $madeAccount = $this->createAccount('أجرة الصياغة', '5005');
        $costCraftedAccount = $this->createAccount('تكلفة المشغول', '5006');
        $salesTaxAccount = $this->createAccount('ضريبة المبيعات', '5007');
        $purchaseTaxAccount = $this->createAccount('ضريبة المشتريات', '5008');

        $customerAccountId = DB::table('customers')->where('id', $customerId)->value('account_id');
        $supplierAccountId = DB::table('customers')->where('id', $supplierId)->value('account_id');

        DB::table('account_settings')->insert([
            'safe_account' => $safeAccount->id,
            'bank_account' => $bankAccount->id,
            'sales_account' => $salesAccount->id,
            'return_sales_account' => $returnSalesAccount->id,
            'stock_account_crafted' => $stockCraftedAccount->id,
            'stock_account_scrap' => $stockCraftedAccount->id,
            'stock_account_pure' => $stockCraftedAccount->id,
            'made_account' => $madeAccount->id,
            'cost_account_crafted' => $costCraftedAccount->id,
            'cost_account_scrap' => $costCraftedAccount->id,
            'cost_account_pure' => $costCraftedAccount->id,
            'reverse_profit_account' => $salesAccount->id,
            'profit_account' => $salesAccount->id,
            'sales_tax_account' => $salesTaxAccount->id,
            'purchase_tax_account' => $purchaseTaxAccount->id,
            'sales_tax_excise_account' => $salesTaxAccount->id,
            'supplier_default_account' => $supplierAccountId,
            'clients_account' => $customerAccountId,
            'suppliers_account' => $supplierAccountId,
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertInvoice(FinancialYear $financialYear, Branch $branch, User $user, Shift $shift, array $attributes = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'bill_number' => 'INV-' . now()->timestamp,
            'serial' => '00001',
            'financial_year' => $financialYear->id,
            'branch_id' => $branch->id,
            'type' => 'sale',
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'lines_total' => 0,
            'discount_total' => 0,
            'lines_total_after_discount' => 0,
            'taxes_total' => 0,
            'net_total' => 0,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
