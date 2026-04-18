<?php

namespace Tests\Feature;

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

class StockSettlementFeatureTest extends TestCase
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

    public function test_stock_settlement_store_saves_snapshot_weights_and_show_page_displays_them(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock_settlements.add',
            'employee.stock_settlements.show',
        ]);

        $context = $this->prepareSettlementContext($admin->branch_id);
        $itemId = $this->createSettlementItem($admin->branch_id, $context['carat_id'], $context['crafted_type_id'], 100);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('stock_settlements.store', [], false), [
                'bill_date' => '2026-03-22T10:30',
                'branch_id' => $admin->branch_id,
                'account_id' => $context['settlement_account_id'],
                'item_id' => [$itemId],
                'actual_balance' => [2],
                'weight' => [2.5],
                'diff_weight' => [0.5],
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
        ]);

        $invoice = Invoice::query()->where('type', 'stock_settlements')->firstOrFail();

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id,
            'item_id' => $itemId,
            'stock_actual_weight' => 2,
            'stock_counted_weight' => 2.5,
            'stock_diff_weight' => 0.5,
        ]);

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('stock_settlements.show', $invoice->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('تفاصيل جرد المخزون');
        $showResponse->assertSee('خاتم جرد');
        $showResponse->assertSee('2.000');
        $showResponse->assertSee('2.500');
        $showResponse->assertSee('0.500');
        $showResponse->assertSee('زيادة');
    }

    public function test_stock_settlement_search_finds_default_unit_barcode_for_handheld_scanner(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock_settlements.add',
        ]);

        $context = $this->prepareSettlementContext($admin->branch_id);
        $itemId = $this->createSettlementItem($admin->branch_id, $context['carat_id'], $context['crafted_type_id'], 100);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('stock_settlements.search', [], false), [
                'branch_id' => $admin->branch_id,
                'code' => '0000012500',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.item_id', $itemId);
        $response->assertJsonPath('data.0.barcode', '0000012500');
        $response->assertJsonPath('data.0.weight', 2.5);
    }

    public function test_stock_settlement_pages_expose_print_actions(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock_settlements.add',
            'employee.stock_settlements.show',
        ]);

        $context = $this->prepareSettlementContext($admin->branch_id);
        $itemId = $this->createSettlementItem($admin->branch_id, $context['carat_id'], $context['crafted_type_id'], 100);

        $createResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('stock_settlements.create', [], false));

        $createResponse->assertOk();
        $createResponse->assertSee('print_current_settlement_btn', false);
        $createResponse->assertSee('print_current_settlement_btn_side', false);
        $createResponse->assertSee('add_item', false);

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('stock_settlements.store', [], false), [
                'bill_date' => '2026-03-22T10:30',
                'branch_id' => $admin->branch_id,
                'account_id' => $context['settlement_account_id'],
                'item_id' => [$itemId],
                'actual_balance' => [2],
                'weight' => [2.5],
                'diff_weight' => [0.5],
            ])->assertOk();

        $invoice = Invoice::query()->where('type', 'stock_settlements')->latest('id')->firstOrFail();

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('stock_settlements.show', $invoice->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('print_saved_settlement_btn', false);
        $showResponse->assertSee('طباعة');
    }

    public function test_default_stock_settlement_store_keeps_snapshot_and_show_page_displays_deficit(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock_settlements.add',
            'employee.stock_settlements.show',
        ]);

        $context = $this->prepareSettlementContext($admin->branch_id);

        DB::table('gold_prices')->insert([
            'ounce_price' => 300,
            'ounce_14_price' => 180,
            'ounce_18_price' => 220,
            'ounce_21_price' => 250,
            'ounce_22_price' => 260,
            'ounce_24_price' => 280,
            'currency' => 'SAR',
            'source' => 'manual',
            'source_currency' => 'SAR',
            'meta' => json_encode(['test' => true]),
            'last_update' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('stock_settlements.store_by_default', [], false), [
                'bill_date' => '2026-03-22T14:15',
                'branch_id' => $admin->branch_id,
                'account_id' => $context['settlement_account_id'],
                'carat_id' => $context['carat_id'],
                'carat_type' => 'crafted',
                'actual_balance' => 10,
                'weight' => 8.5,
                'diff_weight' => -1.5,
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
        ]);

        $invoice = Invoice::query()->where('type', 'stock_settlements')->latest('id')->firstOrFail();

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id,
            'item_id' => null,
            'gold_carat_id' => $context['carat_id'],
            'stock_actual_weight' => 10,
            'stock_counted_weight' => 8.5,
            'stock_diff_weight' => -1.5,
        ]);

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('stock_settlements.show', $invoice->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('عيار افتراضي 21');
        $showResponse->assertSee('10.000');
        $showResponse->assertSee('8.500');
        $showResponse->assertSee('-1.500');
        $showResponse->assertSee('عجز');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '123456789',
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
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, int>
     */
    private function prepareSettlementContext(int $branchId): array
    {
        $this->createFinancialYear();
        $taxId = $this->createTax();
        $craftedTypeId = $this->createGoldCaratType('مصنع', 'crafted');
        $this->createGoldCaratType('كسر', 'scrap');
        $this->createGoldCaratType('خام', 'pure');
        $caratId = $this->createGoldCarat('21', '21', $taxId, 1);

        $settlementAccountId = $this->createAccount('حساب الجرد', '7000');
        $stockCraftedId = $this->createAccount('مخزون مصنع', '1100');
        $stockScrapId = $this->createAccount('مخزون كسر', '1200');
        $stockPureId = $this->createAccount('مخزون خام', '1300');

        DB::table('warehouses')->insert([
            'name' => 'مخزن الفرع',
            'code' => 'WH-1',
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_settings')->insert([
            'branch_id' => $branchId,
            'stock_account_crafted' => $stockCraftedId,
            'stock_account_scrap' => $stockScrapId,
            'stock_account_pure' => $stockPureId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'settlement_account_id' => $settlementAccountId,
            'crafted_type_id' => $craftedTypeId,
            'carat_id' => $caratId,
        ];
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

    private function createTax(): int
    {
        return DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGoldCaratType(string $title, string $key): int
    {
        return DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => $title, 'en' => $title], JSON_UNESCAPED_UNICODE),
            'key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGoldCarat(string $title, string $label, int $taxId, float $transformFactor): int
    {
        return DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => $title, 'en' => $title], JSON_UNESCAPED_UNICODE),
            'label' => $label,
            'tax_id' => $taxId,
            'transform_factor' => (string) $transformFactor,
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccount(string $name, string $code): int
    {
        return DB::table('accounts')->insertGetId([
            'name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'code' => $code,
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSettlementItem(int $branchId, int $caratId, int $caratTypeId, float $averageCostPerGram): int
    {
        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'خاتم جرد', 'en' => 'Count Ring'], JSON_UNESCAPED_UNICODE),
            'code' => '000001',
            'description' => null,
            'category_id' => null,
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
            'barcode' => '0000012500',
            'weight' => 2.5,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $itemId;
    }
}
