<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionUiTest extends TestCase
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

    public function test_role_create_page_shows_grouped_searchable_permissions(): void
    {
        $admin = $this->createAdminUser([
            'employee.user_permissions.add',
        ]);

        $response = $this->actingAs($admin, 'admin-web')->get(route('admin.roles.create', [], false));

        $response->assertOk();
        $response->assertSee('ابحث باسم الموديول أو الصلاحية');
        $response->assertSee('الادارة');
        $response->assertSee('المبيعات');
        $response->assertSee('employee.users.add');
    }

    public function test_authorized_admin_can_update_role_permissions_from_grouped_screen(): void
    {
        $admin = $this->createAdminUser([
            'employee.user_permissions.edit',
        ]);
        $role = Role::create([
            'name' => ['ar' => 'مدير مبيعات', 'en' => 'Sales Manager'],
            'guard_name' => 'admin-web',
        ]);

        $usersShow = Permission::create([
            'name' => 'employee.users.show',
            'guard_name' => 'admin-web',
        ]);
        $usersEdit = Permission::create([
            'name' => 'employee.users.edit',
            'guard_name' => 'admin-web',
        ]);
        $branchesShow = Permission::create([
            'name' => 'employee.branches.show',
            'guard_name' => 'admin-web',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.roles.update', $role->id, false), [
                'name' => 'مدير مبيعات',
                'permission' => [
                    $usersShow->name,
                    $usersEdit->name,
                ],
            ]);

        $response->assertRedirect(route('admin.roles.index', [], false));
        $this->assertTrue($role->fresh()->hasPermissionTo($usersShow));
        $this->assertTrue($role->fresh()->hasPermissionTo($usersEdit));
        $this->assertFalse($role->fresh()->hasPermissionTo($branchesShow));
    }

    public function test_role_create_validation_errors_are_shown_in_clear_arabic_messages(): void
    {
        $admin = $this->createAdminUser([
            'employee.user_permissions.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.roles.create', [], false))
            ->post(route('admin.roles.store', [], false), [
                'name' => '',
                'guard_name' => 'admin-web',
                'permission' => [],
            ]);

        $response->assertRedirect(route('admin.roles.create', [], false));
        $response->assertSessionHasErrors([
            'name' => 'يرجى إدخال اسم واضح لمجموعة الصلاحيات.',
            'permission' => 'حدد صلاحية واحدة على الأقل داخل المجموعة قبل الحفظ.',
        ]);
    }

    public function test_role_name_validation_detects_duplicate_translated_role_names(): void
    {
        $admin = $this->createAdminUser([
            'employee.user_permissions.add',
        ]);

        Role::create([
            'name' => ['ar' => 'مدير المخزون', 'en' => 'Inventory Manager'],
            'guard_name' => 'admin-web',
        ]);

        $permission = Permission::create([
            'name' => 'employee.users.show',
            'guard_name' => 'admin-web',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.roles.create', [], false))
            ->post(route('admin.roles.store', [], false), [
                'name' => 'مدير المخزون',
                'guard_name' => 'admin-web',
                'permission' => [$permission->name],
            ]);

        $response->assertRedirect(route('admin.roles.create', [], false));
        $response->assertSessionHasErrors([
            'name' => 'اسم مجموعة الصلاحيات مستخدم بالفعل. اختر اسمًا آخر.',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '123456789',
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
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'is_admin' => true,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
