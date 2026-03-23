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

class ManufacturingReceiptFeatureTest extends TestCase
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

    public function test_store_manufacturing_receipt_creates_stock_inflow_journal_and_updates_order_progress(): void
    {
        $branch = $this->createBranch('فرع الاستلام');
        $user = $this->createUser($branch, 'manufacturing-receipt@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
            'employee.suppliers.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId, $stockCraftedId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع الاستلام', '0557777777');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة تحت التصنيع', 200);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 5, 200);

        $this->actingAs($user, 'admin-web')->post(route('manufacturing_orders.store', [], false), [
            'bill_date' => '2026-03-23T13:45',
            'branch_id' => $branch->id,
            'manufacturer_id' => $supplierId,
            'account_id' => $wipAccountId,
            'item_id' => [$itemId],
            'quantity' => [2],
            'weight' => [4],
            'notes' => 'إرسال للتصنيع قبل الاستلام',
        ])->assertRedirect();

        $order = Invoice::query()
            ->where('type', 'manufacturing_order')
            ->with('details')
            ->firstOrFail();
        $parentDetail = $order->details->firstOrFail();

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_receipts.store', $order->id, false), [
                'bill_date' => '2026-03-23T16:00',
                'notes' => 'تم استلام جزء من التصنيع',
                'parent_detail_id' => [$parentDetail->id],
                'quantity' => [1],
                'weight' => [2],
            ]);

        $receipt = Invoice::query()->where('type', 'manufacturing_receipt')->firstOrFail();

        $response->assertRedirect(route('manufacturing_receipts.show', $receipt->id, false));

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $receipt->id,
            'parent_id' => $parentDetail->id,
            'item_id' => $itemId,
            'in_quantity' => 1,
            'in_weight' => 2,
            'stock_actual_weight' => 6,
            'unit_cost' => 200,
            'net_total' => 400,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Invoice::class,
            'journalable_id' => $receipt->id,
            'branch_id' => $branch->id,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $receipt->journalEntry?->id,
            'account_id' => $stockCraftedId,
            'debit' => 400,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $receipt->journalEntry?->id,
            'account_id' => $wipAccountId,
            'debit' => 0,
            'credit' => 400,
        ]);

        $branchBalance = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branch->id)
            ->where('invoice_details.item_id', $itemId)
            ->sum(DB::raw('invoice_details.in_weight - invoice_details.out_weight'));

        $this->assertSame(8.0, round((float) $branchBalance, 3));

        $orderShowResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_orders.show', $order->id, false));

        $orderShowResponse->assertOk();
        $orderShowResponse->assertSee('الوزن المستلم');
        $orderShowResponse->assertSee('الوزن المتبقي');
        $orderShowResponse->assertSee($receipt->bill_number);
        $orderShowResponse->assertSee('2.000');

        $receiptShowResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('manufacturing_receipts.show', $receipt->id, false));

        $receiptShowResponse->assertOk();
        $receiptShowResponse->assertSee('تفاصيل استلام من التصنيع');
        $receiptShowResponse->assertSee('مصنع الاستلام');
        $receiptShowResponse->assertSee('قطعة تحت التصنيع');
        $receiptShowResponse->assertSee('2.000');
        $receiptShowResponse->assertSee('400.00');

        $reportResponse = $this
            ->actingAs($user, 'admin-web')
            ->get(route('customers.report', $supplierId, false));

        $reportResponse->assertOk();
        $reportResponse->assertSee($receipt->bill_number);
        $reportResponse->assertSee('استلام من التصنيع');
        $reportResponse->assertSee('2.000');
        $reportResponse->assertSee('400.00');
    }

    public function test_manufacturing_receipt_cannot_exceed_remaining_weight(): void
    {
        $branch = $this->createBranch('فرع تجاوز الاستلام');
        $user = $this->createUser($branch, 'manufacturing-receipt-limit@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);

        $this->createFinancialYear();
        [$wipAccountId] = $this->prepareManufacturingAccounts($branch->id);
        [$supplierId] = $this->createSupplier('مصنع الحد', '0558888888');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $itemId = $this->createGoldItem($branch->id, $caratId, $caratTypeId, 'قطعة حدية', 150);
        $this->seedOpeningStock($branch->id, $user->id, $supplierId, $itemId, $caratId, $caratTypeId, 10, 4, 150);

        $order = $this->createManufacturingOrder($branch->id, $user->id, $supplierId, $wipAccountId, $itemId, $caratId, $caratTypeId, 4, 2, 150);
        $parentDetail = $order->details()->firstOrFail();

        $this->actingAs($user, 'admin-web')->post(route('manufacturing_receipts.store', $order->id, false), [
            'bill_date' => '2026-03-23T16:30',
            'parent_detail_id' => [$parentDetail->id],
            'quantity' => [1],
            'weight' => [3],
        ])->assertRedirect();

        $response = $this
            ->from(route('manufacturing_receipts.create', $order->id, false))
            ->actingAs($user, 'admin-web')
            ->post(route('manufacturing_receipts.store', $order->id, false), [
                'bill_date' => '2026-03-23T17:00',
                'parent_detail_id' => [$parentDetail->id],
                'quantity' => [1],
                'weight' => [2],
            ]);

        $response->assertRedirect(route('manufacturing_receipts.create', $order->id, false));
        $response->assertSessionHasErrors('weight');
        $this->assertSame(1, Invoice::query()->where('type', 'manufacturing_receipt')->count());
    }

    public function test_branch_user_cannot_access_foreign_branch_receipt_create_store_or_show(): void
    {
        $ownBranch = $this->createBranch('فرع استلام أساسي');
        $foreignBranch = $this->createBranch('فرع استلام خارجي');
        $user = $this->createUser($ownBranch, 'manufacturing-receipt-own@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);
        $foreignUser = $this->createUser($foreignBranch, 'manufacturing-receipt-foreign@example.com', [
            'employee.manufacturing_orders.add',
            'employee.manufacturing_orders.show',
        ]);

        $this->createFinancialYear();
        [$ownWipAccountId] = $this->prepareManufacturingAccounts($ownBranch->id);
        [$foreignWipAccountId] = $this->prepareManufacturingAccounts($foreignBranch->id);
        [$supplierId] = $this->createSupplier('مصنع العزل للاستلام', '0559999999');
        [$caratId, $caratTypeId] = $this->prepareGoldLookups();
        $ownItemId = $this->createGoldItem($ownBranch->id, $caratId, $caratTypeId, 'قطعة استلام أساسية', 100);
        $foreignItemId = $this->createGoldItem($foreignBranch->id, $caratId, $caratTypeId, 'قطعة استلام خارجية', 100);

        $this->seedOpeningStock($ownBranch->id, $user->id, $supplierId, $ownItemId, $caratId, $caratTypeId, 5, 1, 100);
        $this->seedOpeningStock($foreignBranch->id, $foreignUser->id, $supplierId, $foreignItemId, $caratId, $caratTypeId, 5, 1, 100);

        $ownOrder = $this->createManufacturingOrder($ownBranch->id, $user->id, $supplierId, $ownWipAccountId, $ownItemId, $caratId, $caratTypeId, 2, 1, 100);
        $foreignOrder = $this->createManufacturingOrder($foreignBranch->id, $foreignUser->id, $supplierId, $foreignWipAccountId, $foreignItemId, $caratId, $caratTypeId, 2, 1, 100);
        $foreignOrderDetail = $foreignOrder->details()->firstOrFail();
        $foreignReceipt = $this->createManufacturingReceipt($foreignOrder, $foreignUser->id, $foreignOrderDetail->id, $foreignItemId, $caratId, $caratTypeId, 1, 1, 100);

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_receipts.create', $ownOrder->id, false))
            ->assertOk()
            ->assertSee($ownOrder->bill_number);

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_receipts.create', $foreignOrder->id, false))
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->post(route('manufacturing_receipts.store', $foreignOrder->id, false), [
                'bill_date' => '2026-03-23T18:00',
                'parent_detail_id' => [$foreignOrderDetail->id],
                'quantity' => [1],
                'weight' => [1],
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin-web')
            ->get(route('manufacturing_receipts.show', $foreignReceipt->id, false))
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
                'name' => ['ar' => 'دور استلام تصنيع ' . $user->id, 'en' => 'Manufacturing Receipt Role ' . $user->id],
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
        $receipt = Invoice::create([
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

        $receipt->details()->create([
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
            'stock_actual_weight' => 3,
        ]);

        return $receipt;
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
