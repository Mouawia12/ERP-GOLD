<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserDirectPermissionsFeatureTest extends TestCase
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

    public function test_user_create_and_edit_pages_show_direct_permissions_matrix(): void
    {
        Permission::findOrCreate('employee.users.show', 'admin-web');
        $admin = $this->createAdminUser([
            'employee.users.add',
            'employee.users.edit',
        ]);

        $managedRole = $this->createRole('موظف المبيعات', 'Sales Employee');
        $managedUser = $this->createManagedUser($managedRole);

        $createResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.create', [], false));

        $createResponse->assertOk();
        $createResponse->assertSee('صلاحيات مباشرة للمستخدم');
        $createResponse->assertSee('user-direct-permissions-search', false);
        $createResponse->assertSee('direct_permissions[]', false);
        $createResponse->assertSee('employee.users.show');

        $editResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.edit', $managedUser->id, false));

        $editResponse->assertOk();
        $editResponse->assertSee('صلاحيات مباشرة للمستخدم');
        $editResponse->assertSee('user-direct-permissions-check-all', false);
        $editResponse->assertSee('employee.users.show');
    }

    public function test_admin_can_assign_direct_permissions_to_user_and_show_page_displays_effective_permissions(): void
    {
        $directPermission = Permission::findOrCreate('employee.branches.show', 'admin-web');
        $rolePermission = Permission::findOrCreate('employee.users.show', 'admin-web');
        $admin = $this->createAdminUser([
            'employee.users.add',
            'employee.users.show',
        ]);
        $targetBranch = $this->createBranch('فرع العمليات', 'Operations Branch', 'operations@example.com');
        $managedRole = $this->createRole('مشرف العمليات', 'Operations Supervisor');
        $managedRole->givePermissionTo($rolePermission);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('admin.users.store', [], false), [
                'name' => 'Direct Permission User',
                'email' => 'direct-permission-user@example.com',
                'role_id' => $managedRole->id,
                'branch_id' => $targetBranch->id,
                'branch_ids' => [$targetBranch->id],
                'password' => 'secret123',
                'confirm-password' => 'secret123',
                'direct_permissions' => [
                    $directPermission->name,
                ],
            ]);

        $response->assertRedirect(route('admin.users.index', [], false));
        $response->assertSessionHasNoErrors();

        $managedUser = User::query()->where('email', 'direct-permission-user@example.com')->firstOrFail();

        $this->assertTrue($managedUser->hasRole($managedRole));
        $this->assertTrue($managedUser->hasDirectPermission($directPermission));
        $this->assertEqualsCanonicalizing(
            [
                'employee.branches.show',
                'employee.users.show',
            ],
            $managedUser->getAllPermissions()->pluck('name')->all(),
        );

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.show', $managedUser->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('الصلاحيات المباشرة');
        $showResponse->assertSee('صلاحيات الدور');
        $showResponse->assertSee('الصلاحيات الفعلية');
        $showResponse->assertSee('employee.branches.show');
        $showResponse->assertSee('employee.users.show');
    }

    public function test_updating_direct_permissions_syncs_user_permissions_and_writes_audit_log(): void
    {
        $oldDirectPermission = Permission::findOrCreate('employee.branches.show', 'admin-web');
        $newDirectPermission = Permission::findOrCreate('employee.accounts.show', 'admin-web');
        $admin = $this->createAdminUser([
            'employee.users.edit',
            'employee.users.show',
        ]);
        $managedRole = $this->createRole('موظف التشغيل', 'Operations Employee');
        $managedUser = $this->createManagedUser($managedRole, [
            $oldDirectPermission->name,
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.users.update', $managedUser->id, false), [
                'name' => $managedUser->name,
                'email' => $managedUser->email,
                'role_id' => $managedRole->id,
                'branch_id' => $managedUser->branch_id,
                'branch_ids' => [$managedUser->branch_id],
                'status' => 1,
                'direct_permissions' => [
                    $newDirectPermission->name,
                ],
            ]);

        $response->assertRedirect(route('admin.users.index', [], false));
        $response->assertSessionHasNoErrors();

        $managedUser = $managedUser->fresh();

        $this->assertFalse($managedUser->hasDirectPermission($oldDirectPermission));
        $this->assertTrue($managedUser->hasDirectPermission($newDirectPermission));

        $auditLog = UserAuditLog::query()
            ->where('target_user_id', $managedUser->id)
            ->where('event_key', 'direct_permissions_changed')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame([$oldDirectPermission->name], $auditLog->old_values['permissions']);
        $this->assertSame([$newDirectPermission->name], $auditLog->new_values['permissions']);

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.show', $managedUser->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('تغيير الصلاحيات المباشرة');
        $showResponse->assertSee('employee.accounts.show');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = $this->createBranch('الفرع الرئيسي', 'Main Branch', 'main-branch@example.com');
        $role = $this->createRole('مدير النظام', 'System Admin');

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'is_admin' => false,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<int, string>  $directPermissions
     */
    private function createManagedUser(Role $role, array $directPermissions = []): User
    {
        $branch = $this->createBranch('فرع الموظفين', 'Employees Branch', 'employees-branch@example.com');

        $user = User::create([
            'name' => 'Managed User',
            'email' => 'managed-user-direct@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        if ($directPermissions !== []) {
            $user->syncPermissions(
                collect($directPermissions)
                    ->map(fn (string $permissionName) => Permission::findOrCreate($permissionName, 'admin-web'))
                    ->all(),
            );
        }

        return $user;
    }

    private function createBranch(string $arabicName, string $englishName, string $email): Branch
    {
        return Branch::create([
            'name' => ['ar' => $arabicName, 'en' => $englishName],
            'email' => $email,
            'phone' => '555555555',
        ]);
    }

    private function createRole(string $arabicName, string $englishName): Role
    {
        return Role::create([
            'name' => ['ar' => $arabicName, 'en' => $englishName],
            'guard_name' => 'admin-web',
        ]);
    }
}
