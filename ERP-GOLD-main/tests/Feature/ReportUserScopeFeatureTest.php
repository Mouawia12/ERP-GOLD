<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Reports\ReportUserScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * قاعدة رؤية التقارير: المستخدم التشغيلي مقصور على فواتيره، إلا الحساب الرئيسي
 * للمشترك ومن مُنح صلاحية «تقارير كل المستخدمين».
 */
class ReportUserScopeFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_operational_user_is_locked_to_his_own_invoices(): void
    {
        [$subscriber, $primary] = $this->createSubscriberWithPrimaryAdmin();
        $employee = $this->createUser($subscriber, 'employee@example.com');

        $scope = app(ReportUserScopeService::class);

        $this->assertFalse($scope->seesAllUsers($employee));
        $this->assertSame($employee->id, $scope->lockedUserId($employee));
        $this->assertTrue($scope->seesAllUsers($primary), 'الحساب الرئيسي للمشترك يرى الجميع');
        $this->assertNull($scope->lockedUserId($primary));
    }

    public function test_the_all_users_reports_permission_unlocks_the_whole_branch_data(): void
    {
        [$subscriber] = $this->createSubscriberWithPrimaryAdmin();
        $manager = $this->createUser($subscriber, 'manager@example.com');

        $manager->givePermissionTo(
            Permission::findOrCreate(ReportUserScopeService::ALL_USERS_PERMISSION, 'admin-web')
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $scope = app(ReportUserScopeService::class);

        $this->assertTrue($scope->seesAllUsers($manager->fresh()));
        $this->assertNull($scope->lockedUserId($manager->fresh()));
    }

    public function test_a_missing_permission_row_keeps_the_isolation(): void
    {
        [$subscriber] = $this->createSubscriberWithPrimaryAdmin();
        $employee = $this->createUser($subscriber, 'no-permission@example.com');

        // الصلاحية غير معرّفة أصلاً في هذا التثبيت — لا استثناء، والعزل باقٍ.
        $this->assertFalse(app(ReportUserScopeService::class)->seesAllUsers($employee));
    }

    public function test_the_permission_appears_once_in_the_permissions_screen(): void
    {
        $groups = app(\App\Services\Permissions\PermissionMatrixService::class)->permissionGroups();

        $module = collect($groups)
            ->flatMap(fn (array $group) => $group['modules'])
            ->firstWhere('key', 'all_users_reports');

        $this->assertNotNull($module, 'الوحدة غير معروضة في شاشة الصلاحيات');
        $this->assertSame(['show'], array_keys($module['permissions']), 'إجراء «عرض» فقط');
        $this->assertSame(
            ReportUserScopeService::ALL_USERS_PERMISSION,
            $module['permissions']['show']['name']
        );
    }

    /**
     * @return array{0: Subscriber, 1: User}
     */
    private function createSubscriberWithPrimaryAdmin(): array
    {
        $subscriber = Subscriber::create([
            'name' => 'مشترك التقارير',
            'login_email' => 'reports-subscriber@example.com',
            'status' => true,
        ]);

        $primary = $this->createUser($subscriber, 'primary@example.com');
        $subscriber->forceFill(['admin_user_id' => $primary->id])->save();

        return [$subscriber, $primary->fresh()];
    }

    private function createUser(Subscriber $subscriber, string $email): User
    {
        $branch = Branch::create([
            'subscriber_id' => $subscriber->id,
            'name' => ['ar' => 'فرع', 'en' => 'Branch'],
            'phone' => '05'.random_int(10000000, 99999999),
        ]);

        return User::create([
            'name' => 'مستخدم '.$email,
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'subscriber_id' => $subscriber->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }
}
