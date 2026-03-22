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

class InvoicePartySnapshotFeatureTest extends TestCase
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

    public function test_sales_create_page_exposes_customer_snapshot_fields_and_party_data(): void
    {
        $admin = $this->createAdminUser([
            'employee.simplified_tax_invoices.add',
        ]);
        DB::table('customers')->insert([
            'name' => 'عميل الكاش',
            'phone' => '0500000001',
            'identity_number' => '1000000001',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $response->assertOk();
        $response->assertSee('name="bill_client_name"', false);
        $response->assertSee('name="bill_client_phone"', false);
        $response->assertSee('name="bill_client_identity_number"', false);
        $response->assertSee('quick_save_customer_is_cash_party', false);
        $response->assertSee('data-name="عميل الكاش"', false);
        $response->assertSee('data-phone="0500000001"', false);
        $response->assertSee('data-identity-number="1000000001"', false);
    }

    public function test_purchases_create_page_exposes_supplier_snapshot_fields_and_party_data(): void
    {
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
        ]);
        DB::table('customers')->insert([
            'name' => 'مورد السبائك',
            'phone' => '0551234567',
            'identity_number' => '2000000002',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertSee('name="bill_client_name"', false);
        $response->assertSee('name="bill_client_phone"', false);
        $response->assertSee('name="bill_client_identity_number"', false);
        $response->assertSee('quick_save_supplier_is_cash_party', false);
        $response->assertSee('data-name="مورد السبائك"', false);
        $response->assertSee('data-phone="0551234567"', false);
        $response->assertSee('data-identity-number="2000000002"', false);
    }

    public function test_sales_print_page_uses_saved_customer_snapshot_after_customer_master_changes(): void
    {
        $branch = $this->createBranch('فرع العملاء', 'branch-customers@example.com', '111111111');
        $user = $this->createUser($branch, 'snapshot-sales@example.com');
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'الاسم الأساسي',
            'phone' => '0501111111',
            'identity_number' => '1231231231',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'customer_id' => $customerId,
            'sale_type' => 'simplified',
            'bill_client_name' => 'الاسم المحفوظ في الفاتورة',
            'bill_client_phone' => '0509999999',
            'bill_client_identity_number' => '9999999999',
        ]);

        DB::table('customers')->where('id', $customerId)->update([
            'name' => 'اسم جديد بعد الفاتورة',
            'phone' => '0502222222',
            'identity_number' => '1111111111',
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('الاسم المحفوظ في الفاتورة');
        $response->assertSee('0509999999');
        $response->assertSee('9999999999');
        $response->assertDontSee('اسم جديد بعد الفاتورة');
        $response->assertDontSee('0502222222');
        $response->assertDontSee('1111111111');
    }

    public function test_purchases_print_page_uses_saved_supplier_snapshot_after_supplier_master_changes(): void
    {
        $branch = $this->createBranch('فرع الموردين', 'branch-suppliers@example.com', '222222222');
        $user = $this->createUser($branch, 'snapshot-purchases@example.com');
        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'اسم المورد الأساسي',
            'phone' => '0561111111',
            'identity_number' => '2222333344',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->createInvoice($branch, $user, 'purchase', [
            'customer_id' => $supplierId,
            'supplier_bill_number' => 'SUP-SNAPSHOT-1',
            'bill_client_name' => 'اسم المورد في الفاتورة',
            'bill_client_phone' => '0569999999',
            'bill_client_identity_number' => '8888777766',
        ]);

        DB::table('customers')->where('id', $supplierId)->update([
            'name' => 'اسم مورد جديد',
            'phone' => '0562222222',
            'identity_number' => '0000111122',
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('purchases.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('اسم المورد في الفاتورة');
        $response->assertSee('0569999999');
        $response->assertSee('8888777766');
        $response->assertDontSee('اسم مورد جديد');
        $response->assertDontSee('0562222222');
        $response->assertDontSee('0000111122');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = $this->createBranch('الفرع الرئيسي', 'crm-main-branch@example.com', '123456789');

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

        $user = $this->createUser($branch, 'crm-admin@example.com');
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
