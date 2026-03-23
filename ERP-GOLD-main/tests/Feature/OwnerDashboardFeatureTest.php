<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GoldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class OwnerDashboardFeatureTest extends TestCase
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

    public function test_owner_dashboard_uses_business_date_and_shows_owner_metrics(): void
    {
        $branchOne = $this->createBranch('فرع الرياض');
        $branchTwo = $this->createBranch('فرع جدة');

        $owner = $this->createUser($branchOne, 'Owner Admin', true);
        $branchUser = $this->createUser($branchTwo, 'Branch Seller');

        [$carat21Id, $carat18Id] = $this->prepareGoldDimensions();

        GoldPrice::query()->create([
            'ounce_price' => 12345,
            'ounce_14_price' => 7200,
            'ounce_18_price' => 9100,
            'ounce_21_price' => 10600,
            'ounce_22_price' => 11100,
            'ounce_24_price' => 12100,
            'currency' => 'SAR',
            'source' => 'manual',
            'source_currency' => 'SAR',
            'last_update' => '2026-03-22 11:30:00',
        ]);

        $todayCountedSale = $this->insertInvoice([
            'bill_number' => 'DASH-SALE-1',
            'type' => 'sale',
            'branch_id' => $branchOne->id,
            'user_id' => $owner->id,
            'date' => '2026-03-22',
            'time' => '10:00:00',
            'net_total' => 575,
            'created_at' => '2026-03-18 08:00:00',
            'updated_at' => '2026-03-18 08:00:00',
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $todayCountedSale,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 5,
            'line_total' => 500,
            'line_tax' => 75,
            'net_total' => 575,
            'date' => '2026-03-22',
        ]);

        $branchTwoSale = $this->insertInvoice([
            'bill_number' => 'DASH-SALE-2',
            'type' => 'sale',
            'branch_id' => $branchTwo->id,
            'user_id' => $branchUser->id,
            'date' => '2026-03-22',
            'time' => '12:00:00',
            'net_total' => 920,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $branchTwoSale,
            'gold_carat_id' => $carat18Id,
            'out_weight' => 4,
            'line_total' => 800,
            'line_tax' => 120,
            'net_total' => 920,
            'date' => '2026-03-22',
        ]);

        $branchOnePurchase = $this->insertInvoice([
            'bill_number' => 'DASH-PUR-1',
            'type' => 'purchase',
            'branch_id' => $branchOne->id,
            'user_id' => $owner->id,
            'date' => '2026-03-22',
            'time' => '14:00:00',
            'net_total' => 460,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $branchOnePurchase,
            'gold_carat_id' => $carat21Id,
            'in_weight' => 4,
            'line_total' => 400,
            'line_tax' => 60,
            'net_total' => 460,
            'date' => '2026-03-22',
        ]);

        $branchTwoPurchase = $this->insertInvoice([
            'bill_number' => 'DASH-PUR-2',
            'type' => 'purchase',
            'branch_id' => $branchTwo->id,
            'user_id' => $branchUser->id,
            'date' => '2026-03-22',
            'time' => '15:00:00',
            'net_total' => 690,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $branchTwoPurchase,
            'gold_carat_id' => $carat18Id,
            'in_weight' => 6,
            'line_total' => 600,
            'line_tax' => 90,
            'net_total' => 690,
            'date' => '2026-03-22',
        ]);

        $todayReturn = $this->insertInvoice([
            'bill_number' => 'DASH-RET-1',
            'type' => 'sale_return',
            'branch_id' => $branchOne->id,
            'user_id' => $owner->id,
            'date' => '2026-03-22',
            'time' => '16:00:00',
            'net_total' => 115,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $todayReturn,
            'gold_carat_id' => $carat21Id,
            'in_weight' => 1,
            'line_total' => 100,
            'line_tax' => 15,
            'net_total' => 115,
            'date' => '2026-03-22',
        ]);

        $excludedByBusinessDate = $this->insertInvoice([
            'bill_number' => 'DASH-OLD-DATE',
            'type' => 'sale',
            'branch_id' => $branchOne->id,
            'user_id' => $owner->id,
            'date' => '2026-03-21',
            'time' => '18:00:00',
            'net_total' => 9999,
            'created_at' => '2026-03-22 18:00:00',
            'updated_at' => '2026-03-22 18:00:00',
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $excludedByBusinessDate,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 10,
            'line_total' => 8694.78,
            'line_tax' => 1304.22,
            'net_total' => 9999,
            'date' => '2026-03-21',
        ]);

        $response = $this->actingAs($owner, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee('لوحة المالك');
        $response->assertSee('جميع الفروع');
        $response->assertSee('SAR');
        $response->assertSee('تحديث يدوي');
        $response->assertSee('1,495.00');
        $response->assertSee('1,380.00');
        $response->assertSee('9.428 جم', false);
        $response->assertSee('10.642 جم', false);
        $response->assertSee('عيار 21');
        $response->assertSee('عيار 18');
        $response->assertSee('Owner Admin');
        $response->assertSee('Branch Seller');
        $response->assertSee('فرع الرياض');
        $response->assertSee('فرع جدة');
        $response->assertDontSee('9,999.00');
    }

    public function test_branch_user_dashboard_is_scoped_to_current_branch(): void
    {
        $branchOne = $this->createBranch('فرع النطاق');
        $branchTwo = $this->createBranch('فرع مستبعد');

        $branchUser = $this->createUser($branchOne, 'Scoped User');
        $otherUser = $this->createUser($branchTwo, 'Other Branch User');

        [$carat21Id] = $this->prepareGoldDimensions();

        GoldPrice::query()->create([
            'ounce_price' => 11000,
            'ounce_14_price' => 6500,
            'ounce_18_price' => 8200,
            'ounce_21_price' => 9600,
            'ounce_22_price' => 10100,
            'ounce_24_price' => 10800,
            'currency' => 'SAR',
            'source' => 'manual',
            'source_currency' => 'SAR',
            'last_update' => '2026-03-22 09:00:00',
        ]);

        $scopedSale = $this->insertInvoice([
            'bill_number' => 'SCOPED-SALE',
            'type' => 'sale',
            'branch_id' => $branchOne->id,
            'user_id' => $branchUser->id,
            'date' => '2026-03-22',
            'time' => '10:15:00',
            'net_total' => 345,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $scopedSale,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 3,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
            'date' => '2026-03-22',
        ]);

        $excludedSale = $this->insertInvoice([
            'bill_number' => 'OTHER-SALE',
            'type' => 'sale',
            'branch_id' => $branchTwo->id,
            'user_id' => $otherUser->id,
            'date' => '2026-03-22',
            'time' => '11:30:00',
            'net_total' => 920,
        ]);
        $this->insertInvoiceDetail([
            'invoice_id' => $excludedSale,
            'gold_carat_id' => $carat21Id,
            'out_weight' => 5,
            'line_total' => 800,
            'line_tax' => 120,
            'net_total' => 920,
            'date' => '2026-03-22',
        ]);

        $response = $this->actingAs($branchUser, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee('عرض الفرع النشط فقط');
        $response->assertSee('فرع النطاق');
        $response->assertSee('Scoped User');
        $response->assertSee('345.00');
        $response->assertSee('3.000 جم', false);
        $response->assertDontSee('Other Branch User');
        $response->assertDontSee('فرع مستبعد');
        $response->assertDontSee('920.00');
    }

    private function createBranch(string $name): Branch
    {
        return Branch::query()->create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $name, bool $isAdmin = false): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => $isAdmin,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function prepareGoldDimensions(): array
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
            'transform_factor' => '1.107',
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
