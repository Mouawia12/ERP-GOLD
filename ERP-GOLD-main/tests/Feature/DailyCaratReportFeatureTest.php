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

class DailyCaratReportFeatureTest extends TestCase
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

    public function test_daily_carat_report_aggregates_sales_and_purchases_by_day(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);

        [$carat21Id] = $this->prepareReportDimensions();

        $saleInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-SALE-1',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '10:00:00',
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
            'date' => '2026-03-22',
        ]);

        $purchaseInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-PUR-1',
            'type' => 'purchase',
            'payment_type' => 'credit_card',
            'date' => '2026-03-22',
            'time' => '12:00:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $purchaseInvoiceId,
            'gold_carat_id' => $carat21Id,
            'in_weight' => 7,
            'line_total' => 800,
            'line_tax' => 120,
            'net_total' => 920,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.daily_carat_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
            ]);

        $response->assertOk();
        $response->assertSee('التقرير اليومي للمبيعات والمشتريات حسب العيار');
        $response->assertSee('2026-03-22');
        $response->assertSee('بيع');
        $response->assertSee('شراء');
        $response->assertSee('عيار 21');
        $response->assertSee('1,150.00');
        $response->assertSee('920.00');
        $response->assertSee('10.000');
        $response->assertSee('7.000');
    }

    public function test_daily_carat_report_respects_branch_and_carat_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);
        $otherBranch = Branch::create([
            'name' => ['ar' => 'فرع آخر', 'en' => 'Other Branch'],
            'phone' => '987654321',
            'status' => true,
        ]);

        [$carat21Id, $carat18Id] = $this->prepareReportDimensions();

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'MATCH-DAY',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '10:00:00',
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

        $excludedInvoiceId = $this->insertInvoice([
            'bill_number' => 'EXCLUDED-DAY',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '11:00:00',
            'branch_id' => $otherBranch->id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $excludedInvoiceId,
            'gold_carat_id' => $carat18Id,
            'out_weight' => 3,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.daily_carat_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'carat_id' => $carat21Id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
            ]);

        $response->assertOk();
        $response->assertSee('2026-03-22');
        $response->assertSee('عيار 21');
        $response->assertDontSee('عيار 18');
        $response->assertSee('575.00');
        $response->assertDontSee('345.00');
        $response->assertDontSee('3.000');
    }

    public function test_daily_carat_report_respects_time_filters_and_displays_selected_time_window(): void
    {
        $admin = $this->createAdminUser([
            'employee.inventory_reports.show',
        ]);

        [$carat21Id] = $this->prepareReportDimensions();

        $morningInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-TIME-1',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '09:15:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $morningInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 2,
            'line_total' => 200,
            'line_tax' => 30,
            'net_total' => 230,
            'date' => '2026-03-22',
        ]);

        $eveningInvoiceId = $this->insertInvoice([
            'bill_number' => 'DAY-TIME-2',
            'type' => 'sale',
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '18:45:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $eveningInvoiceId,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 5,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.daily_carat_report.index', [], false), [
                'branch_id' => $admin->branch_id,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '18:00',
                'to_time' => '19:00',
            ]);

        $response->assertOk();
        $response->assertSee('18:00:00');
        $response->assertSee('19:00:00');
        $response->assertSee('575.00');
        $response->assertSee('5.000');
        $response->assertDontSee('230.00');
        $response->assertDontSee('2.000');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع التقارير اليومية', 'en' => 'Daily Reports Branch'],
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
            'name' => 'Daily Report Admin',
            'email' => 'daily-report-admin-'.uniqid().'@example.com',
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
