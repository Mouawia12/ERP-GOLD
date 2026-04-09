<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingSummaryReportsFeatureTest extends TestCase
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

    public function test_summary_report_search_pages_expose_branch_filter(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);

        $this->actingAs($admin, 'admin-web')
            ->get(route('trail_balance.index', [], false))
            ->assertOk()
            ->assertSee('name="branch_ids[]"', false)
            ->assertSee('name="branch_id"', false);

        $this->actingAs($admin, 'admin-web')
            ->get(route('income_statement.index', [], false))
            ->assertOk()
            ->assertSee('name="branch_ids[]"', false)
            ->assertSee('name="branch_id"', false);

        $this->actingAs($admin, 'admin-web')
            ->get(route('balance_sheet.index', [], false))
            ->assertOk()
            ->assertSee('name="branch_ids[]"', false)
            ->assertSee('name="branch_id"', false);
    }

    public function test_trail_balance_respects_branch_filter_and_excludes_other_branch_movements(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);
        $financialYearId = $this->createFinancialYear();

        $otherBranch = $this->createBranch('فرع قيود آخر');
        $accountId = $this->createAccount([
            'name' => ['ar' => 'الصندوق الرئيسي', 'en' => 'Main Cash'],
            'code' => '1101',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $branchJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-00001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 500,
        ]);

        $otherBranchJournalId = $this->insertJournalEntry([
            'serial' => 'J-2-00001',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherBranchJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 700,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('الفرع: ' . $admin->branch->name);
        $response->assertSee('الصندوق الرئيسي');
        $response->assertSee('500.00');
        $response->assertDontSee('700.00');
    }

    public function test_income_statement_respects_branch_filter(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);
        $financialYearId = $this->createFinancialYear();

        $otherBranch = $this->createBranch('فرع أرباح آخر');

        $revenuesId = $this->createAccount([
            'name' => ['ar' => 'الإيرادات', 'en' => 'Revenues'],
            'code' => '4000',
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
        ]);
        $expensesId = $this->createAccount([
            'name' => ['ar' => 'المصروفات', 'en' => 'Expenses'],
            'code' => '5000',
            'account_type' => 'expenses',
            'transfer_side' => 'income_statement',
        ]);

        $branchJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-01001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 900,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $expensesId,
            'document_date' => '2026-03-22',
            'debit' => 300,
        ]);

        $otherBranchJournalId = $this->insertJournalEntry([
            'serial' => 'J-2-01001',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherBranchJournalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 1500,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('income_statement.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('الفرع: ' . $admin->branch->name);
        $response->assertSee('الإيرادات');
        $response->assertSee('المصروفات');
        $response->assertSee('900.00');
        $response->assertSee('300.00');
        $response->assertSee('600.00');
        $response->assertDontSee('1,500.00');
    }

    public function test_balance_sheet_respects_branch_filter(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);
        $financialYearId = $this->createFinancialYear();

        $otherBranch = $this->createBranch('فرع ميزانية آخر');

        $assetsId = $this->createAccount([
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
            'code' => '1000',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $equityId = $this->createAccount([
            'name' => ['ar' => 'حقوق الملكية', 'en' => 'Equity'],
            'code' => '3000',
            'account_type' => 'equity',
            'transfer_side' => 'budget',
        ]);
        $liabilitiesId = $this->createAccount([
            'name' => ['ar' => 'الخصوم', 'en' => 'Liabilities'],
            'code' => '2000',
            'account_type' => 'liabilities',
            'transfer_side' => 'budget',
        ]);

        $branchJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-02001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 1000,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $equityId,
            'document_date' => '2026-03-22',
            'credit' => 400,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $branchJournalId,
            'account_id' => $liabilitiesId,
            'document_date' => '2026-03-22',
            'credit' => 300,
        ]);

        $otherBranchJournalId = $this->insertJournalEntry([
            'serial' => 'J-2-02001',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherBranchJournalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 900,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('الفرع: ' . $admin->branch->name);
        $response->assertSee('الأصول');
        $response->assertSee('حقوق الملكية');
        $response->assertSee('الخصوم');
        $response->assertSee('1,000.00');
        $response->assertSee('400.00');
        $response->assertSee('300.00');
        $response->assertDontSee('900.00');
    }

    public function test_subscriber_primary_account_can_select_multiple_accounting_branches_from_search_and_limit_trail_balance(): void
    {
        [$subscriber, $mainBranch, $primaryUser] = $this->createSubscriberPrimaryUser([
            'employee.accounting_reports.show',
        ]);
        $secondBranch = $this->createSubscriberBranch($subscriber->id, 'فرع محاسبي ثان');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberPrimaryUser([
            'employee.accounting_reports.show',
        ], 'مشترك أجنبي');
        unset($foreignSubscriber);

        $searchResponse = $this->actingAs($primaryUser->fresh(), 'admin-web')
            ->get(route('trail_balance.index', [], false));

        $searchResponse->assertOk();
        $searchResponse->assertSee('name="branch_ids[]"', false);
        $searchResponse->assertSee($mainBranch->getTranslation('name', 'ar'));
        $searchResponse->assertSee($secondBranch->getTranslation('name', 'ar'));
        $searchResponse->assertDontSee($foreignBranch->getTranslation('name', 'ar'));

        $financialYearId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'الصندوق متعدد الفروع', 'en' => 'Multi Branch Cash'],
            'code' => '1109',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $mainJournalId = $this->insertJournalEntry([
            'serial' => 'J-PR-1001',
            'financial_year' => $financialYearId,
            'branch_id' => $mainBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $mainJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-24',
            'debit' => 400,
        ]);

        $secondJournalId = $this->insertJournalEntry([
            'serial' => 'J-PR-1002',
            'financial_year' => $financialYearId,
            'branch_id' => $secondBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $secondJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-24',
            'debit' => 650,
        ]);

        $foreignJournalId = $this->insertJournalEntry([
            'serial' => 'J-PR-1999',
            'financial_year' => $financialYearId,
            'branch_id' => $foreignBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $foreignJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-24',
            'debit' => 999,
        ]);

        $allBranchesResponse = $this->actingAs($primaryUser->fresh(), 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-24',
                'date_to' => '2026-03-24',
            ]);

        $allBranchesResponse->assertOk();
        $allBranchesResponse->assertSee('الصندوق متعدد الفروع');
        $allBranchesResponse->assertSee('1,050.00');
        $allBranchesResponse->assertDontSee('999.00');

        $selectedBranchResponse = $this->actingAs($primaryUser->fresh(), 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-24',
                'date_to' => '2026-03-24',
                'branch_ids' => [$secondBranch->id],
            ]);

        $selectedBranchResponse->assertOk();
        $selectedBranchResponse->assertSee('الفرع: '.$secondBranch->getTranslation('name', 'ar'));
        $selectedBranchResponse->assertSee('الصندوق متعدد الفروع');
        $selectedBranchResponse->assertSee('650.00');
        $selectedBranchResponse->assertDontSee('1,050.00');
        $selectedBranchResponse->assertDontSee('999.00');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions): User
    {
        $branch = $this->createBranch('فرع التقارير المحاسبية');

        $role = Role::create([
            'name' => ['ar' => 'مدير التقارير', 'en' => 'Reports Admin'],
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
            'name' => 'Summary Reports Admin',
            'email' => 'summary-report-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0:Subscriber,1:Branch,2:User}
     */
    private function createSubscriberPrimaryUser(array $permissions, string $subscriberName = 'مشترك محاسبي'): array
    {
        $subscriber = Subscriber::create([
            'name' => $subscriberName,
            'login_email' => uniqid('summary-subscriber-', true).'@example.com',
            'status' => true,
        ]);

        $branch = $this->createSubscriberBranch($subscriber->id, 'الفرع الرئيسي '.$subscriberName);

        $role = Role::create([
            'name' => ['ar' => 'دور '.$subscriberName, 'en' => 'Role '.$subscriberName],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'subscriber_id' => $subscriber->id,
            'name' => 'مدير '.$subscriberName,
            'email' => uniqid('summary-primary-user-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        $subscriber->update([
            'admin_user_id' => $user->id,
        ]);

        return [$subscriber->fresh(), $branch, $user->fresh()];
    }

    private function createSubscriberBranch(int $subscriberId, string $name): Branch
    {
        return Branch::create([
            'subscriber_id' => $subscriberId,
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createFinancialYear(): int
    {
        return DB::table('financial_years')->insertGetId([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(array $attributes): int
    {
        $subscriberId = auth('admin-web')->user()?->subscriber_id;

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode(['ar' => 'حساب', 'en' => 'Account'], JSON_UNESCAPED_UNICODE),
            'code' => '1000',
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'subscriber_id' => $subscriberId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ], [
            'name' => json_encode($attributes['name'], JSON_UNESCAPED_UNICODE),
        ], collect($attributes)->except('name')->all()));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertJournalEntry(array $attributes): int
    {
        return DB::table('journal_entries')->insertGetId(array_merge([
            'serial' => null,
            'journal_date' => '2026-03-22',
            'notes' => null,
            'financial_year' => null,
            'branch_id' => null,
            'journalable_type' => null,
            'journalable_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertJournalEntryDocument(array $attributes): int
    {
        return DB::table('journal_entry_documents')->insertGetId(array_merge([
            'journal_id' => null,
            'account_id' => null,
            'document_date' => '2026-03-22',
            'credit' => 0,
            'debit' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }
}
