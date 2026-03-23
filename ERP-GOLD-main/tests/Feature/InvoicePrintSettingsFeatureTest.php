<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class InvoicePrintSettingsFeatureTest extends TestCase
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

    public function test_sales_print_page_uses_a5_without_header_or_footer_when_settings_disabled(): void
    {
        $branch = $this->createBranch('فرع البيع', 'sales-print-settings@example.com', '111111111');
        $user = $this->createUser($branch, 'sales-print-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'bill_client_name' => 'عميل اختبار',
            'bill_client_phone' => '0550000000',
        ]);

        DB::table('system_settings')->insert([
            ['key' => 'invoice_print_format', 'value' => 'a5'],
            ['key' => 'invoice_print_template', 'value' => 'compact'],
            ['key' => 'invoice_print_show_header', 'value' => '0'],
            ['key' => 'invoice_print_show_footer', 'value' => '0'],
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('data-print-format="a5"', false);
        $response->assertSee('data-print-template="compact"', false);
        $response->assertSee('data-show-header="0"', false);
        $response->assertSee('data-show-footer="0"', false);
        $response->assertDontSee('<header class="print-header-section"', false);
        $response->assertDontSee('<div class="row print-footer-section"', false);
    }

    public function test_purchases_print_page_uses_a4_with_header_and_footer_when_enabled(): void
    {
        $branch = $this->createBranch('فرع الشراء', 'purchase-print-settings@example.com', '222222222');
        $user = $this->createUser($branch, 'purchase-print-user@example.com');
        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد الطباعة',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->createInvoice($branch, $user, 'purchase', [
            'customer_id' => $supplierId,
            'supplier_bill_number' => 'SUP-PRINT-01',
        ]);

        DB::table('system_settings')->insert([
            ['key' => 'invoice_print_format', 'value' => 'a4'],
            ['key' => 'invoice_print_template', 'value' => 'modern'],
            ['key' => 'invoice_print_show_header', 'value' => '1'],
            ['key' => 'invoice_print_show_footer', 'value' => '1'],
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('purchases.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('data-print-format="a4"', false);
        $response->assertSee('data-print-template="modern"', false);
        $response->assertSee('data-show-header="1"', false);
        $response->assertSee('data-show-footer="1"', false);
        $response->assertSee('<header class="print-header-section"', false);
        $response->assertSee('<div class="row print-footer-section"', false);
    }

    private function createBranch(string $name, string $email, string $phone): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => $email,
            'phone' => $phone,
            'tax_number' => str_pad((string) random_int(1, 999999999999999), 15, '0', STR_PAD_LEFT),
            'commercial_register' => str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT),
            'short_address' => 'الرياض',
            'region' => 'الرياض',
            'city' => 'الرياض',
            'district' => 'الملز',
            'street_name' => 'الشارع الرئيسي',
            'building_number' => '1234',
            'plot_identification' => '5678',
            'country' => 'SA',
            'postal_code' => '12345',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createInvoice(Branch $branch, User $user, string $type, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'type' => $type,
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ], $attributes));
    }
}
