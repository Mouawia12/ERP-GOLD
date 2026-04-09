<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_unauthenticated_admin_route_redirects_to_admin_login_without_missing_route_error(): void
    {
        $response = $this->get(route('admin.home', [], false));

        $response->assertRedirect(route('admin.login', [], false));
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

    public function test_authenticated_admin_visiting_login_page_is_redirected_to_dashboard(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.login', [], false));

        $response->assertRedirect(route('admin.home', [], false));
    }

    public function test_admin_can_log_out_cleanly_from_post_route(): void
    {
        $user = $this->createAdminUser();

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('admin.logout', [], false));

        $response->assertRedirect(route('admin.login', [], false));
        $response->assertSessionHas('success', 'تم تسجيل الخروج بنجاح.');
        $this->assertGuest('admin-web');
    }

    public function test_admin_can_log_out_cleanly_from_legacy_get_logout_url(): void
    {
        $user = $this->createAdminUser();

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('admin.logout.legacy', [], false));

        $response->assertRedirect(route('admin.login', [], false));
        $this->assertGuest('admin-web');
    }

    public function test_token_mismatch_on_admin_logout_redirects_to_login_instead_of_419_page(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user, 'admin-web');
        $session = app('session.store');
        $session->start();
        $request = Request::create(route('admin.logout', [], false), 'POST');
        $request->setLaravelSession($session);

        $response = $this
            ->app
            ->make(Handler::class)
            ->handleAdminAuthTokenMismatch($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith(route('admin.login', [], false), $response->getTargetUrl());
        $this->assertGuest('admin-web');
    }

    public function test_token_mismatch_on_admin_login_redirects_back_to_login_instead_of_419_page(): void
    {
        $user = $this->createAdminUser();
        $session = app('session.store');
        $session->start();
        $request = Request::create(route('admin.login', [], false), 'POST', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);
        $request->setLaravelSession($session);

        $response = $this
            ->app
            ->make(Handler::class)
            ->handleAdminAuthTokenMismatch($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith(route('admin.login', [], false), $response->getTargetUrl());
        $this->assertGuest('admin-web');
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

    public function test_owner_dashboard_shows_owner_sidebar_without_operational_menu_groups(): void
    {
        $owner = $this->createOwnerUser([
            'employee.subscribers.show',
            'employee.subscribers.add',
            'employee.simplified_tax_invoices.show',
            'employee.system_settings.show',
        ]);

        $response = $this->actingAs($owner, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee('إدارة المشتركين');
        $response->assertSee('قائمة المشتركين');
        $response->assertDontSee('المبيعات الضريبية المبسطة');
        $response->assertDontSee('إعدادات الإدارة');
    }

    public function test_operational_dashboard_hides_owner_sidebar_and_shows_operational_admin_settings(): void
    {
        $user = $this->createAdminUser([
            'employee.branches.show',
            'employee.branches.add',
            'employee.users.show',
            'employee.users.add',
            'employee.system_settings.show',
            'employee.simplified_tax_invoices.show',
        ]);

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee('المبيعات الضريبية المبسطة');
        $response->assertSee('إدارة المشترك');
        $response->assertSee('قائمة الفروع');
        $response->assertSee('إضافة فرع');
        $response->assertSee('قائمة المستخدمين');
        $response->assertSee('إضافة مستخدم');
        $response->assertSee('إعدادات الإدارة');
        $response->assertSee('إعدادات تسجيل الدخول');
        $response->assertDontSee('إدارة المشتركين');
        $response->assertDontSee('قائمة المشتركين');
        $response->assertDontSee('صلاحيات المشتركين');
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
            'branch_ids' => [$targetBranch->id],
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
            'branch_ids' => [$targetBranch->id],
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
                'branch_ids' => [$newBranch->id],
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

        $this->assertCount(5, $auditLogs);
        $this->assertEqualsCanonicalizing(
            ['assigned_branches_changed', 'branch_changed', 'password_changed', 'role_changed', 'status_changed'],
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
        $showResponse->assertSee('تغيير الفروع المسموح بها');
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

    public function test_branch_creation_allows_duplicate_tax_number_and_commercial_register_when_name_differs(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.add',
        ]);

        Branch::create($this->branchFormData([
            'name' => ['ar' => 'الفرع الأصلي', 'en' => 'Original Branch'],
            'email' => 'original-branch@example.com',
            'commercial_register' => '1234567890',
            'tax_number' => '123456789012345',
        ]));

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.branches.create', [], false))
            ->post(route('admin.branches.store', [], false), $this->branchRequestData([
                'name' => 'فرع جديد 2',
                'email' => 'duplicate-branch@example.com',
                'commercial_register' => '1234567890',
                'tax_number' => '123456789012345',
            ]));

        $response->assertRedirect(route('admin.branches.index', [], false));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('branches', [
            'email' => 'duplicate-branch@example.com',
            'commercial_register' => '1234567890',
            'tax_number' => '123456789012345',
        ]);
    }

    public function test_branch_creation_rejects_duplicate_branch_name_for_same_subscriber(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.add',
        ]);

        Branch::create($this->branchFormData([
            'name' => ['ar' => 'الفرع الأصلي', 'en' => 'Original Branch'],
            'email' => 'original-duplicate-name@example.com',
            'commercial_register' => '1234567890',
            'tax_number' => '123456789012345',
        ]));

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.branches.create', [], false))
            ->post(route('admin.branches.store', [], false), $this->branchRequestData([
                'name' => 'الفرع الأصلي',
                'email' => 'duplicate-name@example.com',
                'commercial_register' => '0987654321',
                'tax_number' => '543210987654321',
            ]));

        $response->assertRedirect(route('admin.branches.create', [], false));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_branch_create_form_keeps_old_input_and_renders_inline_errors_after_validation_failure(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.branches.create', [], false))
            ->followingRedirects()
            ->post(route('admin.branches.store', [], false), $this->branchRequestData([
                'name' => 'فرع يحتفظ بالمدخلات',
                'email' => 'keep-branch@example.com',
                'commercial_register' => '123456789',
                'tax_number' => '12345678901234',
                'building_number' => '123',
                'plot_identification' => '12',
                'postal_code' => '1234',
                'street_name' => 'شارع الاحتفاظ',
                'country' => 'السعودية',
                'region' => 'الرياض',
                'city' => 'الرياض',
                'district' => 'الملز',
                'short_address' => 'عنوان محفوظ',
            ]));

        $response->assertOk();
        $response->assertSee('data-branch-validation-form="create"', false);
        $response->assertSee('value="فرع يحتفظ بالمدخلات"', false);
        $response->assertSee('value="keep-branch@example.com"', false);
        $response->assertSee('value="123456789"', false);
        $response->assertSee('value="12345678901234"', false);
        $response->assertSee(__('dashboard.tax_settings.validations.commercial_register_digits', ['digits' => 10]));
        $response->assertSee(__('dashboard.tax_settings.validations.tax_number_digits', ['digits' => 15]));
        $response->assertSee('data-feedback-for="commercial_register"', false);
    }

    public function test_branch_update_rejects_duplicate_branch_name_for_same_subscriber(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.edit',
        ]);

        $originalBranch = Branch::create($this->branchFormData([
            'name' => ['ar' => 'الفرع الأصلي', 'en' => 'Original Branch'],
            'email' => 'original-update@example.com',
            'commercial_register' => '1234567890',
            'tax_number' => '123456789012345',
        ]));

        $branchToUpdate = Branch::create($this->branchFormData([
            'name' => ['ar' => 'الفرع الثاني', 'en' => 'Second Branch'],
            'email' => 'second-update@example.com',
            'commercial_register' => '0987654321',
            'tax_number' => '543210987654321',
        ]));

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.branches.edit', $branchToUpdate->id, false))
            ->patch(route('admin.branches.update', $branchToUpdate->id, false), $this->branchRequestData([
                'name' => 'الفرع الأصلي',
                'email' => 'second-update@example.com',
                'commercial_register' => $originalBranch->commercial_register,
                'tax_number' => $originalBranch->tax_number,
            ]));

        $response->assertRedirect(route('admin.branches.edit', $branchToUpdate->id, false));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_branch_edit_form_keeps_old_input_and_renders_inline_errors_after_validation_failure(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.edit',
        ]);

        $branch = Branch::create($this->branchFormData([
            'name' => ['ar' => 'فرع التعديل', 'en' => 'Editable Branch'],
            'email' => 'editable-branch@example.com',
        ]));

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->from(route('admin.branches.edit', $branch->id, false))
            ->followingRedirects()
            ->patch(route('admin.branches.update', $branch->id, false), $this->branchRequestData([
                'name' => 'فرع التعديل',
                'email' => 'updated-invalid@example.com',
                'commercial_register' => '123456789',
                'tax_number' => '12345678901234',
                'building_number' => '123',
                'plot_identification' => '12',
                'postal_code' => '1234',
            ]));

        $response->assertOk();
        $response->assertSee('data-branch-validation-form="edit"', false);
        $response->assertSee('value="updated-invalid@example.com"', false);
        $response->assertSee('value="123456789"', false);
        $response->assertSee(__('dashboard.tax_settings.validations.commercial_register_digits', ['digits' => 10]));
        $response->assertSee(__('dashboard.tax_settings.validations.tax_number_digits', ['digits' => 15]));
        $response->assertSee('data-feedback-for="tax_number"', false);
    }

    public function test_branch_index_counts_only_active_assigned_users(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.show',
        ]);

        $branch = Branch::create([
            'name' => ['ar' => 'فرع العد', 'en' => 'Count Branch'],
            'email' => 'count-branch@example.com',
            'phone' => '123123123',
            'tax_number' => '123451234512345',
            'short_address' => 'مكة',
        ]);

        $activeUser = User::create([
            'name' => 'Active Branch User',
            'email' => 'active-branch-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $inactiveAssignmentUser = User::create([
            'name' => 'Inactive Assignment User',
            'email' => 'inactive-assignment-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $inactiveAssignmentUser->branches()->updateExistingPivot($branch->id, [
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'admin-web')->get(route('admin.branches.index', [], false));

        $response->assertOk();

        $branchRow = $response->viewData('data')->firstWhere('id', $branch->id);

        $this->assertNotNull($branchRow);
        $this->assertSame(1, $branchRow->users_count);
        $this->assertTrue($activeUser->branches()->where('branch_id', $branch->id)->exists());
    }

    public function test_branch_details_allow_managing_users_from_branch_screen(): void
    {
        $admin = $this->createAdminUser([
            'employee.branches.show',
            'employee.users.add',
            'employee.users.edit',
            'employee.users.show',
        ]);
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الإدارة', 'en' => 'Management Branch'],
            'email' => 'management-branch@example.com',
            'phone' => '999999999',
            'tax_number' => '999999999999999',
            'short_address' => 'جدة',
        ]);
        $role = Role::create([
            'name' => ['ar' => 'مشرف', 'en' => 'Supervisor'],
            'guard_name' => 'admin-web',
        ]);

        $linkedUser = User::create([
            'name' => 'Managed From Branch',
            'email' => 'managed-from-branch@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
        $linkedUser->assignRole($role);

        $branchResponse = $this->actingAs($admin, 'admin-web')->get(route('admin.branches.show', $branch->id, false));

        $branchResponse->assertOk();
        $branchResponse->assertSee(e(route('admin.users.create', [
            'branch_id' => $branch->id,
            'return_branch_id' => $branch->id,
        ], false)), false);
        $branchResponse->assertSee(e(route('admin.users.edit', [
            'user' => $linkedUser->id,
            'return_branch_id' => $branch->id,
        ], false)), false);

        $createResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.users.create', [
                'branch_id' => $branch->id,
                'return_branch_id' => $branch->id,
            ], false));

        $createResponse->assertOk();
        $createResponse->assertSee('name="return_branch_id"', false);
        $createResponse->assertSee('سيتم إعادتك إلى شاشة هذا الفرع بعد الحفظ.');

        $storeResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('admin.users.store', [], false), [
                'name' => 'Branch Managed User',
                'email' => 'branch-managed-user@example.com',
                'role_id' => $role->id,
                'branch_id' => $branch->id,
                'branch_ids' => [$branch->id],
                'password' => 'secret123',
                'confirm-password' => 'secret123',
                'return_branch_id' => $branch->id,
            ]);

        $storeResponse->assertRedirect(route('admin.branches.show', $branch->id, false));

        $storedUser = User::where('email', 'branch-managed-user@example.com')->firstOrFail();

        $updateResponse = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.users.update', $storedUser->id, false), [
                'name' => 'Branch Managed User Updated',
                'email' => 'branch-managed-user@example.com',
                'role_id' => $role->id,
                'branch_id' => $branch->id,
                'branch_ids' => [$branch->id],
                'status' => '1',
                'return_branch_id' => $branch->id,
            ]);

        $updateResponse->assertRedirect(route('admin.branches.show', $branch->id, false));
        $this->assertDatabaseHas('users', [
            'id' => $storedUser->id,
            'name' => 'Branch Managed User Updated',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_owner_cannot_open_operational_branch_routes_even_with_permissions(): void
    {
        $user = $this->createOwnerUser([
            'employee.branches.show',
        ]);

        $branch = Branch::create([
            'name' => ['ar' => 'فرع ممنوع', 'en' => 'Restricted Branch'],
            'email' => 'restricted-branch@example.com',
            'phone' => '333333333',
        ]);

        $response = $this->actingAs($user, 'admin-web')->get(route('admin.branches.show', $branch->id, false));

        $response->assertRedirect(route('admin.home', [], false));
        $response->assertSessionHas('warning');
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
                'templates' => [
                    [
                        'key' => 'sales-retail',
                        'context' => 'sales_simplified',
                        'title' => 'بيع تجزئة',
                        'content' => "يحق الاستبدال خلال 3 أيام\nمع إبراز الفاتورة الأصلية",
                    ],
                    [
                        'key' => 'sales-company',
                        'context' => 'sales_standard',
                        'title' => 'بيع شركات',
                        'content' => "تعتمد الفاتورة على السجل الضريبي للعميل\nولا تقبل التعديلات اليدوية",
                    ],
                    [
                        'key' => 'supplier-standard',
                        'context' => 'purchases',
                        'title' => 'مورد قياسي',
                        'content' => "يتم اعتماد الوزن بعد الفحص\nوالسداد حسب الاتفاق",
                    ],
                ],
                'default_template_keys' => [
                    'sales_simplified' => 'sales-retail',
                    'sales_standard' => 'sales-company',
                    'purchases' => 'supplier-standard',
                ],
            ]);

        $response->assertRedirect(route('admin.system-settings.invoice-terms.edit', [], false));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_invoice_terms',
            'value' => "يحق الاستبدال خلال 3 أيام\nمع إبراز الفاتورة الأصلية",
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_invoice_terms_template_key',
            'value' => 'sales-retail',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_invoice_terms_template_keys',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'invoice_terms_templates',
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
                'template' => 'modern',
                'orientation' => 'landscape',
                'show_header' => '1',
            ]);

        $response->assertRedirect(route('admin.system-settings.invoice-print.edit', [], false));
        $this->assertDatabaseHas('user_invoice_print_settings', [
            'user_id' => $admin->id,
            'format' => 'a5',
            'show_header' => 1,
            'show_footer' => 0,
            'template' => 'modern',
            'orientation' => 'landscape',
        ]);
    }

    public function test_unauthorized_admin_cannot_update_invoice_print_settings(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-print.update', [], false), [
                'format' => 'a5',
                'template' => 'compact',
                'orientation' => 'portrait',
                'show_header' => '1',
                'show_footer' => '1',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_invoice_print_settings', [
            'user_id' => $admin->id,
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
    private function createOwnerUser(array $permissions = []): User
    {
        return $this->createAdminUser($permissions, true);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = [], bool $isOwner = false): User
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
            $permission = Permission::findOrCreate($permissionName, 'admin-web');

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => $isOwner ? 'owner@example.com' : 'admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'is_admin' => $isOwner,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function branchFormData(array $overrides = []): array
    {
        return array_merge([
            'name' => ['ar' => 'فرع افتراضي', 'en' => 'Default Branch'],
            'email' => 'branch-'.uniqid().'@example.com',
            'phone' => '0500000000',
            'commercial_register' => '1111222233',
            'tax_number' => '111122223333444',
            'street_name' => 'شارع الملك',
            'building_number' => '1234',
            'plot_identification' => '5678',
            'country' => 'السعودية',
            'region' => 'الرياض',
            'city' => 'الرياض',
            'district' => 'الملز',
            'postal_code' => '12345',
            'short_address' => 'الرياض - الملز',
            'status' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function branchRequestData(array $overrides = []): array
    {
        $base = $this->branchFormData();
        $base['name'] = $base['name']['ar'];

        return array_merge($base, $overrides);
    }
}
