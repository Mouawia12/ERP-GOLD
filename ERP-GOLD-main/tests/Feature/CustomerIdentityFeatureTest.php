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

class CustomerIdentityFeatureTest extends TestCase
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

    public function test_customer_store_persists_identity_number_and_edit_endpoint_returns_it(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
            'employee.customers.add',
            'employee.customers.edit',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $storeResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'customer'], false), [
                'name' => 'عميل الهوية',
                'phone' => '0501234567',
                'identity_number' => '1234567890',
                'email' => 'identity@example.com',
                'type' => 'customer',
            ]);

        $storeResponse->assertOk();
        $storeResponse->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'عميل الهوية',
            'identity_number' => '1234567890',
        ]);

        $customerId = DB::table('customers')->where('name', 'عميل الهوية')->value('id');

        $editResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.get', ['id' => $customerId], false));

        $editResponse->assertOk();
        $editResponse->assertJson([
            'id' => $customerId,
            'identity_number' => '1234567890',
        ]);
    }

    public function test_customers_index_displays_identity_number_column_and_value(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        DB::table('customers')->insert([
            'name' => 'عميل العرض',
            'phone' => '0550000000',
            'identity_number' => '9988776655',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers', ['type' => 'customer'], false));

        $response->assertOk();
        $response->assertSee('رقم الهوية');
        $response->assertSee('9988776655');
    }

    public function test_customers_index_can_filter_by_identity_number(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'العميل المطابق',
                'phone' => '0551111111',
                'identity_number' => '111122223333',
                'type' => 'customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'عميل آخر',
                'phone' => '0552222222',
                'identity_number' => '999988887777',
                'type' => 'customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers', ['type' => 'customer', 'identity_number' => '11112222'], false));

        $response->assertOk();
        $response->assertSee('العميل المطابق');
        $response->assertSee('111122223333');
        $response->assertDontSee('عميل آخر');
        $response->assertDontSee('999988887777');
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
            'email' => 'customer-identity-admin@example.com',
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
