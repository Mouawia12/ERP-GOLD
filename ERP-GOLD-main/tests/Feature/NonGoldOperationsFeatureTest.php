<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class NonGoldOperationsFeatureTest extends TestCase
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

    public function test_sales_store_supports_non_gold_items_and_prints_classification_label(): void
    {
        $branch = $this->createBranch('فرع بيع غير ذهبي');
        $user = $this->createUser($branch, 'nongold-sales@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);
        [$unitId] = $this->createNonGoldInventoryFixture($branch, 'silver', 'سوار فضي', 'NG-SALE-001');

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false));

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 18:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل فضة',
                'cash' => 150,
                'unit_id' => [$unitId],
                'quantity' => [1],
                'weight' => [2],
                'gram_price' => [75],
                'discount' => [0],
                'no_metal' => [0],
            ]);

        $response->assertOk()->assertJsonPath('status', true);

        $invoice = Invoice::query()->with('details')->where('type', 'sale')->firstOrFail();
        $detail = $invoice->details->firstOrFail();

        $this->assertNull($detail->gold_carat_id);
        $this->assertNull($detail->gold_carat_type_id);
        $this->assertSame(0.0, (float) $detail->unit_tax_rate);
        $this->assertSame(150.0, round((float) $detail->net_total, 2));

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.show', $invoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('فضة');
        $printResponse->assertSee('150');
    }

    public function test_purchases_search_and_store_support_non_gold_items_in_normal_flow(): void
    {
        $branch = $this->createBranch('فرع شراء غير ذهبي');
        $user = $this->createUser($branch, 'nongold-purchases@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$safeAccount, $legacyBankAccount, $stockCraftedAccount] = $this->createBranchAccountSettings($branch, $customerId, $supplierId);
        [$unitId] = $this->createNonGoldInventoryFixture($branch, 'collectible', 'قطعة مقتنيات', 'NG-PUR-001');

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false));

        $searchResponse = $this->actingAs($user, 'admin-web')
            ->post(route('items.purchases.search', [], false), [
                'branch_id' => $branch->id,
                'carat_type' => 'non_gold',
                'code' => 'قطعة مقتنيات',
            ]);

        $searchResponse->assertOk();
        $searchResponse->assertJsonPath('status', true);
        $searchResponse->assertJsonCount(1, 'data');
        $searchResponse->assertJsonPath('data.0.unit_id', $unitId);
        $searchResponse->assertJsonPath('data.0.carat', 'مقتنيات');

        $storeResponse = $this->actingAs($user, 'admin-web')
            ->postJson(route('purchases.store', [], false), [
                'bill_date' => '2026-03-22 19:00:00',
                'branch_id' => $branch->id,
                'carat_type' => 'non_gold',
                'purchase_type' => 'normal',
                'supplier_id' => $supplierId,
                'bill_client_name' => 'مورد مقتنيات',
                'cash' => 200,
                'unit_id' => [$unitId],
                'carats_id' => [null],
                'weight' => [2],
                'discount' => [0],
                'item_total_cost' => [180],
                'item_total_labor_cost' => [20],
            ]);

        $storeResponse->assertOk()->assertJsonPath('status', true);

        $invoice = Invoice::query()->with(['details', 'journalEntry.documents'])->where('type', 'purchase')->firstOrFail();
        $detail = $invoice->details->firstOrFail();

        $this->assertNull($invoice->purchase_carat_type_id);
        $this->assertNull($detail->gold_carat_id);
        $this->assertNull($detail->gold_carat_type_id);
        $this->assertSame(0.0, (float) $detail->unit_tax_rate);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $invoice->journalEntry?->id,
            'account_id' => $stockCraftedAccount->id,
            'debit' => 180,
            'credit' => 0,
        ]);

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('purchases.show', $invoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('مقتنيات');
        $printResponse->assertSee('200');

        $this->assertSame($legacyBankAccount->id, (int) DB::table('account_settings')->where('branch_id', $branch->id)->value('bank_account'));
        $this->assertSame($safeAccount->id, (int) DB::table('account_settings')->where('branch_id', $branch->id)->value('safe_account'));
    }

    public function test_sales_return_supports_non_gold_items(): void
    {
        $branch = $this->createBranch('فرع مرتجع غير ذهبي');
        $user = $this->createUser($branch, 'nongold-return@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);
        [$unitId] = $this->createNonGoldInventoryFixture($branch, 'silver', 'خاتم فضي', 'NG-RET-001');

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ]);

        $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 20:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل مرتجع فضي',
                'cash' => 90,
                'unit_id' => [$unitId],
                'quantity' => [1],
                'weight' => [1.5],
                'gram_price' => [60],
                'discount' => [0],
                'no_metal' => [0],
            ])
            ->assertOk();

        $saleInvoice = Invoice::query()->with('details')->where('type', 'sale')->firstOrFail();
        $saleDetail = $saleInvoice->details->firstOrFail();

        $returnResponse = $this->actingAs($user, 'admin-web')
            ->post(route('sales_return.store', ['type' => 'simplified', 'id' => $saleInvoice->id], false), [
                'checkDetail' => [$saleDetail->id],
                'cash' => 90,
                'notes' => 'مرتجع صنف فضي',
            ]);

        $returnResponse->assertRedirect(route('sales_return.index', ['type' => 'simplified'], false));

        $returnInvoice = Invoice::query()->with('details')->where('type', 'sale_return')->firstOrFail();
        $returnDetail = $returnInvoice->details->firstOrFail();

        $this->assertNull($returnDetail->gold_carat_id);
        $this->assertNull($returnDetail->gold_carat_type_id);

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales_return.show', $returnInvoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('فضة');
        $printResponse->assertSee('90');
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => 'Non Gold User',
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
        $customerAccount = $this->createAccount('ذمم العملاء', '9101');
        $supplierAccount = $this->createAccount('ذمم الموردين', '9102');

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل غير ذهبي',
            'phone' => '0551110001',
            'account_id' => $customerAccount->id,
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد غير ذهبي',
            'phone' => '0552220002',
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
            'name' => 'مخزن غير ذهبي',
            'code' => 'WH-NG-1',
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNonGoldInventoryFixture(Branch $branch, string $classification, string $nameAr, string $code): array
    {
        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'قسم غير ذهبي', 'en' => 'Non Gold Category'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-NG-1',
            'description' => json_encode(['ar' => 'تصنيف غير ذهبي', 'en' => 'Non gold category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => $nameAr, 'en' => $nameAr], JSON_UNESCAPED_UNICODE),
            'code' => $code,
            'description' => null,
            'category_id' => $categoryId,
            'branch_id' => $branch->id,
            'inventory_classification' => $classification,
            'gold_carat_id' => null,
            'gold_carat_type_id' => null,
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
            'initial_cost_per_gram' => 50,
            'average_cost_per_gram' => 50,
            'current_cost_per_gram' => 50,
            'barcode' => $code . '-UNIT',
            'weight' => 1,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$itemUnitId, $itemId];
    }

    private function createBranchAccountSettings(Branch $branch, int $customerId, int $supplierId): array
    {
        $safeAccount = $this->createAccount('الصندوق التشغيلي', '9150');
        $bankAccount = $this->createAccount('البنك الافتراضي', '9151');
        $salesAccount = $this->createAccount('المبيعات', '9152');
        $returnSalesAccount = $this->createAccount('مرتجعات المبيعات', '9153');
        $stockCraftedAccount = $this->createAccount('مخزون المشغول', '9154');
        $madeAccount = $this->createAccount('أجرة الصياغة', '9155');
        $costCraftedAccount = $this->createAccount('تكلفة المشغول', '9156');
        $salesTaxAccount = $this->createAccount('ضريبة المبيعات', '9157');
        $purchaseTaxAccount = $this->createAccount('ضريبة المشتريات', '9158');

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

        return [$safeAccount, $bankAccount, $stockCraftedAccount];
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
