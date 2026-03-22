<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
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

class QuickPartyDirectoryFeatureTest extends TestCase
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

    public function test_customer_quick_store_creates_reusable_customer_and_returns_payload(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'عميل الفاتورة السريع',
                'phone' => '0551000000',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'created' => true,
            'customer_name' => 'عميل الفاتورة السريع',
            'phone' => '0551000000',
            'type' => 'customer',
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'عميل الفاتورة السريع',
            'phone' => '0551000000',
            'type' => 'customer',
        ]);
    }

    public function test_customer_quick_store_persists_identity_and_cash_party_and_reuses_existing_customer_by_identity(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $createResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'عميل هوية سريع',
                'phone' => '',
                'identity_number' => '1002003004',
                'is_cash_party' => 1,
            ]);

        $createResponse->assertOk();
        $createResponse->assertJson([
            'status' => true,
            'created' => true,
            'customer_name' => 'عميل هوية سريع',
            'identity_number' => '1002003004',
            'is_cash_party' => true,
        ]);

        $customerId = Customer::query()
            ->where('type', 'customer')
            ->where('identity_number', '1002003004')
            ->value('id');

        $reuseResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'اسم آخر لنفس الهوية',
                'phone' => '0551777888',
                'identity_number' => '1002003004',
            ]);

        $reuseResponse->assertOk();
        $reuseResponse->assertJson([
            'status' => true,
            'created' => false,
            'customer_id' => $customerId,
            'identity_number' => '1002003004',
            'phone' => '0551777888',
            'is_cash_party' => true,
        ]);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'phone' => '0551777888',
            'identity_number' => '1002003004',
            'is_cash_party' => true,
        ]);
    }

    public function test_customer_quick_store_reuses_existing_customer_by_phone(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $existingCustomer = Customer::create([
            'name' => 'العميل الأصلي',
            'phone' => '0551222333',
            'type' => 'customer',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'اسم مختلف لنفس الهاتف',
                'phone' => '0551222333',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'created' => false,
            'customer_id' => $existingCustomer->id,
            'customer_name' => 'العميل الأصلي',
            'phone' => '0551222333',
        ]);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_supplier_quick_store_creates_reusable_supplier_and_returns_payload(): void
    {
        $admin = $this->createAdminUser([
            'employee.suppliers.add',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'supplier'], false), [
                'name' => 'مورد سريع',
                'phone' => '0562000000',
                'identity_number' => '5566778899',
                'is_cash_party' => 1,
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'created' => true,
            'customer_name' => 'مورد سريع',
            'phone' => '0562000000',
            'identity_number' => '5566778899',
            'type' => 'supplier',
            'is_cash_party' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'مورد سريع',
            'phone' => '0562000000',
            'identity_number' => '5566778899',
            'type' => 'supplier',
            'is_cash_party' => true,
        ]);
    }

    public function test_unauthorized_admin_cannot_quick_store_customer(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'عميل غير مصرح',
                'phone' => '0551999999',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('customers', [
            'name' => 'عميل غير مصرح',
            'phone' => '0551999999',
            'type' => 'customer',
        ]);
    }

    public function test_sales_create_page_shows_customer_quick_save_button_for_authorized_user(): void
    {
        $admin = $this->createAdminUser([
            'employee.simplified_tax_invoices.add',
            'employee.customers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $response->assertOk();
        $response->assertSee('quick_save_customer_btn', false);
        $response->assertSee('customers/quick-store/customer', false);
    }

    public function test_purchases_create_page_shows_supplier_quick_save_button_for_authorized_user(): void
    {
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
            'employee.suppliers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertSee('quick_save_supplier_btn', false);
        $response->assertSee('customers/quick-store/supplier', false);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
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
            'name' => 'Admin User',
            'email' => 'quick-party-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function prepareCustomerAccounting(Branch $branch): void
    {
        $clientsAccountId = DB::table('accounts')->insertGetId([
            'name' => 'Customers Root',
            'code' => '1',
            'level' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suppliersAccountId = DB::table('accounts')->insertGetId([
            'name' => 'Suppliers Root',
            'code' => '2',
            'level' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_settings')->insert([
            'clients_account' => $clientsAccountId,
            'suppliers_account' => $suppliersAccountId,
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
