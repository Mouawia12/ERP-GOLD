<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Reports\CostCenterReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * قائمة الدخل موزّعة على الفروع: صف لكل حساب، عمود لكل فرع، وصافي ربح لكل فرع.
 */
class CostCenterReportFeatureTest extends TestCase
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

    public function test_each_branch_gets_its_own_column_and_net_profit(): void
    {
        [$admin, $other] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        [$revenues, $expenses, $sales, $rent] = $this->incomeStatementTree();

        // فرع المستخدم: مبيعات 1,000 ومصروف 300 → ربح 700
        $this->postEntry($admin->branch_id, [[$sales, 'credit', 1000], [$rent, 'debit', 300]]);
        // الفرع الآخر: مبيعات 400 ومصروف 500 → خسارة 100
        $this->postEntry($other->id, [[$sales, 'credit', 400], [$rent, 'debit', 500]]);

        $report = app(CostCenterReportBuilder::class)
            ->build([$admin->branch_id, $other->id], '2026-01-01', '2026-12-31');

        $this->assertSame(700.0, $report['net_by_branch'][$admin->branch_id]);
        $this->assertSame(-100.0, $report['net_by_branch'][$other->id]);
        $this->assertSame(600.0, $report['grand_net']);
    }

    public function test_a_parent_row_carries_the_sum_of_its_children(): void
    {
        [$admin] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        [$revenues, $expenses, $sales, $rent] = $this->incomeStatementTree();

        $power = $this->createAccount([
            'code' => '5102',
            'level' => '2',
            'parent_account_id' => $expenses,
            'account_type' => 'expenses',
            'name' => ['ar' => 'كهرباء', 'en' => 'Power'],
        ]);

        $this->postEntry($admin->branch_id, [[$rent, 'debit', 300], [$power, 'debit', 200]]);

        $report = app(CostCenterReportBuilder::class)
            ->build([$admin->branch_id], '2026-01-01', '2026-12-31');

        $expenseSection = collect($report['sections'])->firstWhere('account_type', 'expenses');
        $rows = collect($expenseSection['rows'])->keyBy('id');

        $this->assertSame(500.0, $rows[$expenses]['values'][$admin->branch_id]);
        $this->assertSame(300.0, $rows[$rent]['values'][$admin->branch_id]);
        $this->assertSame(200.0, $rows[$power]['values'][$admin->branch_id]);
    }

    public function test_revenue_reads_positive_and_expense_reads_positive(): void
    {
        [$admin] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        [$revenues, $expenses, $sales, $rent] = $this->incomeStatementTree();

        $this->postEntry($admin->branch_id, [[$sales, 'credit', 1000], [$rent, 'debit', 300]]);

        $report = app(CostCenterReportBuilder::class)
            ->build([$admin->branch_id], '2026-01-01', '2026-12-31');

        // كل قسم في اتجاهه الطبيعي، فلا يقرأ المحاسب إشارات سالبة بلا داع
        $this->assertSame(1000.0, $report['branch_totals']['revenues'][$admin->branch_id]);
        $this->assertSame(300.0, $report['branch_totals']['expenses'][$admin->branch_id]);
    }

    public function test_the_account_level_hides_depth_without_changing_totals(): void
    {
        [$admin] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        [$revenues, $expenses, $sales, $rent] = $this->incomeStatementTree();

        $detail = $this->createAccount([
            'code' => '510101',
            'level' => '3',
            'parent_account_id' => $rent,
            'account_type' => 'expenses',
            'name' => ['ar' => 'ايجار المستودع', 'en' => 'Warehouse Rent'],
        ]);

        $this->postEntry($admin->branch_id, [[$detail, 'debit', 400], [$sales, 'credit', 400]]);

        $full = app(CostCenterReportBuilder::class)->build([$admin->branch_id], '2026-01-01', '2026-12-31');
        $capped = app(CostCenterReportBuilder::class)->build([$admin->branch_id], '2026-01-01', '2026-12-31', 2);

        $names = fn (array $r) => collect($r['sections'])
            ->flatMap(fn ($section) => collect($section['rows'])->pluck('name'))
            ->all();

        $this->assertContains('ايجار المستودع', $names($full));
        $this->assertNotContains('ايجار المستودع', $names($capped));

        // المجموع لا يتأثر: الحساب المخفي دخل في مجاميع آبائه
        $this->assertSame(
            $full['net_by_branch'][$admin->branch_id],
            $capped['net_by_branch'][$admin->branch_id]
        );
        $this->assertSame(0.0, $capped['net_by_branch'][$admin->branch_id]);
    }

    public function test_another_branchs_movement_never_lands_in_this_branchs_column(): void
    {
        [$admin, $other] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        [$revenues, $expenses, $sales, $rent] = $this->incomeStatementTree();

        $this->postEntry($other->id, [[$sales, 'credit', 900], [$rent, 'debit', 100]]);

        $report = app(CostCenterReportBuilder::class)
            ->build([$admin->branch_id, $other->id], '2026-01-01', '2026-12-31');

        $this->assertSame(0.0, $report['net_by_branch'][$admin->branch_id]);
        $this->assertSame(800.0, $report['net_by_branch'][$other->id]);
    }

    public function test_the_screen_and_the_report_are_reachable(): void
    {
        [$admin] = $this->twoBranchAdmin();
        $this->createFinancialYear();
        $this->incomeStatementTree();

        $this->actingAs($admin, 'admin-web')
            ->get(route('cost_centers.index', [], false))
            ->assertOk()
            ->assertSee('مراكز التكلفة');

        $this->actingAs($admin, 'admin-web')
            ->post(route('cost_centers.search', [], false), [
                'date_from' => '2026-01-01',
                'date_to' => '2026-12-31',
            ])
            ->assertOk()
            ->assertSee('صافي الربح');
    }

    /**
     * @return array{0: User, 1: Branch}
     */
    private function twoBranchAdmin(): array
    {
        $branch = Branch::create(['name' => ['ar' => 'زاتكا العويس', 'en' => 'A'], 'phone' => '111']);
        $other = Branch::create(['name' => ['ar' => 'فرع المرقب', 'en' => 'B'], 'phone' => '222']);

        $role = Role::create(['name' => ['ar' => 'مدير التقارير', 'en' => 'R'], 'guard_name' => 'admin-web']);
        $role->givePermissionTo(Permission::findOrCreate('employee.accounting_reports.show', 'admin-web'));

        $user = User::create([
            'name' => 'Admin',
            'email' => 'cost-centers@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
        $user->assignRole($role);
        $user->branches()->sync([$branch->id, $other->id]);

        return [$user->fresh(), $other];
    }

    /**
     * @return array<int, int>  [revenues, expenses, sales, rent]
     */
    private function incomeStatementTree(): array
    {
        $revenues = $this->createAccount([
            'code' => '4', 'level' => '1', 'account_type' => 'revenues',
            'name' => ['ar' => 'الإيرادات', 'en' => 'Revenues'],
        ]);
        $expenses = $this->createAccount([
            'code' => '5', 'level' => '1', 'account_type' => 'expenses',
            'name' => ['ar' => 'المصروفات', 'en' => 'Expenses'],
        ]);
        $sales = $this->createAccount([
            'code' => '41', 'level' => '2', 'parent_account_id' => $revenues,
            'account_type' => 'revenues', 'name' => ['ar' => 'المبيعات', 'en' => 'Sales'],
        ]);
        $rent = $this->createAccount([
            'code' => '51', 'level' => '2', 'parent_account_id' => $expenses,
            'account_type' => 'expenses', 'name' => ['ar' => 'ايجارات', 'en' => 'Rent'],
        ]);

        return [$revenues, $expenses, $sales, $rent];
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: float}>  $lines
     */
    private function postEntry(int $branchId, array $lines): void
    {
        $journalId = DB::table('journal_entries')->insertGetId([
            'serial' => 'CC-' . uniqid(),
            'journal_date' => '2026-03-22',
            'financial_year' => DB::table('financial_years')->value('id'),
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($lines as [$accountId, $side, $amount]) {
            DB::table('journal_entry_documents')->insert([
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'document_date' => '2026-03-22',
                'debit' => $side === 'debit' ? $amount : 0,
                'credit' => $side === 'credit' ? $amount : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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
    private function createAccount(array $attributes = []): int
    {
        $name = $attributes['name'] ?? ['ar' => 'حساب', 'en' => 'Account'];
        unset($attributes['name']);

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
            'code' => '4000',
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
