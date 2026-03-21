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

class AdminAccessTest extends TestCase
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

    public function test_admin_can_log_in_and_open_dashboard(): void
    {
        $user = $this->createAdminUser();

        $response = $this->post(route('admin.login', [], false), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user, 'admin-web');
    }

    public function test_dashboard_renders_without_missing_profile_route_errors(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee($user->name);
    }

    public function test_profile_edit_route_is_available_for_authenticated_admin(): void
    {
        $user = $this->createAdminUser([
            'employee.users.edit',
        ]);

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.profile.edit', $user->id, false));

        $response->assertOk();
        $response->assertSee('تعديل بيانات المستخدم');
    }

    public function test_authenticated_admin_can_create_a_user(): void
    {
        $admin = $this->createAdminUser();
        $targetBranch = Branch::create([
            'name' => ['ar' => 'فرع جديد', 'en' => 'New Branch'],
            'email' => 'branch@example.com',
            'phone' => '111111111',
        ]);
        $role = Role::create([
            'name' => ['ar' => 'موظف', 'en' => 'Employee'],
            'guard_name' => 'admin-web',
        ]);

        $response = $this->actingAs($admin, 'admin-web')->post(route('admin.users.store', [], false), [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'role_id' => $role->id,
            'branch_id' => $targetBranch->id,
            'password' => 'secret123',
            'confirm-password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'branch_id' => $targetBranch->id,
        ]);
        $this->assertTrue(User::where('email', 'user@example.com')->first()->hasRole($role));
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
