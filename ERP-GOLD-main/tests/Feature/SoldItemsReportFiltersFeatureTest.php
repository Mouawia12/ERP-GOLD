<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class SoldItemsReportFiltersFeatureTest extends TestCase
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

    public function test_sold_items_search_page_exposes_common_filters(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('reports.sold_items_report.index', [], false));

        $response->assertOk();
        $response->assertSee('name="from_time"', false);
        $response->assertSee('name="to_time"', false);
        $response->assertSee('name="invoice_number"', false);
        $response->assertSee('name="user_id"', false);
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="inventory_classification"', false);
    }

    public function test_sold_items_report_respects_user_time_invoice_number_and_item_filters(): void
    {
        $admin = $this->createAdminUser();
        $otherBranch = Branch::create([
            'name' => ['ar' => 'فرع البيع الآخر', 'en' => 'Other Sales Branch'],
            'phone' => '777888999',
            'status' => true,
        ]);

        $otherUser = User::create([
            'name' => 'Other Sales User',
            'email' => 'sold-items-other-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $admin->branch_id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $branchUser = User::create([
            'name' => 'Other Branch Sales User',
            'email' => 'sold-items-branch-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $otherBranch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        [$caratId, $matchingCategoryId, $otherCategoryId, $matchingItemId, $otherItemId, $silverItemId] = $this->prepareInventoryDimensions($admin->branch_id, $otherBranch->id);

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-DUP-001',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '15:20:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingInvoiceId,
            'item_id' => $matchingItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 4.5,
            'date' => '2026-03-22',
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingInvoiceId,
            'item_id' => $otherItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 2.2,
            'date' => '2026-03-22',
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingInvoiceId,
            'item_id' => $silverItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 1.4,
            'date' => '2026-03-22',
        ]);

        $otherUserInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-DUP-001',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '15:25:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $otherUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherUserInvoiceId,
            'item_id' => $matchingItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 6.1,
            'date' => '2026-03-22',
        ]);

        $otherTimeInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-DUP-001',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '14:10:00',
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherTimeInvoiceId,
            'item_id' => $matchingItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 7.7,
            'date' => '2026-03-22',
        ]);

        $otherBranchInvoiceId = $this->insertInvoice([
            'bill_number' => 'SALE-DUP-001',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '15:30:00',
            'branch_id' => $otherBranch->id,
            'user_id' => $branchUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherBranchInvoiceId,
            'item_id' => $matchingItemId,
            'gold_carat_id' => $caratId,
            'out_weight' => 9.4,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.sold_items_report.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '16:00',
                'branch_id' => $admin->branch_id,
                'user_id' => $admin->id,
                'invoice_number' => 'SALE-DUP-001',
                'inventory_classification' => 'gold',
                'carat' => $caratId,
                'category' => $matchingCategoryId,
                'code' => 'ITM-MATCH',
                'name' => 'سوار',
            ]);

        $response->assertOk();
        $response->assertSee('SALE-DUP-001');
        $response->assertSee('15:20:00');
        $response->assertSee('ITM-MATCH');
        $response->assertSee('سوار');
        $response->assertSee('ذهب');
        $response->assertSee('4.5');
        $response->assertSee($admin->name);
        $response->assertSee($admin->branch->name);
        $response->assertDontSee('ITM-SILVER');
        $response->assertDontSee('سوار فضي');
        $response->assertDontSee('ITM-OTHER');
        $response->assertDontSee('خاتم');
        $response->assertDontSee('Other Sales User');
        $response->assertDontSee('Other Branch Sales User');
        $response->assertDontSee('14:10:00');
        $response->assertDontSee('6.1');
        $response->assertDontSee('7.7');
        $response->assertDontSee('9.4');
    }

    private function createAdminUser(): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع تقرير المبيعات', 'en' => 'Sold Items Branch'],
            'phone' => '111333555',
            'status' => true,
        ]);

        return User::create([
            'name' => 'Sold Items Admin',
            'email' => 'sold-items-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function prepareInventoryDimensions(int $branchId, int $otherBranchId): array
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

        $caratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchingCategoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'أساور', 'en' => 'Bracelets'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-1',
            'description' => 'Bracelets category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherCategoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'خواتم', 'en' => 'Rings'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-2',
            'description' => 'Rings category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchingItemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'سوار ذهبي', 'en' => 'Gold Bracelet'], JSON_UNESCAPED_UNICODE),
            'code' => 'ITM-MATCH',
            'description' => null,
            'category_id' => $matchingCategoryId,
            'branch_id' => $branchId,
            'inventory_classification' => 'gold',
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => 1,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 0,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherItemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'خاتم ذهبي', 'en' => 'Gold Ring'], JSON_UNESCAPED_UNICODE),
            'code' => 'ITM-OTHER',
            'description' => null,
            'category_id' => $otherCategoryId,
            'branch_id' => $otherBranchId,
            'inventory_classification' => 'gold',
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => 1,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 0,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $silverItemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'سوار فضي', 'en' => 'Silver Bracelet'], JSON_UNESCAPED_UNICODE),
            'code' => 'ITM-SILVER',
            'description' => null,
            'category_id' => $matchingCategoryId,
            'branch_id' => $branchId,
            'inventory_classification' => 'silver',
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => 1,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 0,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$caratId, $matchingCategoryId, $otherCategoryId, $matchingItemId, $otherItemId, $silverItemId];
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
