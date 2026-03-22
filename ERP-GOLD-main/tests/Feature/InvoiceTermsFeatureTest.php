<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceTermsFeatureTest extends TestCase
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

    public function test_sales_create_page_prefills_default_invoice_terms(): void
    {
        SystemSetting::putValue('default_invoice_terms', "الاستبدال خلال 3 أيام\nمع الفاتورة الأصلية");
        $admin = $this->createAdminUser([
            'employee.simplified_tax_invoices.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('الاستبدال خلال 3 أيام');
        $response->assertSee('مع الفاتورة الأصلية');
    }

    public function test_purchases_create_page_prefills_default_invoice_terms(): void
    {
        SystemSetting::putValue('default_invoice_terms', "الشراء النهائي بعد الفحص\nولا يقبل الإلغاء");
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('الشراء النهائي بعد الفحص');
        $response->assertSee('ولا يقبل الإلغاء');
    }

    public function test_sales_print_page_uses_saved_invoice_terms_snapshot_even_after_setting_changes(): void
    {
        $branch = $this->createBranch('فرع المبيعات', 'sales-branch@example.com', '111111111');
        $user = $this->createUser($branch, 'sales-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'bill_client_name' => 'عميل نقدي',
            'bill_client_phone' => '0555555555',
            'invoice_terms' => "تم حفظ الشروط داخل الفاتورة\nولا تتغير لاحقًا",
        ]);

        SystemSetting::putValue('default_invoice_terms', 'هذا النص الجديد يجب ألا يظهر');

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('تم حفظ الشروط داخل الفاتورة');
        $response->assertSee('ولا تتغير لاحقًا');
        $response->assertDontSee('هذا النص الجديد يجب ألا يظهر');
    }

    public function test_purchases_print_page_uses_saved_invoice_terms_snapshot(): void
    {
        $branch = $this->createBranch('فرع المشتريات', 'purchases-branch@example.com', '222222222');
        $user = $this->createUser($branch, 'purchases-user@example.com');
        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد الاختبار',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->createInvoice($branch, $user, 'purchase', [
            'customer_id' => $supplierId,
            'supplier_bill_number' => 'SUP-1001',
            'invoice_terms' => "يتم اعتماد الوزن بعد الفحص\nوالدفع حسب الحساب البنكي المحدد",
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('purchases.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('يتم اعتماد الوزن بعد الفحص');
        $response->assertSee('والدفع حسب الحساب البنكي المحدد');
        $response->assertSee('print-brand-logo', false);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = $this->createBranch('الفرع الرئيسي', 'main-branch@example.com', '123456789');

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

        $user = $this->createUser($branch, 'admin-invoice-terms@example.com');
        $user->assignRole($role);

        return $user;
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
