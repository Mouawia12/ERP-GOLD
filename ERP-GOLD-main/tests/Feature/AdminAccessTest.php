<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserAuditLog;
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

    public function test_inactive_admin_cannot_log_in(): void
    {
        $user = $this->createAdminUser();
        $user->update(['status' => false]);

        $response = $this->post(route('admin.login', [], false), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin-web');
    }

    public function test_login_page_uses_updated_brand_logo_markup(): void
    {
        $response = $this->get(route('admin.login', [], false));

        $response->assertOk();
        $response->assertSee('brand-login-logo', false);
    }

    public function test_dashboard_renders_without_missing_profile_route_errors(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee('app-sidebar__brand-logo', false);
        $response->assertSee('brand-header-logo', false);
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
        $admin = $this->createAdminUser([
            'employee.users.add',
        ]);
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

    public function test_authenticated_admin_cannot_create_a_user_without_permission(): void
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
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'role_id' => $role->id,
            'branch_id' => $targetBranch->id,
            'password' => 'secret123',
            'confirm-password' => 'secret123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.com',
        ]);
    }

    public function test_updating_user_writes_audit_logs_and_show_page_displays_them(): void
    {
        $admin = $this->createAdminUser([
            'employee.users.edit',
            'employee.users.show',
        ]);

        $oldBranch = Branch::create([
            'name' => ['ar' => 'فرع المستخدم القديم', 'en' => 'Old User Branch'],
            'email' => 'old-branch@example.com',
            'phone' => '111111112',
        ]);
        $newBranch = Branch::create([
            'name' => ['ar' => 'فرع المستخدم الجديد', 'en' => 'New User Branch'],
            'email' => 'new-branch@example.com',
            'phone' => '111111113',
        ]);

        $oldRole = Role::create([
            'name' => ['ar' => 'موظف قديم', 'en' => 'Old Employee'],
            'guard_name' => 'admin-web',
        ]);
        $newRole = Role::create([
            'name' => ['ar' => 'موظف جديد', 'en' => 'New Employee'],
            'guard_name' => 'admin-web',
        ]);

        $targetUser = User::create([
            'name' => 'Managed User',
            'email' => 'managed-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $oldBranch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
        $targetUser->assignRole($oldRole);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.users.update', $targetUser->id, false), [
                'name' => 'Managed User Updated',
                'email' => 'managed-user@example.com',
                'role_id' => $newRole->id,
                'branch_id' => $newBranch->id,
                'status' => '0',
                'password' => 'new-secret-123',
                'confirm-password' => 'new-secret-123',
            ]);

        $response->assertRedirect(route('admin.users.index', [], false));
        $response->assertSessionHasNoErrors();

        $targetUser = $targetUser->fresh();
        $this->assertSame('Managed User Updated', $targetUser->name);
        $this->assertFalse((bool) $targetUser->status);
        $this->assertSame($newBranch->id, $targetUser->branch_id);
        $this->assertTrue($targetUser->hasRole($newRole));
        $this->assertTrue(Hash::check('new-secret-123', $targetUser->password));

        $auditLogs = UserAuditLog::query()
            ->where('target_user_id', $targetUser->id)
            ->orderBy('event_key')
            ->get();

        $this->assertCount(4, $auditLogs);
        $this->assertEqualsCanonicalizing(
            ['branch_changed', 'password_changed', 'role_changed', 'status_changed'],
            $auditLogs->pluck('event_key')->all()
        );
        $this->assertTrue($auditLogs->every(fn (UserAuditLog $log) => $log->actor_user_id === $admin->id));

        $statusLog = $auditLogs->firstWhere('event_key', 'status_changed');
        $this->assertSame(true, $statusLog->old_values['status']);
        $this->assertSame(false, $statusLog->new_values['status']);

        $showResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.show', $targetUser->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('سجل التعديلات على المستخدم');
        $showResponse->assertSee('تغيير حالة المستخدم');
        $showResponse->assertSee('تغيير كلمة المرور');
        $showResponse->assertSee('تغيير الفرع');
        $showResponse->assertSee('تغيير الصلاحية');
        $showResponse->assertSee('فرع المستخدم القديم');
        $showResponse->assertSee('فرع المستخدم الجديد');
    }

    public function test_branch_details_show_linked_users(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.show',
        ]);
        $branch = Branch::create([
            'name' => ['ar' => 'فرع المبيعات', 'en' => 'Sales Branch'],
            'email' => 'sales@example.com',
            'phone' => '987654321',
            'tax_number' => '123456789012345',
            'short_address' => 'الرياض',
        ]);

        User::create([
            'name' => 'Branch User One',
            'email' => 'branch-user-1@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        User::create([
            'name' => 'Branch User Two',
            'email' => 'branch-user-2@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => false,
            'profile_pic' => 'default.png',
        ]);

        $response = $this->actingAs($admin, 'admin-web')->get(route('admin.branches.show', $branch->id, false));

        $response->assertOk();
        $response->assertSee('عرض بيانات الفرع');
        $response->assertSee('Branch User One');
        $response->assertSee('Branch User Two');
    }

    public function test_authorized_admin_can_update_login_mode_setting(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.login-mode.update', [], false), [
                'login_mode' => 'single_device',
            ]);

        $response->assertRedirect(route('admin.system-settings.login-mode.edit', [], false));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'login_mode',
            'value' => 'single_device',
        ]);
    }

    public function test_unauthorized_admin_cannot_update_login_mode_setting(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.login-mode.update', [], false), [
                'login_mode' => 'single_device',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('system_settings', [
            'key' => 'login_mode',
        ]);
    }

    public function test_authorized_admin_can_update_default_invoice_terms_setting(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-terms.update', [], false), [
                'invoice_terms' => "يحق الاستبدال خلال 3 أيام\nمع إبراز الفاتورة الأصلية",
            ]);

        $response->assertRedirect(route('admin.system-settings.invoice-terms.edit', [], false));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_invoice_terms',
            'value' => "يحق الاستبدال خلال 3 أيام\nمع إبراز الفاتورة الأصلية",
        ]);
    }

    public function test_unauthorized_admin_cannot_update_default_invoice_terms_setting(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-terms.update', [], false), [
                'invoice_terms' => 'شروط غير مصرح بها',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('system_settings', [
            'key' => 'default_invoice_terms',
        ]);
    }

    public function test_authorized_admin_can_update_invoice_print_settings(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-print.update', [], false), [
                'format' => 'a5',
                'show_header' => '1',
            ]);

        $response->assertRedirect(route('admin.system-settings.invoice-print.edit', [], false));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'invoice_print_format',
            'value' => 'a5',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'invoice_print_show_header',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'invoice_print_show_footer',
            'value' => '0',
        ]);
    }

    public function test_unauthorized_admin_cannot_update_invoice_print_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-print.update', [], false), [
                'format' => 'a5',
                'show_header' => '1',
                'show_footer' => '1',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('system_settings', [
            'key' => 'invoice_print_format',
        ]);
    }

    public function test_single_device_mode_stores_active_session_on_login(): void
    {
        SystemSetting::putValue('login_mode', 'single_device');
        $user = $this->createAdminUser();

        $response = $this->post(route('admin.login', [], false), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user, 'admin-web');
        $this->assertNotNull($user->fresh()->active_session_id);
    }

    public function test_single_device_mode_rejects_displaced_session_on_protected_page(): void
    {
        SystemSetting::putValue('login_mode', 'single_device');
        $user = $this->createAdminUser();
        $user->update([
            'active_session_id' => 'another-device-session',
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('admin.home', [], false));

        $response->assertRedirect(route('admin.login', [], false));
        $response->assertSessionHas('error');
        $this->assertGuest('admin-web');
    }

    public function test_multi_device_mode_allows_protected_page_even_if_stored_session_differs(): void
    {
        SystemSetting::putValue('login_mode', 'multi_device');
        $user = $this->createAdminUser();
        $user->update([
            'active_session_id' => 'another-device-session',
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee($user->name);
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
