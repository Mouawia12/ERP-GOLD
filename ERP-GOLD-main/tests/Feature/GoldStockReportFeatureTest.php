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

class GoldStockReportFeatureTest extends TestCase
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

    public function test_gold_stock_search_page_exposes_branch_and_period_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock.show',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('reports.gold_stock.search', [], false));

        $response->assertOk();
        $response->assertSee('name="branch_ids[]"', false);
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
    }

    public function test_gold_stock_report_respects_branch_filter(): void
    {
        $admin = $this->createAdminUser([
            'employee.stock.show',
        ]);

        $otherBranch = $this->createBranch('فرع مخزون آخر');
        $financialYearId = $this->createFinancialYear();
        [$craftedTypeId] = $this->createGoldCaratTypes();
        $caratId = $this->createGoldCarat('عيار 21', 'C21', 1, false);

        $branchInvoiceId = $this->insertInvoice([
            'bill_number' => 'STOCK-001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'type' => 'purchase',
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $branchInvoiceId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $craftedTypeId,
            'date' => '2026-03-22',
            'in_weight' => 1.234,
            'out_weight' => 0.123,
        ]);

        $otherBranchInvoiceId = $this->insertInvoice([
            'bill_number' => 'STOCK-002',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
            'type' => 'purchase',
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $otherBranchInvoiceId,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $craftedTypeId,
            'date' => '2026-03-22',
            'in_weight' => 9.876,
            'out_weight' => 0.456,
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.gold_stock.index', [], false), [
                'branch_id' => $admin->branch_id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
            ]);

        $response->assertOk();
        $response->assertSee($admin->branch->name);
        $response->assertSee('1.234');
        $response->assertSee('0.123');
        $response->assertSee('1.111');
        $response->assertDontSee($otherBranch->name);
        $response->assertDontSee('9.876');
        $response->assertDontSee('0.456');
        $response->assertDontSee('9.420');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions): User
    {
        $branch = $this->createBranch('فرع المخزون');

        $role = Role::create([
            'name' => ['ar' => 'مدير المخزون', 'en' => 'Stock Admin'],
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
            'name' => 'Gold Stock Admin',
            'email' => 'gold-stock-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createFinancialYear(): int
    {
        return DB::table('financial_years')->insertGetId([
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
     * @return array{0:int,1:int,2:int}
     */
    private function createGoldCaratTypes(): array
    {
        $craftedId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'مشغول', 'en' => 'Crafted'], JSON_UNESCAPED_UNICODE),
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scrapId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'كسر', 'en' => 'Scrap'], JSON_UNESCAPED_UNICODE),
            'key' => 'scrap',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pureId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'صافي', 'en' => 'Pure'], JSON_UNESCAPED_UNICODE),
            'key' => 'pure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$craftedId, $scrapId, $pureId];
    }

    private function createGoldCarat(string $titleAr, string $label, float $transformFactor, bool $isPure): int
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => $titleAr, 'en' => $titleAr], JSON_UNESCAPED_UNICODE),
            'label' => $label,
            'tax_id' => $taxId,
            'transform_factor' => (string) $transformFactor,
            'is_pure' => $isPure,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
            'bill_client_identity_number' => null,
            'parent_id' => null,
            'type' => 'purchase',
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
            'gold_carat_type_id' => null,
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
            'unit_tax_rate' => 0,
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
