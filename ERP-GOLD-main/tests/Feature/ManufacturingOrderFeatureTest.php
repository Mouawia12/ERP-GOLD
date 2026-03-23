<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManufacturingOrderFeatureTest extends TestCase
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

    public function test_store_manufacturing_order_creates_stock_outflow_journal_and_supplier_report_entry(): void
    {
        $branch = $this->createBranch('فرع التصنيع');
        $user = $this->createUser($branch, 'manufacturing@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
            'employee.suppliers.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId, $stockCraftedId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId, $supplierAccountId] = $this->createSupplier('مصنع خارجي', '0551111111');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'سبيكة تصنيع', 200);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 5, 200);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_orders.store', [], false), [
                'bill_date' => '2026-03-23T13:45',
                'branch_id' => $branch->id,
                'manufacturer_id' => $supplierId,
                'account_id' => $wipAccountId,
                'item_id' => [$itemId],
                'quantity' => [2],
                'weight' => [4],
                'notes' => 'إرسال ذهب للتصنيع الخارجي',
            ]);

        $invoice = Invoice::query()->where('type', 'manufacturing_order')->firstOrFail();

        $response->assertRedirect(route('manufacturing_orders.show', $invoice->id, false));

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id,
            'item_id' => $itemId,
            'out_quantity' => 2,
            'out_weight' => 4,
            'stock_actual_weight' => 10,
            'unit_cost' => 200,
            'net_total' => 800,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Invoice::class,
            'journalable_id' => $invoice->id,
            'branch_id' => $branch->id,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $invoice->journalEntry?->id,
            'account_id' => $wipAccountId,
            'debit' => 800,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $invoice->journalEntry?->id,
            'account_id' => $stockCraftedId,
            'debit' => 0,
            'credit' => 800,
        ]);

        $branchBalance = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branch->id)
            ->where('invoice_details.item_id', $itemId)
            ->sum(DB::raw('invoice_details.in_weight - invoice_details.out_weight'));

        $this->assertSame(6.0, round((float) $branchBalance, 3));

        $showResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.show', $invoice->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('تفاصيل أمر التصنيع');
        $showResponse->assertSee('مصنع خارجي');
        $showResponse->assertSee('سبيكة تصنيع');
        $showResponse->assertSee('10.000');
        $showResponse->assertSee('4.000');
        $showResponse->assertSee('800.00');

        $reportResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('customers.report', $supplierId, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($invoice->bill_number);
        $reportResponse->assertSee('إرسال للتصنيع');
        $reportResponse->assertSee('مصنع خارجي');
        $reportResponse->assertSee('4.000');
        $reportResponse->assertSee('800.00');
    }

    public function test_store_manufacturing_order_rejects_weight_above_branch_balance(): void
    {
        $branch = $this->createBranch('فرع فحص الرصيد');
        $user = $this->createUser($branch, 'manufacturing-balance@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع الرصيد', '0552222222');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'سوار تصنيع', 150);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 3, 1, 150);

        $response = $this
            ->from(route('manufacturing_orders.create', [], false))
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_orders.store', [], false), [
                'bill_date' => '2026-03-23T14:20',
                'branch_id' => $branch->id,
                'manufacturer_id' => $supplierId,
                'account_id' => $wipAccountId,
                'item_id' => [$itemId],
                'quantity' => [1],
                'weight' => [4],
            ]);

        $response->assertRedirect(route('manufacturing_orders.create', [], false));
        $response->assertSessionHasErrors('weight');
        $this->assertDatabaseMissing('invoices', [
            'type' => 'manufacturing_order',
        ]);
    }

    public function test_branch_user_can_only_access_his_branch_manufacturing_orders(): void
    {
        $ownBranch = $this->createBranch('فرع التصنيع الأساسي');
        $foreignBranch = $this->createBranch('فرع التصنيع الخارجي');
        $user = $this->createUser($ownBranch, 'manufacturing-scope@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'manufacturing-foreign@example.com');

        $this->createFinancialYear();
        [$ownWipAccountId] = $this->prepareManufacturingAccounts($ownBranch->id);
        [$foreignWipAccountId] = $this->prepareManufacturingAccounts($foreignBranch->id);
        [$supplierId] = $this->createSupplier('مصنع العزل', '0553333333');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $ownItemId = $this->createGoldItem($ownBranch->id, $caratId, $caratTypeId, 'قطعة فرع أساسي', 100);
        $foreignItemId = $this->createGoldItem($foreignBranch->id, $caratId, $caratTypeId, 'قطعة فرع خارجي', 100);
        $this->seedOpeningStock($ownBranch->id, $user->id, $supplierId, $ownItemId, $caratId, $caratTypeId, 5, 1, 100);
        $this->seedOpeningStock($foreignBranch->id, $foreignUser->id, $supplierId, $foreignItemId, $caratId, $caratTypeId, 5, 1, 100);

        $ownOrder = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $ownBranch->id,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $ownWipAccountId,
            'date' => '2026-03-23',
            'time' => '09:00:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 0,
            'net_total' => 100,
            'user_id' => $user->id,
        ]);

        $ownOrder->details()->create([
            'item_id' => $ownItemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'out_quantity' => 1,
            'out_weight' => 1,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 100,
            'net_total' => 100,
            'stock_actual_weight' => 5,
        ]);

        $foreignOrder = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $foreignBranch->id,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $foreignWipAccountId,
            'date' => '2026-03-23',
            'time' => '10:00:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 0,
            'net_total' => 100,
            'user_id' => $foreignUser->id,
        ]);

        $foreignOrder->details()->create([
            'item_id' => $foreignItemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'out_quantity' => 1,
            'out_weight' => 1,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 100,
            'net_total' => 100,
            'stock_actual_weight' => 5,
        ]);

        $createResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.create', [], false));

        $createResponse->assertOk();
        $createResponse->assertSee('فرع التصنيع الأساسي');
        $createResponse->assertDontSee('فرع التصنيع الخارجي');

        $indexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.index', [], false));

        $indexResponse->assertOk();
        $indexResponse->assertJsonFragment([
            'bill_number' => $ownOrder->bill_number,
        ]);
        $indexResponse->assertJsonMissing([
            'bill_number' => $foreignOrder->bill_number,
        ]);

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.show', $foreignOrder->id, false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->post(route('manufacturing_orders.store', [], false), [
                'bill_date' => '2026-03-23T11:10',
                'branch_id' => $foreignBranch->id,
                'manufacturer_id' => $supplierId,
                'account_id' => $foreignWipAccountId,
                'item_id' => [$foreignItemId],
                'quantity' => [1],
                'weight' => [1],
            ])
            ->assertForbidden();
    }

    public function test_manufacturing_orders_index_tracks_open_completed_and_late_statuses(): void
    {
        Carbon::setTestNow('2026-03-23 12:00:00');

        $branch = $this->createBranch('فرع متابعة التصنيع');
        $user = $this->createUser($branch, 'manufacturing-status@example.com', [
            'employee.manufacturing_orders.show',
            'employee.manufacturing_orders.add',
        ]);

        $this->createFinancialYear();
        [$wipAccountId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع الحالة', '0554444444');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة حالة', 100);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 4, 100);

        $lateOrder = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branch->id,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $wipAccountId,
            'date' => '2026-03-22',
            'time' => '09:00:00',
            'lines_total' => 200,
            'discount_total' => 0,
            'lines_total_after_discount' => 200,
            'taxes_total' => 0,
            'net_total' => 200,
            'user_id' => $user->id,
        ]);
        $lateOrder->details()->create([
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-22',
            'out_quantity' => 1,
            'out_weight' => 2,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 200,
            'net_total' => 200,
            'stock_actual_weight' => 10,
        ]);

        $openOrder = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branch->id,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $wipAccountId,
            'date' => '2026-03-23',
            'time' => '10:00:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 0,
            'net_total' => 100,
            'user_id' => $user->id,
        ]);
        $openOrder->details()->create([
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'out_quantity' => 1,
            'out_weight' => 1,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 100,
            'net_total' => 100,
            'stock_actual_weight' => 8,
        ]);

        $completedOrder = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branch->id,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $wipAccountId,
            'date' => '2026-03-22',
            'time' => '11:00:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 0,
            'net_total' => 100,
            'user_id' => $user->id,
        ]);
        $completedDetail = $completedOrder->details()->create([
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-22',
            'out_quantity' => 1,
            'out_weight' => 1,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 100,
            'net_total' => 100,
            'stock_actual_weight' => 7,
        ]);

        $completedReceipt = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branch->id,
            'customer_id' => $supplierId,
            'parent_id' => $completedOrder->id,
            'type' => 'manufacturing_receipt',
            'account_id' => $wipAccountId,
            'date' => '2026-03-23',
            'time' => '11:30:00',
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 0,
            'net_total' => 100,
            'user_id' => $user->id,
        ]);
        $completedReceipt->details()->create([
            'parent_id' => $completedDetail->id,
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'in_quantity' => 1,
            'in_weight' => 1,
            'unit_cost' => 100,
            'unit_price' => 100,
            'line_total' => 100,
            'net_total' => 100,
            'stock_actual_weight' => 7,
        ]);

        $pageResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.index', [], false));

        $pageResponse->assertOk();
        $pageResponse->assertSee('كل الأوامر');
        $pageResponse->assertSee('المفتوحة');
        $pageResponse->assertSee('المكتملة');
        $pageResponse->assertSee('المتأخرة');
        $pageResponse->assertSee('3');
        $pageResponse->assertSee('1');

        $lateResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.index', ['status' => 'late'], false));

        $lateResponse->assertOk();
        $lateResponse->assertJsonFragment([
            'bill_number' => $lateOrder->bill_number,
        ]);
        $lateResponse->assertJsonMissing([
            'bill_number' => $openOrder->bill_number,
        ]);
        $lateResponse->assertJsonMissing([
            'bill_number' => $completedOrder->bill_number,
        ]);

        $completedResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.index', ['status' => 'completed'], false));

        $completedResponse->assertOk();
        $completedResponse->assertJsonFragment([
            'bill_number' => $completedOrder->bill_number,
        ]);
        $completedResponse->assertJsonMissing([
            'bill_number' => $lateOrder->bill_number,
        ]);

        Carbon::setTestNow();
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => strtolower(str_replace(' ', '-', $name)) . '@example.com',
            'phone' => '0550000000',
            'tax_number' => str_pad((string) (Branch::query()->count() + 1), 15, '3', STR_PAD_LEFT),
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email, array $permissions = []): User
    {
        $user = User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'admin-web');
        }

        if ($permissions !== []) {
            $role = Role::create([
                'name' => ['ar' => 'دور تصنيع ' . $user->id, 'en' => 'Manufacturing Role ' . $user->id],
                'guard_name' => 'admin-web',
            ]);
            $role->givePermissionTo($permissions);
            $user->assignRole($role);
        }

        return $user;
    }

    private function createFinancialYear(): void
    {
        DB::table('financial_years')->insert([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function prepareManufacturingAccounts(int $branchId): array
    {
        $wipAccount = $this->createAccount('مخزون تحت التصنيع ' . $branchId, 'WIP-' . $branchId);
        $stockCrafted = $this->createAccount('مخزون مشغول ' . $branchId, 'STK-' . $branchId);
        $stockScrap = $this->createAccount('مخزون كسر ' . $branchId, 'SCR-' . $branchId);
        $stockPure = $this->createAccount('مخزون صافي ' . $branchId, 'PUR-' . $branchId);

        DB::table('account_settings')->insert([
            'branch_id' => $branchId,
            'stock_account_crafted' => $stockCrafted->id,
            'stock_account_scrap' => $stockScrap->id,
            'stock_account_pure' => $stockPure->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'name' => 'مخزن الفرع ' . $branchId,
            'code' => 'WH-' . $branchId,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$wipAccount->id, $stockCrafted->id];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function createSupplier(string $name, string $phone): array
    {
        $account = $this->createAccount($name . ' حساب', 'SUP-' . random_int(1000, 9999));

        $supplierId = (int) DB::table('customers')->insertGetId([
            'name' => $name,
            'phone' => $phone,
            'account_id' => $account->id,
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$supplierId, $account->id];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function prepareGoldLookups(): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratTypeId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'مشغول', 'en' => 'Crafted'], JSON_UNESCAPED_UNICODE),
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => '21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$caratId, $caratTypeId];
    }

    private function createGoldItem(int $branchId, int $caratId, int $caratTypeId, string $title, float $averageCostPerGram): int
    {
        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'تصنيف', 'en' => 'Category'], JSON_UNESCAPED_UNICODE),
            'description' => json_encode(['ar' => 'تصنيف تصنيع', 'en' => 'Manufacturing Category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => $title, 'en' => $title], JSON_UNESCAPED_UNICODE),
            'code' => 'MFG-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'description' => null,
            'category_id' => $categoryId,
            'branch_id' => $branchId,
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

        DB::table('item_units')->insert([
            'item_id' => $itemId,
            'initial_cost_per_gram' => $averageCostPerGram,
            'average_cost_per_gram' => $averageCostPerGram,
            'current_cost_per_gram' => $averageCostPerGram,
            'barcode' => 'BAR-' . $itemId,
            'weight' => 1,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branch_items')->insert([
            'branch_id' => $branchId,
            'item_id' => $itemId,
            'is_active' => true,
            'is_visible' => true,
            'sale_price_per_gram' => null,
            'published_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $itemId;
    }

    private function seedOpeningStock(
        int $branchId,
        int $userId,
        int $supplierId,
        int $itemId,
        int $caratId,
        int $caratTypeId,
        float $weight,
        float $quantity,
        float $costPerGram
    ): void {
        $invoice = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branchId,
            'customer_id' => $supplierId,
            'type' => 'initial_quantities',
            'date' => '2026-03-23',
            'time' => '08:00:00',
            'lines_total' => round($weight * $costPerGram, 2),
            'discount_total' => 0,
            'lines_total_after_discount' => round($weight * $costPerGram, 2),
            'taxes_total' => 0,
            'net_total' => round($weight * $costPerGram, 2),
            'user_id' => $userId,
        ]);

        $invoice->details()->create([
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'in_quantity' => $quantity,
            'out_quantity' => 0,
            'in_weight' => $weight,
            'out_weight' => 0,
            'unit_cost' => $costPerGram,
            'unit_price' => $costPerGram,
            'line_total' => round($weight * $costPerGram, 2),
            'line_discount' => 0,
            'line_tax' => 0,
            'net_total' => round($weight * $costPerGram, 2),
        ]);
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
