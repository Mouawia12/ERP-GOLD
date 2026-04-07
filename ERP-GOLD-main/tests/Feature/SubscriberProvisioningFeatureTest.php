<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriberProvisioningFeatureTest extends TestCase
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

    public function test_owner_can_create_subscriber_and_first_admin_account(): void
    {
        Permission::findOrCreate('employee.subscribers.add', 'admin-web');
        Permission::findOrCreate('employee.subscribers.show', 'admin-web');
        Permission::findOrCreate('employee.branches.show', 'admin-web');
        Permission::findOrCreate('employee.users.show', 'admin-web');

        $owner = $this->createOwnerUser([
            'employee.subscribers.add',
            'employee.subscribers.show',
        ]);

        $response = $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'شركة الذهب الأولى',
                'admin_name' => 'مدير الشركة الأولى',
                'login_email' => 'subscriber-one@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'contact_email' => 'contact-one@example.com',
                'contact_phone' => '0555555555',
                'starts_at' => '2026-03-23',
                'ends_at' => '2026-12-31',
                'max_users' => 5,
                'max_branches' => 2,
                'default_branch_name' => 'الفرع الرئيسي للشركة الأولى',
                'default_tax_number' => '123456789012345',
                'default_address' => 'الرياض',
                'status' => '1',
            ]);

        $subscriber = Subscriber::query()->where('login_email', 'subscriber-one@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.subscribers.show', $subscriber, false));

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'name' => 'شركة الذهب الأولى',
            'login_email' => 'subscriber-one@example.com',
            'admin_user_id' => $subscriber->admin_user_id,
        ]);
        $this->assertDatabaseHas('branches', [
            'subscriber_id' => $subscriber->id,
        ]);
        $this->assertDatabaseHas('users', [
            'subscriber_id' => $subscriber->id,
            'email' => 'subscriber-one@example.com',
            'is_admin' => false,
        ]);

        $adminUser = User::query()->where('email', 'subscriber-one@example.com')->firstOrFail();
        $branch = Branch::query()->where('subscriber_id', $subscriber->id)->firstOrFail();

        $this->assertSame('الفرع الرئيسي للشركة الأولى', $branch->name);
        $this->assertTrue(Hash::check('secret123', $adminUser->password));
        $this->assertSame($branch->id, (int) $adminUser->branch_id);
        $this->assertDatabaseHas('branch_user', [
            'user_id' => $adminUser->id,
            'branch_id' => $branch->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('warehouses', [
            'branch_id' => $branch->id,
            'name' => 'المستودع الرئيسي',
        ]);
        $this->assertDatabaseHas('account_settings', [
            'branch_id' => $branch->id,
            'subscriber_id' => $subscriber->id,
        ]);
        $this->assertDatabaseHas('accounts', [
            'subscriber_id' => $subscriber->id,
            'code' => '1107',
        ]);
        $this->assertDatabaseHas('accounts', [
            'subscriber_id' => $subscriber->id,
            'code' => '2101',
        ]);
        $this->assertDatabaseHas('accounts', [
            'subscriber_id' => $subscriber->id,
            'code' => '410101',
        ]);
    }

    public function test_subscriber_admin_sees_only_his_subscriber_users_and_branches(): void
    {
        foreach ([
            'employee.subscribers.add',
            'employee.subscribers.show',
            'employee.branches.show',
            'employee.users.show',
        ] as $permission) {
            Permission::findOrCreate($permission, 'admin-web');
        }

        $owner = $this->createOwnerUser([
            'employee.subscribers.add',
            'employee.subscribers.show',
        ]);

        $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'مشترك ألف',
                'login_email' => 'subscriber-a@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'max_users' => 5,
                'max_branches' => 2,
                'default_branch_name' => 'فرع ألف',
                'status' => '1',
            ]);

        $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'مشترك باء',
                'login_email' => 'subscriber-b@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'max_users' => 5,
                'max_branches' => 2,
                'default_branch_name' => 'فرع باء',
                'status' => '1',
            ]);

        $subscriberA = Subscriber::query()->where('login_email', 'subscriber-a@example.com')->firstOrFail();
        $subscriberB = Subscriber::query()->where('login_email', 'subscriber-b@example.com')->firstOrFail();
        $adminA = $subscriberA->adminUser()->firstOrFail();
        $adminB = $subscriberB->adminUser()->firstOrFail();

        $branchesResponse = $this->actingAs($adminA, 'admin-web')
            ->get(route('admin.branches.index', [], false));

        $branchesResponse->assertOk();
        $branchesResponse->assertSee('فرع ألف');
        $branchesResponse->assertDontSee('فرع باء');

        $usersResponse = $this->actingAs($adminA, 'admin-web')
            ->get(route('admin.users.index', [], false));

        $usersResponse->assertOk();
        $usersResponse->assertSee($adminA->email);
        $usersResponse->assertDontSee($adminB->email);
    }

    public function test_suspended_subscriber_admin_cannot_log_in(): void
    {
        Permission::findOrCreate('employee.subscribers.add', 'admin-web');
        Permission::findOrCreate('employee.subscribers.show', 'admin-web');

        $owner = $this->createOwnerUser([
            'employee.subscribers.add',
            'employee.subscribers.show',
        ]);

        $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'مشترك موقوف',
                'login_email' => 'subscriber-suspended@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'max_users' => 2,
                'max_branches' => 1,
                'status' => '1',
            ]);

        $subscriber = Subscriber::query()->where('login_email', 'subscriber-suspended@example.com')->firstOrFail();
        $subscriber->update(['status' => false]);

        Auth::guard('admin-web')->logout();
        $this->flushSession();

        $response = $this->post(route('admin.login', [], false), [
            'email' => 'subscriber-suspended@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin-web');
    }

    public function test_owner_can_update_subscriber_admin_password_without_exposing_it_on_show_page(): void
    {
        foreach ([
            'employee.subscribers.add',
            'employee.subscribers.show',
            'employee.subscribers.edit',
        ] as $permission) {
            Permission::findOrCreate($permission, 'admin-web');
        }

        $owner = $this->createOwnerUser([
            'employee.subscribers.add',
            'employee.subscribers.show',
            'employee.subscribers.edit',
        ]);

        $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'مشترك تحديث كلمة السر',
                'login_email' => 'subscriber-password-update@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'max_users' => 3,
                'max_branches' => 1,
                'status' => '1',
            ]);

        $subscriber = Subscriber::query()->where('login_email', 'subscriber-password-update@example.com')->firstOrFail();
        $adminUser = $subscriber->adminUser()->firstOrFail();

        $updateResponse = $this->actingAs($owner, 'admin-web')
            ->put(route('admin.subscribers.update', $subscriber, false), [
                'name' => 'مشترك تحديث كلمة السر',
                'login_email' => 'subscriber-password-update@example.com',
                'contact_email' => null,
                'contact_phone' => null,
                'max_users' => 3,
                'max_branches' => 1,
                'new_password' => 'new-secret-456',
                'new_password_confirmation' => 'new-secret-456',
                'status' => '1',
            ]);

        $updateResponse->assertRedirect(route('admin.subscribers.show', $subscriber, false));

        $this->assertTrue(Hash::check('new-secret-456', $adminUser->fresh()->password));

        $showResponse = $this->actingAs($owner, 'admin-web')
            ->get(route('admin.subscribers.show', $subscriber, false));

        $showResponse->assertOk();
        $showResponse->assertDontSee('new-secret-456');
        $showResponse->assertDontSee('secret123');
    }

    public function test_subscriber_edit_validation_messages_are_displayed_in_clear_arabic(): void
    {
        foreach ([
            'employee.subscribers.add',
            'employee.subscribers.show',
            'employee.subscribers.edit',
        ] as $permission) {
            Permission::findOrCreate($permission, 'admin-web');
        }

        $owner = $this->createOwnerUser([
            'employee.subscribers.add',
            'employee.subscribers.show',
            'employee.subscribers.edit',
        ]);

        $this->actingAs($owner, 'admin-web')
            ->post(route('admin.subscribers.store', [], false), [
                'name' => 'مشترك رسائل التحقق',
                'login_email' => 'subscriber-validation@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'max_users' => 3,
                'max_branches' => 1,
                'status' => '1',
            ]);

        $subscriber = Subscriber::query()->where('login_email', 'subscriber-validation@example.com')->firstOrFail();

        $response = $this->actingAs($owner, 'admin-web')
            ->from(route('admin.subscribers.edit', $subscriber, false))
            ->followingRedirects()
            ->put(route('admin.subscribers.update', $subscriber, false), [
                'name' => 'مشترك رسائل التحقق',
                'login_email' => 'subscriber-validation@example.com',
                'contact_email' => null,
                'contact_phone' => null,
                'max_users' => 3,
                'max_branches' => 1,
                'new_password' => '123',
                'new_password_confirmation' => '123',
                'status' => '1',
            ]);

        $response->assertOk();
        $response->assertSee('تعذر حفظ التعديلات. يرجى مراجعة الأخطاء التالية:');
        $response->assertSee('يجب ألا تقل كلمة السر الجديدة عن 6 أحرف.');
        $response->assertDontSee('validation.min.string');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createOwnerUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع المالك', 'en' => 'Owner Branch'],
            'phone' => '123456789',
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مالك النظام', 'en' => 'System Owner'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $owner = User::create([
            'name' => 'System Owner',
            'email' => 'owner-subscriber@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'is_admin' => true,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $owner->assignRole($role);

        return $owner;
    }
}
