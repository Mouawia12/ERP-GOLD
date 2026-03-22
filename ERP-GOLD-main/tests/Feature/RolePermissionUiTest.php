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
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
