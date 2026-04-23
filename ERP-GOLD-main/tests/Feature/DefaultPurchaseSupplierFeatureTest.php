<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Purchases\DefaultPurchaseSupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DefaultPurchaseSupplierFeatureTest extends TestCase
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

    public function test_authorized_admin_can_set_default_purchase_supplier(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);
        $supplierId = $this->createSupplier('مورد الإعداد الافتراضي');

        $pageResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.system-settings.default-purchase-supplier.edit', [], false));

        $pageResponse->assertOk();
        $pageResponse->assertSee('المورد الافتراضي للمشتريات');
        $pageResponse->assertSee('مورد الإعداد الافتراضي');

        $updateResponse = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.default-purchase-supplier.update', [], false), [
                'default_supplier_id' => $supplierId,
            ]);

        $updateResponse->assertRedirect(route('admin.system-settings.default-purchase-supplier.edit', [], false));
        $this->assertDatabaseHas('system_settings', [
            'key' => DefaultPurchaseSupplierService::SETTING_KEY,
            'value' => (string) $supplierId,
        ]);
    }

    public function test_purchases_create_page_selects_configured_default_supplier(): void
    {
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
        ]);
        $supplierId = $this->createSupplier('مورد يظهر تلقائيًا');

        SystemSetting::putValue(DefaultPurchaseSupplierService::SETTING_KEY, (string) $supplierId);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertSee('data-default-supplier-id="' . $supplierId . '"', false);
        $response->assertSee('data-default-supplier="1"', false);
        $response->assertSee('مورد يظهر تلقائيًا');
    }

    public function test_unauthorized_admin_cannot_update_default_purchase_supplier(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
        ]);
        $supplierId = $this->createSupplier('مورد غير مصرح');

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.default-purchase-supplier.update', [], false), [
                'default_supplier_id' => $supplierId,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('system_settings', [
            'key' => DefaultPurchaseSupplierService::SETTING_KEY,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع المشتريات', 'en' => 'Purchases Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير المشتريات', 'en' => 'Purchases Admin'],
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
            'name' => 'Purchases Admin',
            'email' => 'default-supplier-admin-' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createSupplier(string $name): int
    {
        return (int) DB::table('customers')->insertGetId([
            'name' => $name,
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
