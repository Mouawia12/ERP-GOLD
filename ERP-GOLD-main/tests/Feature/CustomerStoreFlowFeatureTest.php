<?php

namespace Tests\Feature;

use App\Models\Account;
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

class CustomerStoreFlowFeatureTest extends TestCase
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

    public function test_customer_store_creates_customer_with_linked_account_when_data_is_valid(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
            'employee.customers.add',
        ]);

        [$clientsAccountId] = $this->prepareCustomerAccounting($admin->branch);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'customer'], false), [
                'name' => 'عميل إضافة صحيحة',
                'phone' => '0551234567',
                'identity_number' => '1010101010',
                'email' => 'customer-store@example.com',
                'type' => 'customer',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
        ]);

        $customer = Customer::query()->where('name', 'عميل إضافة صحيحة')->firstOrFail();

        $this->assertNotNull($customer->account_id);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'type' => 'customer',
            'identity_number' => '1010101010',
            'email' => 'customer-store@example.com',
        ]);

        $customerAccount = Account::withoutGlobalScopes()->findOrFail($customer->account_id);

        $this->assertSame($clientsAccountId, (int) $customerAccount->parent_account_id);
    }

    public function test_customer_store_treats_zero_id_as_create_not_edit(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.show',
            'employee.customers.add',
        ]);

        $this->prepareCustomerAccounting($admin->branch);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'customer'], false), [
                'id' => '0',
                'name' => 'عميل من مودال الإضافة',
                'phone' => '0554444444',
                'email' => 'modal-create@example.com',
                'type' => 'customer',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'عميل من مودال الإضافة',
            'email' => 'modal-create@example.com',
            'type' => 'customer',
        ]);
    }

    public function test_customer_store_returns_clear_arabic_validation_errors(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'customer'], false), [
                'name' => '',
                'email' => 'not-an-email',
                'vat_no' => 'VAT-1',
                'type' => 'customer',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'تعذر حفظ البيانات. يرجى مراجعة الحقول المطلوبة.');
        $response->assertJsonPath('field_errors.name.0', 'اسم العميل مطلوب');
        $response->assertJsonPath('field_errors.email.0', 'يجب إدخال البريد الإلكتروني بصيغة بريد إلكتروني صحيحة.');
        $response->assertJsonPath('field_errors.region.0', 'المنطقة مطلوبة في حالة وجود قيمه ل الرقم الضريبي');
        $response->assertJsonMissing([
            'validation.required',
        ]);
    }

    public function test_customer_store_requires_accounting_setup_before_creation(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.store', ['type' => 'customer'], false), [
                'name' => 'عميل بلا ربط',
                'phone' => '0559999999',
                'type' => 'customer',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'لا يمكن إضافة العميل قبل ضبط الروابط المحاسبية للفرع الحالي.',
        ]);

        $this->assertDatabaseMissing('customers', [
            'name' => 'عميل بلا ربط',
        ]);
    }

    public function test_customer_quick_store_requires_accounting_setup_before_creating_new_customer(): void
    {
        $admin = $this->createAdminUser([
            'employee.customers.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('customers.quick-store', ['type' => 'customer'], false), [
                'name' => 'عميل سريع بلا ربط',
                'phone' => '0557777777',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'لا يمكن إضافة العميل قبل ضبط الروابط المحاسبية للفرع الحالي.',
        ]);

        $this->assertDatabaseMissing('customers', [
            'name' => 'عميل سريع بلا ربط',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع العملاء', 'en' => 'Customers Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير العملاء', 'en' => 'Customers Admin'],
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
            'name' => 'Customers Admin',
            'email' => 'customer-store-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function prepareCustomerAccounting(Branch $branch): array
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

        return [$clientsAccountId, $suppliersAccountId];
    }
}
