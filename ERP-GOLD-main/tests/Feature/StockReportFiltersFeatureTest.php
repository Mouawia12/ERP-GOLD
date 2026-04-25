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

class StockReportFiltersFeatureTest extends TestCase
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

    public function test_sales_report_search_page_exposes_invoice_number_range_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('reports.sales_report.search', [], false));

        $response->assertOk();
        $response->assertSee('name="invoice_number_from"', false);
        $response->assertSee('name="invoice_number_to"', false);
        $response->assertDontSee('name="invoice_number"', false);
    }

    public function test_sales_report_respects_user_time_and_invoice_number_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);
        $salesUser = $this->createUser($admin->branch_id, 'sales-filter-user@example.com');

        $dimensions = $this->prepareInventoryDimensions($admin->branch_id, 'customer');

        $earlyInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-001',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '10:00:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 575,
            'lines_total_after_discount' => 500,
            'taxes_total' => 75,
            'lines_total' => 500,
        ]);
        $this->insertInvoiceDetail($earlyInvoiceId, $dimensions, [
            'out_weight' => 5,
            'unit_price' => 100,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-002',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '14:30:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 460,
            'lines_total_after_discount' => 400,
            'taxes_total' => 60,
            'lines_total' => 400,
        ]);
        $this->insertInvoiceDetail($matchingInvoiceId, $dimensions, [
            'out_weight' => 4,
            'unit_price' => 100,
            'line_total' => 400,
            'line_tax' => 60,
            'net_total' => 460,
            'date' => '2026-03-22',
        ]);

        $otherUserInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-FILTER-003',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '14:35:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 345,
            'lines_total_after_discount' => 300,
            'taxes_total' => 45,
            'lines_total' => 300,
        ]);
        $this->insertInvoiceDetail($otherUserInvoiceId, $dimensions, [
            'out_weight' => 3,
            'unit_price' => 100,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
            'date' => '2026-03-22',
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('reports.sales_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'user_id' => $salesUser->id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '14:00',
                'to_time' => '14:59',
                'invoice_number_from' => 'SALE-FILTER-002',
                'invoice_number_to' => 'SALE-FILTER-002',
            ]);

        $response->assertOk();
        $response->assertSee('SALE-FILTER-002');
        $response->assertDontSee('SALE-FILTER-001');
        $response->assertDontSee('SALE-FILTER-003');
        $response->assertSee('عيار 21');
        $response->assertSee('460');
    }

    public function test_sales_total_report_respects_common_filters_and_keeps_carat_grouping(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);
        $salesUser = $this->createUser($admin->branch_id, 'sales-total-user@example.com');

        $dimensions = $this->prepareInventoryDimensions($admin->branch_id, 'customer');

        $excludedInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-TOTAL-001',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '11:00:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 575,
            'lines_total_after_discount' => 500,
            'taxes_total' => 75,
            'lines_total' => 500,
        ]);
        $this->insertInvoiceDetail($excludedInvoiceId, $dimensions, [
            'out_weight' => 5,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-TOTAL-002',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '16:00:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 920,
            'lines_total_after_discount' => 800,
            'taxes_total' => 120,
            'lines_total' => 800,
        ]);
        $this->insertInvoiceDetail($matchingInvoiceId, $dimensions, [
            'out_weight' => 8,
            'line_total' => 800,
            'line_tax' => 120,
            'net_total' => 920,
            'date' => '2026-03-22',
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('reports.sales_total_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'user_id' => $salesUser->id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '16:30',
                'invoice_number_from' => 'SALE-TOTAL-002',
                'invoice_number_to' => 'SALE-TOTAL-002',
                'netMoney' => 920,
            ]);

        $response->assertOk();
        $response->assertSee('SALE-TOTAL-002');
        $response->assertDontSee('SALE-TOTAL-001');
        $response->assertSee('عيار 21');
        $response->assertSee('920');
        $response->assertSee('8');
        $response->assertSee('الفرع: ' . $admin->branch->name);
    }

    public function test_purchases_total_report_respects_user_time_and_invoice_number_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);
        $purchaseUser = $this->createUser($admin->branch_id, 'purchase-total-user@example.com');

        $dimensions = $this->prepareInventoryDimensions($admin->branch_id, 'supplier');

        $excludedInvoiceId = $this->insertInvoice([
            'bill_number' => 'PUR-TOTAL-001',
            'type' => 'purchase',
            'payment_type' => 'credit_card',
            'date' => '2026-03-22',
            'time' => '09:00:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $purchaseUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 690,
            'lines_total_after_discount' => 600,
            'taxes_total' => 90,
            'lines_total' => 600,
        ]);
        $this->insertInvoiceDetail($excludedInvoiceId, $dimensions, [
            'in_weight' => 6,
            'line_total' => 600,
            'line_tax' => 90,
            'net_total' => 690,
            'date' => '2026-03-22',
        ]);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'PUR-TOTAL-002',
            'type' => 'purchase',
            'payment_type' => 'credit_card',
            'date' => '2026-03-22',
            'time' => '13:15:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $purchaseUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 1035,
            'lines_total_after_discount' => 900,
            'taxes_total' => 135,
            'lines_total' => 900,
        ]);
        $this->insertInvoiceDetail($matchingInvoiceId, $dimensions, [
            'in_weight' => 9,
            'line_total' => 900,
            'line_tax' => 135,
            'net_total' => 1035,
            'date' => '2026-03-22',
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('reports.purchases_total_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'user_id' => $purchaseUser->id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '12:00',
                'to_time' => '14:00',
                'invoice_number_from' => 'PUR-TOTAL-002',
                'invoice_number_to' => 'PUR-TOTAL-002',
                'netMoney' => 1035,
            ]);

        $response->assertOk();
        $response->assertSee('PUR-TOTAL-002');
        $response->assertDontSee('PUR-TOTAL-001');
        $response->assertSee('1,035.00');
    }

    public function test_daily_carat_report_respects_time_user_and_invoice_number_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);
        $salesUser = $this->createUser($admin->branch_id, 'daily-filter-user@example.com');

        $dimensions = $this->prepareInventoryDimensions($admin->branch_id, 'customer');

        $excludedInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-FILTER-001',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '09:30:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 575,
            'lines_total_after_discount' => 500,
            'taxes_total' => 75,
            'lines_total' => 500,
        ]);
        $this->insertInvoiceDetail($excludedInvoiceId, $dimensions, [
            'out_weight' => 5,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-FILTER-002',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '13:15:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $salesUser->id,
            'customer_id' => $dimensions['party_id'],
            'net_total' => 345,
            'lines_total_after_discount' => 300,
            'taxes_total' => 45,
            'lines_total' => 300,
        ]);
        $this->insertInvoiceDetail($matchingInvoiceId, $dimensions, [
            'out_weight' => 3,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
            'date' => '2026-03-22',
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('reports.daily_carat_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'user_id' => $salesUser->id,
                'carat_id' => $dimensions['gold_carat_id'],
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '12:00',
                'to_time' => '14:00',
                'invoice_number_from' => 'DAY-FILTER-002',
                'invoice_number_to' => 'DAY-FILTER-002',
            ]);

        $response->assertOk();
        $response->assertSee('345.00');
        $response->assertDontSee('575.00');
        $response->assertSee('3.000');
        $response->assertDontSee('5.000');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع فلاتر التقارير', 'en' => 'Report Filters Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير تقارير', 'en' => 'Reports Admin'],
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
            'name' => 'Report Filters Admin',
            'email' => 'report-filters-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
            'is_admin' => false,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createUser(int $branchId, string $email): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branchId,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function prepareInventoryDimensions(int $branchId, string $partyType): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goldCaratTypeId = DB::table('gold_carat_types')->insertGetId([
            'title' => 'مشغول',
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goldCaratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => 'خاتم فلتر التقارير',
            'code' => 'ITEM-FILTER-01',
            'branch_id' => $branchId,
            'gold_carat_id' => $goldCaratId,
            'gold_carat_type_id' => $goldCaratTypeId,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitId = DB::table('item_units')->insertGetId([
            'item_id' => $itemId,
            'barcode' => 'UNIT-FILTER-01',
            'weight' => 1,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partyId = DB::table('customers')->insertGetId([
            'name' => $partyType === 'supplier' ? 'مورد فلاتر التقارير' : 'عميل فلاتر التقارير',
            'phone' => '0555000000',
            'type' => $partyType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'tax_id' => $taxId,
            'gold_carat_type_id' => $goldCaratTypeId,
            'gold_carat_id' => $goldCaratId,
            'item_id' => $itemId,
            'unit_id' => $unitId,
            'party_id' => $partyId,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoice(array $attributes): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '10:00:00',
            'lines_total' => 0,
            'discount_total' => 0,
            'lines_total_after_discount' => 0,
            'taxes_total' => 0,
            'net_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, int>  $dimensions
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoiceDetail(int $invoiceId, array $dimensions, array $attributes): int
    {
        return DB::table('invoice_details')->insertGetId(array_merge([
            'invoice_id' => $invoiceId,
            'item_id' => $dimensions['item_id'],
            'unit_id' => $dimensions['unit_id'],
            'gold_carat_id' => $dimensions['gold_carat_id'],
            'gold_carat_type_id' => $dimensions['gold_carat_type_id'],
            'unit_tax_id' => $dimensions['tax_id'],
            'date' => '2026-03-22',
            'in_quantity' => 0,
            'out_quantity' => 1,
            'in_weight' => 0,
            'out_weight' => 0,
            'unit_cost' => 50,
            'unit_price' => 0,
            'unit_discount' => 0,
            'unit_tax' => 0,
            'unit_tax_rate' => 15,
            'line_total' => 0,
            'line_discount' => 0,
            'line_tax' => 0,
            'net_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
