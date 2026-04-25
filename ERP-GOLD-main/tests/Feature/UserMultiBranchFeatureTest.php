<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Services\Branches\BranchContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserMultiBranchFeatureTest extends TestCase
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

    public function test_admin_can_assign_multiple_branches_to_user_and_update_default_branch(): void
    {
        $admin = $this->createAdminUser([
            'employee.users.add',
            'employee.users.edit',
            'employee.users.show',
        ]);

        $branchA = $this->createBranch('فرع ألف');
        $branchB = $this->createBranch('فرع باء');
        $branchC = $this->createBranch('فرع جيم');
        $role = $this->createRole('موظف متعدد الفروع', 'Multi Branch Employee');

        $storeResponse = $this->actingAs($admin, 'admin-web')
            ->post(route('admin.users.store', [], false), [
                'name' => 'Multi Branch User',
                'email' => 'multi-branch-user@example.com',
                'role_id' => $role->id,
                'branch_id' => $branchA->id,
                'branch_ids' => [$branchA->id, $branchB->id],
                'password' => 'secret123',
                'confirm-password' => 'secret123',
            ]);

        $storeResponse->assertRedirect(route('admin.users.index', [], false));

        $user = User::query()->where('email', 'multi-branch-user@example.com')->firstOrFail();

        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $branchA->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $branchB->id,
            'is_default' => false,
            'is_active' => true,
        ]);

        $showResponse = $this->actingAs($admin, 'admin-web')
            ->get(route('admin.users.show', $user->id, false));

        $showResponse->assertOk();
        $showResponse->assertSee('فرع ألف');
        $showResponse->assertSee('فرع باء');
        $showResponse->assertSee('افتراضي');

        $updateResponse = $this->actingAs($admin, 'admin-web')
            ->patch(route('admin.users.update', $user->id, false), [
                'name' => 'Multi Branch User',
                'email' => 'multi-branch-user@example.com',
                'role_id' => $role->id,
                'branch_id' => $branchC->id,
                'branch_ids' => [$branchB->id, $branchC->id],
                'status' => 1,
            ]);

        $updateResponse->assertRedirect(route('admin.users.index', [], false));

        $user->refresh();
        $this->assertSame($branchC->id, (int) $user->branch_id);
        $this->assertDatabaseMissing('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $branchA->id,
        ]);
        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $branchB->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $branchC->id,
            'is_default' => true,
        ]);

        $auditLog = UserAuditLog::query()
            ->where('target_user_id', $user->id)
            ->where('event_key', 'assigned_branches_changed')
            ->latest()
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('فرع ألف', $auditLog->summary);
        $this->assertStringContainsString('فرع جيم', $auditLog->summary);
    }

    public function test_multi_branch_user_can_switch_current_branch_and_operational_lists_follow_it(): void
    {
        $financialYear = $this->createFinancialYear();
        $branchA = $this->createBranch('فرع التشغيل ألف');
        $branchB = $this->createBranch('فرع التشغيل باء');
        $branchC = $this->createBranch('فرع التشغيل جيم');
        $customerId = $this->createCustomer('عميل التبديل');

        $user = User::create([
            'name' => 'Switch User',
            'email' => 'switch-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branchA->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $this->attachPermissions($user, [
            'employee.simplified_tax_invoices.show',
            'employee.simplified_tax_invoices.add',
        ]);

        app(BranchContextService::class)->syncUserBranches($user, [$branchA->id, $branchB->id], $branchA->id);

        $invoiceA = $this->createInvoice($financialYear, $branchA, $user, $customerId, '2026-03-23', '09:00:00');
        $invoiceB = $this->createInvoice($financialYear, $branchB, $user, $customerId, '2026-03-23', '10:00:00');

        $defaultCreateResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $defaultCreateResponse->assertOk();
        $defaultCreateResponse->assertSee('name="branch_id"', false);
        $defaultCreateResponse->assertSee('value="'.$branchA->id.'"', false);
        $defaultCreateResponse->assertSee($branchA->name);

        $defaultIndexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('sales.index', ['type' => 'simplified'], false));

        $defaultIndexResponse->assertOk();
        $defaultIndexResponse->assertJsonFragment([
            'bill_number' => $invoiceA->bill_number,
        ]);
        $defaultIndexResponse->assertJsonMissing([
            'bill_number' => $invoiceB->bill_number,
        ]);

        $switchResponse = $this->actingAs($user, 'admin-web')
            ->from(route('sales.create', ['type' => 'simplified'], false))
            ->post(route('admin.current_branch.update', [], false), [
                'branch_id' => $branchB->id,
            ]);

        $switchResponse->assertRedirect(route('sales.create', ['type' => 'simplified'], false));

        $switchedCreateResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $switchedCreateResponse->assertOk();

        $switchedIndexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->withSession([
            BranchContextService::SESSION_KEY => $branchB->id,
        ])->actingAs($user, 'admin-web')
            ->get(route('sales.index', ['type' => 'simplified'], false));

        $switchedIndexResponse->assertOk();
        $switchedIndexResponse->assertJsonFragment([
            'bill_number' => $invoiceB->bill_number,
        ]);
        $switchedIndexResponse->assertJsonMissing([
            'bill_number' => $invoiceA->bill_number,
        ]);

        $forbiddenSwitchResponse = $this->withSession([
            BranchContextService::SESSION_KEY => $branchB->id,
        ])->actingAs($user, 'admin-web')
            ->post(route('admin.current_branch.update', [], false), [
                'branch_id' => $branchC->id,
            ]);

        $forbiddenSwitchResponse->assertRedirect();
        $forbiddenSwitchResponse->assertSessionMissing('success');
    }

    public function test_multi_branch_user_can_show_dashboard_for_all_assigned_branches_without_changing_operational_branch(): void
    {
        $financialYear = $this->createFinancialYear();
        $branchA = $this->createBranch('فرع الداشبورد ألف');
        $branchB = $this->createBranch('فرع الداشبورد باء');
        $customerId = $this->createCustomer('عميل نطاق الداشبورد');

        $user = User::create([
            'name' => 'Dashboard Scope User',
            'email' => 'dashboard-scope-user@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branchA->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        app(BranchContextService::class)->syncUserBranches($user, [$branchA->id, $branchB->id], $branchA->id);

        $invoiceA = $this->createInvoice($financialYear, $branchA, $user, $customerId, '2026-03-23', '09:00:00');
        $invoiceB = $this->createInvoice($financialYear, $branchB, $user, $customerId, '2026-03-23', '10:00:00');

        $defaultDashboardResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.home', [], false));

        $defaultDashboardResponse->assertOk();
        $defaultDashboardResponse->assertSee('عرض الفرع النشط فقط');
        $defaultDashboardResponse->assertSee('115.00');
        $defaultDashboardResponse->assertDontSee('230.00');

        $allBranchesResponse = $this->actingAs($user, 'admin-web')
            ->from(route('admin.home', [], false))
            ->post(route('admin.current_branch.update', [], false), [
                'branch_id' => BranchContextService::DASHBOARD_SCOPE_ALL,
            ]);

        $allBranchesResponse->assertRedirect(route('admin.home', [], false));

        $aggregatedDashboardResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.home', [], false));

        $aggregatedDashboardResponse->assertOk();
        $aggregatedDashboardResponse->assertSee('عرض جميع الفروع المسموح بها');
        $aggregatedDashboardResponse->assertSee('230.00');

        $salesCreateResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $salesCreateResponse->assertOk();
        $salesCreateResponse->assertSee($branchA->name);

        $salesIndexResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->actingAs($user, 'admin-web')
            ->get(route('sales.index', ['type' => 'simplified'], false));

        $salesIndexResponse->assertOk();
        $salesIndexResponse->assertJsonFragment([
            'bill_number' => $invoiceA->bill_number,
        ]);
        $salesIndexResponse->assertJsonMissing([
            'bill_number' => $invoiceB->bill_number,
        ]);
    }

    private function createAdminUser(array $permissions = []): User
    {
        $branch = $this->createBranch('الفرع الرئيسي');
        $role = $this->createRole('مدير النظام', 'System Admin');

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin-multi-branch@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'is_admin' => false,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function attachPermissions(User $user, array $permissions): void
    {
        $role = $this->createRole('دور مستخدم التشغيل '.$user->id, 'Operations User '.$user->id);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user->assignRole($role);
    }

    private function createRole(string $arabicName, string $englishName): Role
    {
        return Role::create([
            'name' => ['ar' => $arabicName, 'en' => $englishName],
            'guard_name' => 'admin-web',
        ]);
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => 'branch-'.(Branch::query()->count() + 1).'-multi@example.com',
            'phone' => '0550000000',
            'tax_number' => str_pad((string) (Branch::query()->count() + 1), 15, '2', STR_PAD_LEFT),
            'status' => true,
        ]);
    }

    private function createFinancialYear(): FinancialYear
    {
        return FinancialYear::create([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
        ]);
    }

    private function createCustomer(string $name): int
    {
        $accountId = DB::table('accounts')->insertGetId([
            'name' => $name.' الحساب',
            'code' => 'MB-'.rand(1000, 9999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('customers')->insertGetId([
            'name' => $name,
            'phone' => '0553000000',
            'account_id' => $accountId,
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createInvoice(
        FinancialYear $financialYear,
        Branch $branch,
        User $user,
        int $customerId,
        string $date,
        string $time
    ): Invoice {
        return Invoice::create([
            'financial_year' => $financialYear->id,
            'branch_id' => $branch->id,
            'customer_id' => $customerId,
            'type' => 'sale',
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'date' => $date,
            'time' => $time,
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
            'user_id' => $user->id,
        ]);
    }
}
