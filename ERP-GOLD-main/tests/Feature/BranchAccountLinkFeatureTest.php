<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounts\BranchAccountEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * فرع لا يُربط بحساب فرع آخر — وإلا رُحِّلت مبيعاته إلى حساب لا يخصّه فظهر
 * فرع داخل قائمة دخل فرع.
 */
class BranchAccountLinkFeatureTest extends TestCase
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

    public function test_an_unassigned_account_belongs_to_every_branch(): void
    {
        $accountId = $this->createAccount(['code' => '4101']);
        $branch = $this->createBranch('فرع أول');

        $this->assertTrue(
            app(BranchAccountEligibility::class)->isLinkableTo($accountId, $branch->id)
        );
    }

    public function test_an_account_assigned_to_one_branch_is_refused_for_another(): void
    {
        $mine = $this->createBranch('زاتكا المرقب الصغير');
        $theirs = $this->createBranch('فرع المرقب الصغير');
        $accountId = $this->createAccount(['code' => '4102']);
        DB::table('account_branch')->insert([
            'account_id' => $accountId,
            'branch_id' => $theirs->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eligibility = app(BranchAccountEligibility::class);

        $this->assertTrue($eligibility->isLinkableTo($accountId, $theirs->id));
        $this->assertFalse($eligibility->isLinkableTo($accountId, $mine->id));
        $this->assertStringContainsString('فرع المرقب الصغير', $eligibility->branchNamesFor($accountId));
    }

    public function test_saving_settings_with_another_branchs_account_is_refused(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit', 'employee.accounts.add']);
        $theirs = $this->createBranch('فرع المرقب الصغير');

        $accountId = $this->createAccount(['code' => '4103']);
        DB::table('account_branch')->insert([
            'account_id' => $accountId,
            'branch_id' => $theirs->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settingId = DB::table('account_settings')->insertGetId([
            'branch_id' => $admin->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.settings.update', $settingId, false), [
                'branch_id' => $admin->branch_id,
                'sales_account' => $accountId,
            ])
            ->assertStatus(422);

        $this->assertNull(DB::table('account_settings')->find($settingId)->sales_account);
    }

    public function test_saving_settings_with_this_branchs_account_is_allowed(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit', 'employee.accounts.add']);

        $accountId = $this->createAccount(['code' => '4104']);
        DB::table('account_branch')->insert([
            'account_id' => $accountId,
            'branch_id' => $admin->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settingId = DB::table('account_settings')->insertGetId([
            'branch_id' => $admin->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.settings.update', $settingId, false), [
                'branch_id' => $admin->branch_id,
                'sales_account' => $accountId,
            ]);

        $this->assertSame($accountId, (int) DB::table('account_settings')->find($settingId)->sales_account);
    }

    public function test_an_existing_wrong_link_does_not_block_editing_another_field(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit', 'employee.accounts.add']);
        $theirs = $this->createBranch('فرع آخر');

        $wrongId = $this->createAccount(['code' => '4105']);
        DB::table('account_branch')->insert([
            'account_id' => $wrongId,
            'branch_id' => $theirs->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sharedId = $this->createAccount(['code' => '4106']);

        // ربط خاطئ موجود من قبل هذا الفحص
        $settingId = DB::table('account_settings')->insertGetId([
            'branch_id' => $admin->branch_id,
            'sales_account' => $wrongId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // تعديل حقل آخر يجب أن ينجح ولا يُحتجز المستخدم بسبب ربط قديم
        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.settings.update', $settingId, false), [
                'branch_id' => $admin->branch_id,
                'sales_account' => $wrongId,
                'safe_account' => $sharedId,
            ]);

        $setting = DB::table('account_settings')->find($settingId);
        $this->assertSame($sharedId, (int) $setting->safe_account);
        $this->assertSame($wrongId, (int) $setting->sales_account);
    }

    public function test_the_audit_command_lists_a_cross_branch_link(): void
    {
        $mine = $this->createBranch('زاتكا المرقب الصغير');
        $theirs = $this->createBranch('فرع المرقب الصغير');

        $accountId = $this->createAccount([
            'code' => '4107',
            'name' => ['ar' => 'مبيعات المرقب الصغير', 'en' => 'Other Sales'],
        ]);
        DB::table('account_branch')->insert([
            'account_id' => $accountId,
            'branch_id' => $theirs->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_settings')->insert([
            'branch_id' => $mine->id,
            'sales_account' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('accounts:audit-branch-links')
            ->expectsOutputToContain('روابط محاسبية تشير إلى حسابات تخص فروعًا أخرى')
            ->assertSuccessful();
    }

    public function test_the_audit_command_is_quiet_when_every_link_is_sound(): void
    {
        $branch = $this->createBranch('فرع سليم');
        $accountId = $this->createAccount(['code' => '4108']);

        DB::table('account_settings')->insert([
            'branch_id' => $branch->id,
            'sales_account' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('accounts:audit-branch-links')
            ->expectsOutputToContain('لا توجد روابط محاسبية تشير إلى حساب يخص فرعًا آخر')
            ->assertSuccessful();
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '05' . random_int(10000000, 99999999),
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = $this->createBranch('زاتكا المرقب الصغير');

        $role = Role::create([
            'name' => ['ar' => 'مدير الروابط', 'en' => 'Links Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'links-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(array $attributes = []): int
    {
        $name = $attributes['name'] ?? ['ar' => 'حساب اختبار', 'en' => 'Test Account'];
        unset($attributes['name']);

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
            'code' => '4100',
            'old_id' => null,
            'level' => '2',
            'parent_account_id' => null,
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
