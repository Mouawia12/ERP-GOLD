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

class CashPartyFeatureTest extends TestCase
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

    public function test_customer_store_can_mark_party_as_cash_and_edit_returns_flag(): void
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
                'name' => 'عميل نقدي',
                'phone' => '0553333333',
                'type' => 'customer',
                'is_cash_party' => '1',
            ]);

        $storeResponse->assertOk();
        $storeResponse->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'عميل نقدي',
            'type' => 'customer',
            'is_cash_party' => true,
        ]);

        $customerId = DB::table('customers')->where('name', 'عميل نقدي')->value('id');

        $editResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.get', ['id' => $customerId], false));

        $editResponse->assertOk();
        $editResponse->assertJson([
            'id' => $customerId,
            'is_cash_party' => true,
        ]);
    }

    public function test_customers_index_can_filter_cash_parties_only(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'عميل نقدي ظاهر',
                'phone' => '0554000000',
                'type' => 'customer',
                'is_cash_party' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'عميل عادي مخفي',
                'phone' => '0554111111',
                'type' => 'customer',
                'is_cash_party' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers', ['type' => 'customer', 'cash_only' => 1], false));

        $response->assertOk();
        $response->assertSee('العملاء النقديون');
        $response->assertSee('عميل نقدي ظاهر');
        $response->assertDontSee('عميل عادي مخفي');
        $response->assertSee('نقدي');
    }

    public function test_cash_directory_route_shows_only_cash_customers_with_dedicated_heading(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'عميل نقدي في الدليل',
                'phone' => '0554222222',
                'type' => 'customer',
                'is_cash_party' => true,
                'identity_number' => 'CID-100',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'عميل عادي خارج الدليل',
                'phone' => '0554333333',
                'type' => 'customer',
                'is_cash_party' => false,
                'identity_number' => 'CID-200',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.cash', ['type' => 'customer'], false));

        $response->assertOk();
        $response->assertSee('العملاء النقديون');
        $response->assertSee('هذه القائمة تعرض الأطراف النقدية فقط.');
        $response->assertSee('عميل نقدي في الدليل');
        $response->assertDontSee('عميل عادي خارج الدليل');
    }

    public function test_cash_directory_route_shows_only_cash_suppliers_and_supports_identity_search(): void
    {
        $admin = $this->createAdminUser([
            'employee.suppliers.show',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'مورد نقدي أول',
                'phone' => '0565222222',
                'type' => 'supplier',
                'is_cash_party' => true,
                'identity_number' => 'SUP-100',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مورد نقدي آخر',
                'phone' => '0565333333',
                'type' => 'supplier',
                'is_cash_party' => true,
                'identity_number' => 'SUP-200',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مورد عادي',
                'phone' => '0565444444',
                'type' => 'supplier',
                'is_cash_party' => false,
                'identity_number' => 'SUP-300',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.cash', ['type' => 'supplier', 'identity_number' => 'SUP-200'], false));

        $response->assertOk();
        $response->assertSee('الموردون النقديون');
        $response->assertSee('مورد نقدي آخر');
        $response->assertDontSee('مورد نقدي أول');
        $response->assertDontSee('مورد عادي');
    }

    public function test_cash_supplier_created_from_cash_directory_is_saved_in_cash_directory_only(): void
    {
        $admin = $this->createAdminUser([
            'employee.suppliers.show',
            'employee.suppliers.add',
        ]);
        $this->prepareCustomerAccounting($admin->branch);

        $storeResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'supplier'], false), [
                'name' => 'مورد نقدي من صفحة النقديين',
                'phone' => '0565666666',
                'type' => 'supplier',
                'force_cash_party' => '1',
            ]);

        $storeResponse->assertOk();
        $storeResponse->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'مورد نقدي من صفحة النقديين',
            'type' => 'supplier',
            'is_cash_party' => true,
        ]);

        $cashDirectory = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.cash', ['type' => 'supplier'], false));

        $regularDirectory = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers', ['type' => 'supplier'], false));

        $cashDirectory->assertOk();
        $cashDirectory->assertSee('<td class="text-center">مورد نقدي من صفحة النقديين</td>', false);

        $regularDirectory->assertOk();
        $regularDirectory->assertDontSee('<td class="text-center">مورد نقدي من صفحة النقديين</td>', false);
    }

    public function test_cash_directory_create_form_forces_cash_party_classification(): void
    {
        $admin = $this->createAdminUser([
            'employee.suppliers.show',
            'employee.suppliers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.cash', ['type' => 'supplier'], false));

        $response->assertOk();
        $response->assertSee('name="force_cash_party"', false);
        $response->assertSee('هذه الصفحة تحفظ الطرف كنقدي تلقائيًا ليظهر في جدوله الصحيح.');
    }

    public function test_sales_create_page_exposes_cash_party_filter_and_marks_customer_options(): void
    {
        $admin = $this->createAdminUser([
            'employee.simplified_tax_invoices.add',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'عميل نقدي للمبيعات',
                'phone' => '0555000000',
                'type' => 'customer',
                'is_cash_party' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'عميل عادي للمبيعات',
                'phone' => '0555111111',
                'type' => 'customer',
                'is_cash_party' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $response->assertOk();
        $response->assertSee('cash_party_only_toggle', false);
        $response->assertSee('عرض العملاء النقديين فقط');
        $response->assertSee('data-cash-party="1"', false);
        $response->assertSee('data-cash-party="0"', false);
    }

    public function test_purchases_create_page_exposes_cash_party_filter_and_marks_supplier_options(): void
    {
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'مورد نقدي للمشتريات',
                'phone' => '0565000000',
                'type' => 'supplier',
                'is_cash_party' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مورد عادي للمشتريات',
                'phone' => '0565111111',
                'type' => 'supplier',
                'is_cash_party' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertSee('cash_supplier_only_toggle', false);
        $response->assertSee('عرض الموردين النقديين فقط');
        $response->assertSee('data-cash-party="1"', false);
        $response->assertSee('data-cash-party="0"', false);
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
            'email' => 'cash-party-admin-'.uniqid().'@example.com',
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
