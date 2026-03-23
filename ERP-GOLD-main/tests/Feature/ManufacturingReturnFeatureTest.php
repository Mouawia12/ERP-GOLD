<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
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

class ManufacturingReturnFeatureTest extends TestCase
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

    public function test_store_manufacturing_return_from_manufacturer_creates_stock_inflow_and_updates_order_progress(): void
    {
        $branch = $this->createBranch('فرع إرجاع المصنع');
        $user = $this->createUser($branch, 'manufacturing-return-in@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
            'employee.suppliers.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId, $stockCraftedId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع الإرجاع', '0554444444');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة إرجاع خام', 200);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 5, 200);

        $order = $this->createManufacturingOrder($branch->id, $user->id, $supplierId, $wipAccountId, $itemId, $caratId, $caratTypeId, 4, 2, 200);
        $parentDetail = $order->details()->firstOrFail();
        $this->createManufacturingReceipt($order, $user->id, $parentDetail->id, $itemId, $caratId, $caratTypeId, 2, 1, 200);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_returns.store', $order->id, false), [
                'bill_date' => '2026-03-23T17:10',
                'return_direction' => 'from_manufacturer',
                'notes' => 'رجع المصنع خامًا زائدًا',
                'parent_detail_id' => [$parentDetail->id],
                'quantity' => [0.5],
                'weight' => [1],
            ]);

        $return = Invoice::query()->where('type', 'manufacturing_return')->firstOrFail();
        $response->assertRedirect(route('manufacturing_returns.show', $return->id, false));

        $this->assertDatabaseHas('invoices', [
            'id' => $return->id,
            'manufacturing_return_direction' => 'from_manufacturer',
            'account_id' => $wipAccountId,
        ]);

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $return->id,
            'parent_id' => $parentDetail->id,
            'item_id' => $itemId,
            'in_quantity' => 0.5,
            'out_quantity' => 0,
            'in_weight' => 1,
            'out_weight' => 0,
            'stock_actual_weight' => 8,
            'unit_cost' => 200,
            'net_total' => 200,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $return->journalEntry?->id,
            'account_id' => $stockCraftedId,
            'debit' => 200,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $return->journalEntry?->id,
            'account_id' => $wipAccountId,
            'debit' => 0,
            'credit' => 200,
        ]);

        $branchBalance = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branch->id)
            ->where('invoice_details.item_id', $itemId)
            ->sum(DB::raw('invoice_details.in_weight - invoice_details.out_weight'));

        $this->assertSame(9.0, round((float) $branchBalance, 3));

        $orderShowResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.show', $order->id, false));

        $orderShowResponse->assertOk();
        $orderShowResponse->assertSee('الوزن المرتجع من المصنع');
        $orderShowResponse->assertSee($return->bill_number);
        $orderShowResponse->assertSee('1.000');

        $returnShowResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_returns.show', $return->id, false));

        $returnShowResponse->assertOk();
        $returnShowResponse->assertSee('تفاصيل إرجاع التصنيع');
        $returnShowResponse->assertSee('إرجاع من المصنع إلى الفرع');
        $returnShowResponse->assertSee('200.00');

        $reportResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('customers.report', $supplierId, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($return->bill_number);
        $reportResponse->assertSee('إرجاع من المصنع إلى الفرع');
        $reportResponse->assertSee('1.000');
    }

    public function test_store_manufacturing_return_to_manufacturer_creates_stock_outflow_and_reopens_order(): void
    {
        $branch = $this->createBranch('فرع إرجاع إلى المصنع');
        $user = $this->createUser($branch, 'manufacturing-return-out@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
            'employee.suppliers.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId, $stockCraftedId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع إعادة التشغيل', '0555555555');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة مراجعة مصنع', 200);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 5, 200);

        $order = $this->createManufacturingOrder($branch->id, $user->id, $supplierId, $wipAccountId, $itemId, $caratId, $caratTypeId, 4, 2, 200);
        $parentDetail = $order->details()->firstOrFail();
        $this->createManufacturingReceipt($order, $user->id, $parentDetail->id, $itemId, $caratId, $caratTypeId, 4, 2, 200);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_returns.store', $order->id, false), [
                'bill_date' => '2026-03-23T18:00',
                'return_direction' => 'to_manufacturer',
                'notes' => 'إرجاع جزء غير مطابق للمصنع',
                'parent_detail_id' => [$parentDetail->id],
                'quantity' => [0.5],
                'weight' => [1],
            ]);

        $return = Invoice::query()->where('type', 'manufacturing_return')->firstOrFail();
        $response->assertRedirect(route('manufacturing_returns.show', $return->id, false));

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $return->id,
            'parent_id' => $parentDetail->id,
            'item_id' => $itemId,
            'in_quantity' => 0,
            'out_quantity' => 0.5,
            'in_weight' => 0,
            'out_weight' => 1,
            'stock_actual_weight' => 10,
            'unit_cost' => 200,
            'net_total' => 200,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $return->journalEntry?->id,
            'account_id' => $wipAccountId,
            'debit' => 200,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $return->journalEntry?->id,
            'account_id' => $stockCraftedId,
            'debit' => 0,
            'credit' => 200,
        ]);

        $branchBalance = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branch->id)
            ->where('invoice_details.item_id', $itemId)
            ->sum(DB::raw('invoice_details.in_weight - invoice_details.out_weight'));

        $this->assertSame(9.0, round((float) $branchBalance, 3));

        $orderShowResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.show', $order->id, false));

        $orderShowResponse->assertOk();
        $orderShowResponse->assertSee('الوزن المرتجع إلى المصنع');
        $orderShowResponse->assertSee($return->bill_number);
        $orderShowResponse->assertSee('إرجاع من الفرع إلى المصنع');

        $reportResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('customers.report', $supplierId, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($return->bill_number);
        $reportResponse->assertSee('إرجاع من الفرع إلى المصنع');
    }

    public function test_manufacturing_return_to_manufacturer_cannot_exceed_available_received_weight(): void
    {
        $branch = $this->createBranch('فرع منع إرجاع زائد');
        $user = $this->createUser($branch, 'manufacturing-return-limit@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع حد الإرجاع', '0556666666');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة حد الإرجاع', 150);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 4, 150);

        $order = $this->createManufacturingOrder($branch->id, $user->id, $supplierId, $wipAccountId, $itemId, $caratId, $caratTypeId, 4, 2, 150);
        $parentDetail = $order->details()->firstOrFail();
        $this->createManufacturingReceipt($order, $user->id, $parentDetail->id, $itemId, $caratId, $caratTypeId, 2, 1, 150);

        $response = $this
            ->from(route('manufacturing_returns.create', $order->id, false))
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_returns.store', $order->id, false), [
                'bill_date' => '2026-03-23T18:30',
                'return_direction' => 'to_manufacturer',
                'parent_detail_id' => [$parentDetail->id],
                'quantity' => [1],
                'weight' => [3],
            ]);

        $response->assertRedirect(route('manufacturing_returns.create', $order->id, false));
        $response->assertSessionHasErrors('weight');
        $this->assertSame(0, Invoice::query()->where('type', 'manufacturing_return')->count());
    }

    public function test_branch_user_cannot_access_foreign_branch_manufacturing_return(): void
    {
        $ownBranch = $this->createBranch('فرع إرجاع أساسي');
        $foreignBranch = $this->createBranch('فرع إرجاع خارجي');
        $user = $this->createUser($ownBranch, 'manufacturing-return-own@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'manufacturing-return-foreign@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);

        $this->createFinancialYear();
        [$ownWipAccountId] = $this->prepareManufacturingAccounts($ownBranch->id);
        [$foreignWipAccountId] = $this->prepareManufacturingAccounts($foreignBranch->id);
        [$supplierId] = $this->createSupplier('مصنع عزل الإرجاع', '0557777000');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $ownItemId = $this->createGoldItem($ownBranch->id, $caratId, $caratTypeId, 'قطعة فرع أساسي', 100);
        $foreignItemId = $this->createGoldItem($foreignBranch->id, $caratId, $caratTypeId, 'قطعة فرع خارجي', 100);

        $this->seedOpeningStock($ownBranch->id, $user->id, $supplierId, $ownItemId, $caratId, $caratTypeId, 5, 1, 100);
        $this->seedOpeningStock($foreignBranch->id, $foreignUser->id, $supplierId, $foreignItemId, $caratId, $caratTypeId, 5, 1, 100);

        $ownOrder = $this->createManufacturingOrder($ownBranch->id, $user->id, $supplierId, $ownWipAccountId, $ownItemId, $caratId, $caratTypeId, 2, 1, 100);
        $foreignOrder = $this->createManufacturingOrder($foreignBranch->id, $foreignUser->id, $supplierId, $foreignWipAccountId, $foreignItemId, $caratId, $caratTypeId, 2, 1, 100);
        $foreignDetail = $foreignOrder->details()->firstOrFail();
        $foreignReturn = $this->createManufacturingReturn($foreignOrder, $foreignUser->id, $foreignDetail->id, $foreignItemId, $caratId, $caratTypeId, 1, 1, 100, 'from_manufacturer');

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_returns.create', $ownOrder->id, false))
            ->assertOk()
            ->assertSee($ownOrder->bill_number);

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_returns.create', $foreignOrder->id, false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->post(route('manufacturing_returns.store', $foreignOrder->id, false), [
                'bill_date' => '2026-03-23T19:00',
                'return_direction' => 'from_manufacturer',
                'parent_detail_id' => [$foreignDetail->id],
                'quantity' => [1],
                'weight' => [1],
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_returns.show', $foreignReturn->id, false))
            ->assertForbidden();
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
                'name' => ['ar' => 'دور إرجاع تصنيع ' . $user->id, 'en' => 'Manufacturing Return Role ' . $user->id],
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
            'code' => 'MFR-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
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

    private function createManufacturingOrder(
        int $branchId,
        int $userId,
        int $supplierId,
        int $wipAccountId,
        int $itemId,
        int $caratId,
        int $caratTypeId,
        float $weight,
        float $quantity,
        float $costPerGram
    ): Invoice {
        $warehouseId = (int) DB::table('warehouses')->where('branch_id', $branchId)->value('id');

        $order = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'customer_id' => $supplierId,
            'type' => 'manufacturing_order',
            'account_id' => $wipAccountId,
            'date' => '2026-03-23',
            'time' => '09:00:00',
            'lines_total' => round($weight * $costPerGram, 2),
            'discount_total' => 0,
            'lines_total_after_discount' => round($weight * $costPerGram, 2),
            'taxes_total' => 0,
            'net_total' => round($weight * $costPerGram, 2),
            'user_id' => $userId,
        ]);

        $order->details()->create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'out_quantity' => $quantity,
            'out_weight' => $weight,
            'unit_cost' => $costPerGram,
            'unit_price' => $costPerGram,
            'line_total' => round($weight * $costPerGram, 2),
            'net_total' => round($weight * $costPerGram, 2),
            'stock_actual_weight' => 5,
        ]);

        return $order->load('details');
    }

    private function createManufacturingReceipt(
        Invoice $order,
        int $userId,
        int $parentDetailId,
        int $itemId,
        int $caratId,
        int $caratTypeId,
        float $weight,
        float $quantity,
        float $costPerGram
    ): Invoice {
        $invoice = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $order->branch_id,
            'warehouse_id' => $order->warehouse_id,
            'customer_id' => $order->customer_id,
            'parent_id' => $order->id,
            'type' => 'manufacturing_receipt',
            'account_id' => $order->account_id,
            'date' => '2026-03-23',
            'time' => '10:00:00',
            'lines_total' => round($weight * $costPerGram, 2),
            'discount_total' => 0,
            'lines_total_after_discount' => round($weight * $costPerGram, 2),
            'taxes_total' => 0,
            'net_total' => round($weight * $costPerGram, 2),
            'user_id' => $userId,
        ]);

        $invoice->details()->create([
            'warehouse_id' => $order->warehouse_id,
            'parent_id' => $parentDetailId,
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
            'net_total' => round($weight * $costPerGram, 2),
            'stock_actual_weight' => 6,
        ]);

        return $invoice;
    }

    private function createManufacturingReturn(
        Invoice $order,
        int $userId,
        int $parentDetailId,
        int $itemId,
        int $caratId,
        int $caratTypeId,
        float $weight,
        float $quantity,
        float $costPerGram,
        string $direction
    ): Invoice {
        $isFromManufacturer = $direction === 'from_manufacturer';

        $invoice = Invoice::create([
            'financial_year' => 1,
            'branch_id' => $order->branch_id,
            'warehouse_id' => $order->warehouse_id,
            'customer_id' => $order->customer_id,
            'parent_id' => $order->id,
            'type' => 'manufacturing_return',
            'manufacturing_return_direction' => $direction,
            'account_id' => $order->account_id,
            'date' => '2026-03-23',
            'time' => '11:00:00',
            'lines_total' => round($weight * $costPerGram, 2),
            'discount_total' => 0,
            'lines_total_after_discount' => round($weight * $costPerGram, 2),
            'taxes_total' => 0,
            'net_total' => round($weight * $costPerGram, 2),
            'user_id' => $userId,
        ]);

        $invoice->details()->create([
            'warehouse_id' => $order->warehouse_id,
            'parent_id' => $parentDetailId,
            'item_id' => $itemId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'date' => '2026-03-23',
            'in_quantity' => $isFromManufacturer ? $quantity : 0,
            'out_quantity' => $isFromManufacturer ? 0 : $quantity,
            'in_weight' => $isFromManufacturer ? $weight : 0,
            'out_weight' => $isFromManufacturer ? 0 : $weight,
            'unit_cost' => $costPerGram,
            'unit_price' => $costPerGram,
            'line_total' => round($weight * $costPerGram, 2),
            'net_total' => round($weight * $costPerGram, 2),
            'stock_actual_weight' => 6,
        ]);

        return $invoice;
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
