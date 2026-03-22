<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class InvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_invoice_sequence_is_scoped_by_user_branch_and_type(): void
    {
        $branchA = $this->createBranch('الفرع أ', 'branch-a@example.com', '111111111');
        $branchB = $this->createBranch('الفرع ب', 'branch-b@example.com', '222222222');
        $userOne = $this->createUser($branchA, 'user1@example.com');
        $userTwo = $this->createUser($branchA, 'user2@example.com');

        $sale1 = $this->createInvoice($branchA, $userOne, 'sale');
        $sale2 = $this->createInvoice($branchA, $userOne, 'sale');
        $saleOtherUser = $this->createInvoice($branchA, $userTwo, 'sale');
        $purchaseSameUser = $this->createInvoice($branchA, $userOne, 'purchase');
        $saleOtherBranch = $this->createInvoice($branchB, $this->createUser($branchB, 'user3@example.com'), 'sale');

        $this->assertSame('00001', $sale1->serial);
        $this->assertSame("S-{$branchA->id}-{$userOne->id}-00001", $sale1->bill_number);

        $this->assertSame('00002', $sale2->serial);
        $this->assertSame("S-{$branchA->id}-{$userOne->id}-00002", $sale2->bill_number);

        $this->assertSame('00001', $saleOtherUser->serial);
        $this->assertSame("S-{$branchA->id}-{$userTwo->id}-00001", $saleOtherUser->bill_number);

        $this->assertSame('00001', $purchaseSameUser->serial);
        $this->assertSame("P-{$branchA->id}-{$userOne->id}-00001", $purchaseSameUser->bill_number);

        $this->assertSame('00001', $saleOtherBranch->serial);
        $this->assertSame("S-{$branchB->id}-{$saleOtherBranch->user_id}-00001", $saleOtherBranch->bill_number);

        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userOne->id,
            'branch_id' => $branchA->id,
            'type' => 'sale',
            'last_number' => 2,
        ]);
        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userTwo->id,
            'branch_id' => $branchA->id,
            'type' => 'sale',
            'last_number' => 1,
        ]);
        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userOne->id,
            'branch_id' => $branchA->id,
            'type' => 'purchase',
            'last_number' => 1,
        ]);
    }

    public function test_return_invoice_uses_its_own_counter_for_same_user(): void
    {
        $branch = $this->createBranch('فرع المرتجعات', 'returns@example.com', '333333333');
        $user = $this->createUser($branch, 'returns-user@example.com');

        $sale = $this->createInvoice($branch, $user, 'sale');
        $returnOne = $this->createInvoice($branch, $user, 'sale_return', $sale->id);
        $returnTwo = $this->createInvoice($branch, $user, 'sale_return', $sale->id);

        $this->assertSame("S-{$branch->id}-{$user->id}-00001", $sale->bill_number);
        $this->assertSame("SR-{$branch->id}-{$user->id}-00001", $returnOne->bill_number);
        $this->assertSame("SR-{$branch->id}-{$user->id}-00002", $returnTwo->bill_number);

        $this->assertSame(2, InvoiceCounter::where([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'type' => 'sale_return',
        ])->value('last_number'));
    }

    public function test_sales_print_page_shows_bill_number(): void
    {
        $branch = $this->createBranch('فرع الطباعة', 'print@example.com', '444444444');
        $user = $this->createUser($branch, 'print-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale');

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee($invoice->bill_number);
        $response->assertSee('print-brand-logo', false);
    }

    public function test_operational_sales_and_purchases_use_per_user_sequences_on_real_store_routes(): void
    {
        $branch = $this->createBranch('فرع التشغيل', 'ops@example.com', '555555555');
        $userOne = $this->createUser($branch, 'ops-user-one@example.com');
        $userTwo = $this->createUser($branch, 'ops-user-two@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);
        [$itemUnitId, $caratId, $caratTypeId] = $this->createInventoryFixture($branch);

        $this->openShift($userOne, $branch);
        $this->openShift($userTwo, $branch);

        $this->actingAs($userOne, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 10:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل التشغيل الأول',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ])->assertOk()->assertJsonPath('status', true);

        $this->actingAs($userOne, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 10:10:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل التشغيل الثاني',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ])->assertOk()->assertJsonPath('status', true);

        $this->actingAs($userTwo, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 10:20:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل مستخدم آخر',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ])->assertOk()->assertJsonPath('status', true);

        $this->actingAs($userOne, 'admin-web')
            ->postJson(route('purchases.store', [], false), [
                'bill_date' => '2026-03-22 11:00:00',
                'branch_id' => $branch->id,
                'carat_type' => 'crafted',
                'purchase_type' => 'normal',
                'supplier_id' => $supplierId,
                'bill_client_name' => 'مورد التشغيل',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'carats_id' => [$caratId],
                'weight' => [1],
                'discount' => [0],
                'item_total_cost' => [100],
                'item_total_labor_cost' => [0],
            ])->assertOk()->assertJsonPath('status', true);

        $userOneSales = Invoice::query()
            ->where('type', 'sale')
            ->where('user_id', $userOne->id)
            ->orderBy('id')
            ->get();
        $userTwoSales = Invoice::query()
            ->where('type', 'sale')
            ->where('user_id', $userTwo->id)
            ->orderBy('id')
            ->get();
        $userOnePurchase = Invoice::query()
            ->where('type', 'purchase')
            ->where('user_id', $userOne->id)
            ->firstOrFail();

        $this->assertCount(2, $userOneSales);
        $this->assertSame("S-{$branch->id}-{$userOne->id}-00001", $userOneSales[0]->bill_number);
        $this->assertSame("S-{$branch->id}-{$userOne->id}-00002", $userOneSales[1]->bill_number);
        $this->assertSame("S-{$branch->id}-{$userTwo->id}-00001", $userTwoSales[0]->bill_number);
        $this->assertSame("P-{$branch->id}-{$userOne->id}-00001", $userOnePurchase->bill_number);

        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userOne->id,
            'branch_id' => $branch->id,
            'type' => 'sale',
            'last_number' => 2,
        ]);
        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userTwo->id,
            'branch_id' => $branch->id,
            'type' => 'sale',
            'last_number' => 1,
        ]);
        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $userOne->id,
            'branch_id' => $branch->id,
            'type' => 'purchase',
            'last_number' => 1,
        ]);

        $purchasePrintResponse = $this
            ->actingAs($userOne, 'admin-web')
            ->get(route('purchases.show', ['id' => $userOnePurchase->id], false));

        $purchasePrintResponse->assertOk();
        $purchasePrintResponse->assertSee($userOnePurchase->bill_number);
    }

    public function test_operational_sale_return_uses_its_own_sequence_on_real_store_route(): void
    {
        $branch = $this->createBranch('فرع المرتجع التشغيلي', 'ops-returns@example.com', '666666666');
        $user = $this->createUser($branch, 'ops-return-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);
        [$itemUnitId] = $this->createInventoryFixture($branch);

        $this->openShift($user, $branch);

        $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 12:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل المرتجع الأول',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ])->assertOk();

        $firstSale = Invoice::query()->where('type', 'sale')->latest('id')->firstOrFail();
        $firstSaleDetail = $firstSale->details()->firstOrFail();

        $this->actingAs($user, 'admin-web')
            ->post(route('sales_return.store', ['type' => 'simplified', 'id' => $firstSale->id], false), [
                'checkDetail' => [$firstSaleDetail->id],
                'cash' => 115,
                'notes' => 'مرتجع أول',
            ])->assertRedirect(route('sales_return.index', ['type' => 'simplified'], false));

        $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 12:30:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل المرتجع الثاني',
                'cash' => 115,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ])->assertOk();

        $secondSale = Invoice::query()->where('type', 'sale')->latest('id')->firstOrFail();
        $secondSaleDetail = $secondSale->details()->firstOrFail();

        $this->actingAs($user, 'admin-web')
            ->post(route('sales_return.store', ['type' => 'simplified', 'id' => $secondSale->id], false), [
                'checkDetail' => [$secondSaleDetail->id],
                'cash' => 115,
                'notes' => 'مرتجع ثانٍ',
            ])->assertRedirect(route('sales_return.index', ['type' => 'simplified'], false));

        $saleReturns = Invoice::query()
            ->where('type', 'sale_return')
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $saleReturns);
        $this->assertSame("SR-{$branch->id}-{$user->id}-00001", $saleReturns[0]->bill_number);
        $this->assertSame("SR-{$branch->id}-{$user->id}-00002", $saleReturns[1]->bill_number);

        $this->assertDatabaseHas('invoice_counters', [
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'type' => 'sale_return',
            'last_number' => 2,
        ]);

        $returnPrintResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales_return.show', ['id' => $saleReturns[1]->id], false));

        $returnPrintResponse->assertOk();
        $returnPrintResponse->assertSee($saleReturns[1]->bill_number);
    }

    private function createBranch(string $name, string $email, string $phone): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => $email,
            'phone' => $phone,
            'tax_number' => str_pad((string) random_int(1, 999999999999999), 15, '0', STR_PAD_LEFT),
            'commercial_register' => str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT),
            'short_address' => 'الرياض',
            'region' => 'الرياض',
            'city' => 'الرياض',
            'district' => 'الملز',
            'street_name' => 'الشارع الرئيسي',
            'building_number' => '1234',
            'plot_identification' => '5678',
            'country' => 'SA',
            'postal_code' => '12345',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    private function createFinancialYear(): FinancialYear
    {
        return FinancialYear::create([
            'description' => 'السنة المالية 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
        ]);
    }

    private function createTradingParties(): array
    {
        $customerAccount = $this->createAccount('ذمم العملاء', '9201');
        $supplierAccount = $this->createAccount('ذمم الموردين', '9202');

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل اختبار الترقيم',
            'phone' => '0551010101',
            'account_id' => $customerAccount->id,
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد اختبار الترقيم',
            'phone' => '0552020202',
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
            'name' => 'مخزن الترقيم',
            'code' => 'WH-NUM-1',
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

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

        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'فئة الترقيم', 'en' => 'Numbering Category'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-NUM-1',
            'description' => json_encode(['ar' => 'فئة الترقيم', 'en' => 'Numbering Category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'قطعة ترقيم', 'en' => 'Numbered Piece'], JSON_UNESCAPED_UNICODE),
            'code' => 'NUM-0001',
            'description' => null,
            'category_id' => $categoryId,
            'branch_id' => $branch->id,
            'inventory_classification' => 'gold',
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 0,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branch_items')->insert([
            'branch_id' => $branch->id,
            'item_id' => $itemId,
            'is_active' => true,
            'is_visible' => true,
            'sale_price_per_gram' => null,
            'published_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemUnitId = DB::table('item_units')->insertGetId([
            'item_id' => $itemId,
            'initial_cost_per_gram' => 80,
            'average_cost_per_gram' => 80,
            'current_cost_per_gram' => 80,
            'barcode' => 'NUM-0001-UNIT',
            'weight' => 1,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$itemUnitId, $caratId, $caratTypeId];
    }

    private function createBranchAccountSettings(Branch $branch, int $customerId, int $supplierId): void
    {
        $safeAccount = $this->createAccount('الصندوق التشغيلي', '9250');
        $bankAccount = $this->createAccount('البنك الافتراضي', '9251');
        $salesAccount = $this->createAccount('المبيعات', '9252');
        $returnSalesAccount = $this->createAccount('مرتجعات المبيعات', '9253');
        $stockCraftedAccount = $this->createAccount('مخزون المشغول', '9254');
        $madeAccount = $this->createAccount('أجرة الصياغة', '9255');
        $costCraftedAccount = $this->createAccount('تكلفة المشغول', '9256');
        $salesTaxAccount = $this->createAccount('ضريبة المبيعات', '9257');
        $purchaseTaxAccount = $this->createAccount('ضريبة المشتريات', '9258');

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

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }

    private function openShift(User $user, Branch $branch): void
    {
        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false));
    }

    private function createInvoice(Branch $branch, User $user, string $type, ?int $parentId = null): Invoice
    {
        return Invoice::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'type' => $type,
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'bill_client_name' => 'عميل نقدي',
            'bill_client_phone' => '0555555555',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ]);
    }
}
