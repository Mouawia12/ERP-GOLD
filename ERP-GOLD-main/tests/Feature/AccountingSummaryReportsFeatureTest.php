<?php

namespace Tests\Feature;

use App\Models\Account;
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

    public function test_balance_sheet_hides_other_branch_account_left_with_zero_balance(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        $otherBranch = $this->createBranch('فرع المرقب الكبير');
        $admin->branches()->sync([$admin->branch_id, $otherBranch->id]);
        $admin = $admin->fresh();

        [$assetsId] = $this->createBalanceSheetRoots();

        $theirs = $this->createAccount([
            'name' => ['ar' => 'صندوق المرقب الكبير', 'en' => 'Other Branch Cash'],
            'code' => '1002',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $mine = $this->createAccount([
            'name' => ['ar' => 'صندوق العويس', 'en' => 'Owais Cash'],
            'code' => '1003',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // حركة الفرع سُجّلت أولًا على صندوق الفرع الآخر…
        $saleJournal = $this->insertJournalEntry([
            'serial' => 'J-BR-MINE',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $saleJournal,
            'account_id' => $theirs,
            'document_date' => '2026-03-22',
            'debit' => 700,
        ]);

        // …ثم رحّلها قيد تسوية إلى صندوق الفرع نفسه، فبقي الأول بحركة ورصيد صفر.
        $reclassJournal = $this->insertJournalEntry([
            'serial' => 'MJ-BR-FIX',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $reclassJournal,
            'account_id' => $theirs,
            'document_date' => '2026-03-22',
            'credit' => 700,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $reclassJournal,
            'account_id' => $mine,
            'document_date' => '2026-03-22',
            'debit' => 700,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_ids' => [$admin->branch_id],
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk();

        $response->assertSee('صندوق العويس');
        $response->assertDontSee('صندوق المرقب الكبير');
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

    public function test_account_childrens_ids_does_not_fail_when_account_key_is_not_loaded(): void
    {
        $this->createAccount([
            'name' => ['ar' => 'حساب بدون مفتاح محمل', 'en' => 'Partially Loaded Account'],
            'code' => '1199',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $account = Account::query()
            ->withoutGlobalScopes()
            ->select('name', 'code')
            ->firstOrFail();

        $this->assertSame([], $account->childrensIds);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function test_balance_sheet_lists_liabilities_before_equity(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);
        $financialYearId = $this->createFinancialYear();

        $assetsId = $this->createAccount([
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
            'code' => '1000',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        // يُنشأ حقوق الملكية أولًا عمدًا: الترتيب في التقرير يجب ألا يتبع ترتيب
        // الإنشاء ولا الكود، بل ترتيب الميزانية المحاسبي.
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

        $journalId = $this->insertJournalEntry([
            'serial' => 'J-ORDER-1',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 1000,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $equityId,
            'document_date' => '2026-03-22',
            'credit' => 400,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $liabilitiesId,
            'document_date' => '2026-03-22',
            'credit' => 600,
        ]);

        $content = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk()
            ->getContent();

        $assetsAt = strpos($content, 'الأصول');
        $liabilitiesAt = strpos($content, 'الخصوم');
        $equityAt = strpos($content, 'حقوق الملكية');

        $this->assertNotFalse($assetsAt);
        $this->assertNotFalse($liabilitiesAt);
        $this->assertNotFalse($equityAt);

        $this->assertLessThan($liabilitiesAt, $assetsAt, 'الأصول يجب أن تسبق الخصوم.');
        $this->assertLessThan($equityAt, $liabilitiesAt, 'الخصوم يجب أن تسبق حقوق الملكية.');
    }

    public function test_branch_filter_shows_the_names_of_the_branches_picked(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);
        $secondBranch = $this->createBranch('فرع ثانٍ للفلتر');
        // القائمة المتعددة لا تُعرض إلا لمن يرى أكثر من فرع.
        $admin->branches()->sync([$admin->branch_id, $secondBranch->id]);

        $content = $this->actingAs($admin->fresh(), 'admin-web')
            ->get(route('balance_sheet.index', [], false))
            ->assertOk()
            ->getContent();

        // «values» تسرد أسماء الفروع المختارة؛ «count > 2» كانت تستبدلها بعبارة
        // إنجليزية «items selected 3» فلا يعرف المستخدم ما اختار.
        $this->assertStringContainsString('data-selected-text-format="values"', $content);
        $this->assertStringNotContainsString('data-selected-text-format="count', $content);
    }

    public function test_the_selected_branch_text_is_not_painted_white(): void
    {
        $css = file_get_contents(public_path('assets/css-rtl/bootstrap-select.css'));

        $this->assertIsString($css);

        $rule = substr(
            $css,
            (int) strpos($css, '.filter-option-inner-inner {'),
            220
        );

        // كان `color: #fff` يجعل نص الاختيار أبيض على زر أبيض فيبدو الحقل فارغًا
        // رغم أن الفرع مختار فعلًا.
        $this->assertStringNotContainsString('color: #fff', $rule);
        $this->assertStringContainsString('color: inherit', $rule);
    }

    public function test_balance_sheet_profit_satisfies_the_accounting_equation(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();

        [$assetsId, $liabilitiesId, $equityId] = $this->createBalanceSheetRoots();

        // أصول 1,000 مدين | خصوم 200 دائن | حقوق ملكية 300 دائن
        // 1,000 = 200 + 300 + 500  →  صافي الربح 500
        $this->postBalanceSheetMovements($admin, $financialYearId, [
            [$assetsId, 'debit', 1000],
            [$liabilitiesId, 'credit', 200],
            [$equityId, 'credit', 300],
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk()
            ->assertViewHas('profitTotal', 500.0);
    }

    public function test_balance_sheet_profit_holds_when_equity_carries_a_debit_balance(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();

        [$assetsId, $liabilitiesId, $equityId] = $this->createBalanceSheetRoots();

        // خسائر مبقاة تجعل حقوق الملكية مدينة 300.
        // 1,000 = 200 + (-300) + 1,100  →  صافي الربح 1,100
        // القيمة المطلقة كانت تعطي 500 لأنها تقلب إشارة حقوق الملكية.
        $this->postBalanceSheetMovements($admin, $financialYearId, [
            [$assetsId, 'debit', 1000],
            [$liabilitiesId, 'credit', 200],
            [$equityId, 'debit', 300],
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk()
            ->assertViewHas('profitTotal', 1100.0);
    }

    /**
     * نتيجة الفترة ما زالت في الإيرادات والمصروفات ولم تُقفل، فتُعرض في
     * الميزانية على «حساب الربح أو الخسارة» داخل حقوق الملكية بدل أن تتدلّى
     * سطرًا وحدها أسفل التقرير.
     */
    public function test_balance_sheet_carries_the_period_result_into_the_profit_and_loss_account(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        [$assetsId, , $equityId, $profitAndLossId, $revenuesId] = $this->createChartWithProfitAndLossAccount();

        // بيع بـ 1,000: نقدية مدينة وإيراد دائن ⇒ ربح الفترة 1,000 دائن
        $journalId = $this->insertJournalEntry([
            'serial' => 'J-PL-1',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 1000,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 1000,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();

        $metrics = $response->viewData('accountMetrics');

        // الربح دائن: يزيد حقوق الملكية، ويظهر على حساب الربح أو الخسارة
        $this->assertSame(1000.0, $metrics[$profitAndLossId]['closing_credit']);
        $this->assertSame(-1000.0, $metrics[$profitAndLossId]['closing_net']);
        $this->assertSame(1000.0, $metrics[$equityId]['closing_credit']);
        $this->assertSame(-1000.0, $metrics[$equityId]['closing_net']);
    }

    /**
     * الشجرة القديمة تجعل الكود «31» رأسَ المال، فاستدلالٌ بالكود كان يحمّل
     * نتيجة الفترة عليه. رأس المال يبقى كما هو مهما ربحت المنشأة أو خسرت.
     */
    public function test_period_result_never_lands_on_the_capital_account(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        [$assetsId, , , , $revenuesId, $capitalId] = $this->createChartWithProfitAndLossAccount();

        $journalId = $this->insertJournalEntry([
            'serial' => 'J-PL-3',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 1000,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 1000,
        ]);

        $metrics = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk()
            ->viewData('accountMetrics');

        $this->assertSame(0.0, $metrics[$capitalId]['closing_debit']);
        $this->assertSame(0.0, $metrics[$capitalId]['closing_credit']);
        $this->assertSame(0.0, $metrics[$capitalId]['closing_net']);
    }

    /**
     * وبعد حمل النتيجة على حقوق الملكية يتساوى إجمالي المدين وإجمالي الدائن،
     * وهو ما يقرأه المحاسب ليطمئن أن الميزانية متوازنة.
     */
    public function test_balance_sheet_totals_show_matching_debit_and_credit(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        [$assetsId, , , , $revenuesId] = $this->createChartWithProfitAndLossAccount();

        $journalId = $this->insertJournalEntry([
            'serial' => 'J-PL-2',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $assetsId,
            'document_date' => '2026-03-22',
            'debit' => 1000,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 1000,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();

        $totals = $response->viewData('totals');

        $this->assertSame(1000.0, $totals['debit']);
        $this->assertSame(1000.0, $totals['credit']);
        $response->assertSee('الإجمالي');
    }

    /**
     * شجرة الميزانية بأكوادها المعتمدة: حقوق الملكية «3» وتحته «31» حساب
     * الربح أو الخسارة، كما ينشئها SubscriberChartProvisioner.
     *
     * @return array{0:int,1:int,2:int,3:int,4:int,5:int}
     */
    private function createChartWithProfitAndLossAccount(): array
    {
        $assetsId = $this->createAccount([
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
            'code' => '1',
            'level' => '1',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $liabilitiesId = $this->createAccount([
            'name' => ['ar' => 'الخصوم', 'en' => 'Liabilities'],
            'code' => '2',
            'level' => '1',
            'account_type' => 'liabilities',
            'transfer_side' => 'budget',
        ]);
        $equityId = $this->createAccount([
            'name' => ['ar' => 'حقوق الملكية', 'en' => 'Equity'],
            'code' => '3',
            'level' => '1',
            'account_type' => 'equity',
            'transfer_side' => 'budget',
        ]);
        // شجرة الحسابات القديمة تجعل «31» رأسَ المال و«33» الربح أو الخسارة،
        // فتُبنى هنا كذلك ليثبت أن النتيجة لا تُحمَّل على رأس المال.
        $capitalId = $this->createAccount([
            'name' => ['ar' => 'رأس المال', 'en' => 'Capital'],
            'code' => '31',
            'level' => '2',
            'parent_account_id' => $equityId,
            'account_type' => 'equity',
            'transfer_side' => 'budget',
        ]);
        $profitAndLossId = $this->createAccount([
            'name' => ['ar' => 'حساب الربح أو الخسارة', 'en' => 'Profit and Loss Account'],
            'code' => '33',
            'level' => '2',
            'parent_account_id' => $equityId,
            'account_type' => 'equity',
            'transfer_side' => 'budget',
        ]);
        $netProfitId = $this->createAccount([
            'name' => ['ar' => 'صافي الربح', 'en' => 'Net Profit'],
            'code' => '3301',
            'level' => '3',
            'parent_account_id' => $profitAndLossId,
            'account_type' => 'equity',
            'transfer_side' => 'budget',
        ]);
        $revenuesId = $this->createAccount([
            'name' => ['ar' => 'الإيرادات', 'en' => 'Revenues'],
            'code' => '4',
            'level' => '1',
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
        ]);
        $this->createAccount([
            'name' => ['ar' => 'المصروفات', 'en' => 'Expenses'],
            'code' => '5',
            'level' => '1',
            'account_type' => 'expenses',
            'transfer_side' => 'income_statement',
        ]);

        \App\Models\AccountSetting::query()->create([
            'branch_id' => auth('admin-web')->user()?->branch_id,
            'profit_account' => $netProfitId,
        ]);

        return [$assetsId, $liabilitiesId, $equityId, $profitAndLossId, $revenuesId, $capitalId];
    }

    /**
     * @return array<int, int>
     */
    private function createBalanceSheetRoots(): array
    {
        return [
            $this->createAccount([
                'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
                'code' => '1000',
                'account_type' => 'assets',
                'transfer_side' => 'budget',
            ]),
            $this->createAccount([
                'name' => ['ar' => 'الخصوم', 'en' => 'Liabilities'],
                'code' => '2000',
                'account_type' => 'liabilities',
                'transfer_side' => 'budget',
            ]),
            $this->createAccount([
                'name' => ['ar' => 'حقوق الملكية', 'en' => 'Equity'],
                'code' => '3000',
                'account_type' => 'equity',
                'transfer_side' => 'budget',
            ]),
        ];
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: float}>  $lines
     */
    private function postBalanceSheetMovements(User $admin, int $financialYearId, array $lines): void
    {
        $journalId = $this->insertJournalEntry([
            'serial' => 'J-EQ-' . uniqid(),
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);

        foreach ($lines as [$accountId, $side, $amount]) {
            $this->insertJournalEntryDocument([
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'document_date' => '2026-03-22',
                $side => $amount,
            ]);
        }
    }

    public function test_balance_sheet_hides_accounts_that_have_no_movement_in_the_chosen_branch(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        $otherBranch = $this->createBranch('فرع المرقب الكبير');
        // اختيار فرع لا يُعدّ تضييقًا إلا إذا كان المستخدم يرى أكثر من فرع.
        $admin->branches()->sync([$admin->branch_id, $otherBranch->id]);
        $admin = $admin->fresh();

        [$assetsId, $liabilitiesId, $equityId] = $this->createBalanceSheetRoots();

        $mine = $this->createAccount([
            'name' => ['ar' => 'مصروفات زاتكا العويس', 'en' => 'Owais Expenses'],
            'code' => '1001',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $theirs = $this->createAccount([
            'name' => ['ar' => 'صندوق المرقب الكبير', 'en' => 'Other Branch Cash'],
            'code' => '1002',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // حركة في فرع المستخدم فقط
        $mineJournal = $this->insertJournalEntry([
            'serial' => 'J-BR-MINE',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $mineJournal,
            'account_id' => $mine,
            'document_date' => '2026-03-22',
            'debit' => 700,
        ]);

        // وحركة في فرع آخر على حساب آخر
        $otherJournal = $this->insertJournalEntry([
            'serial' => 'J-BR-OTHER',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherJournal,
            'account_id' => $theirs,
            'document_date' => '2026-03-22',
            'debit' => 900,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'branch_ids' => [$admin->branch_id],
                'branch_id' => $admin->branch_id,
            ])
            ->assertOk();

        $response->assertSee('مصروفات زاتكا العويس');
        $response->assertDontSee('صندوق المرقب الكبير');
        $response->assertDontSee('900.00');

        // الأقسام الرئيسية تبقى ظاهرة حتى لو خلت من الحركة في هذا الفرع.
        $response->assertSee('الخصوم');
        $response->assertSee('حقوق الملكية');
    }

    public function test_balance_sheet_keeps_every_branch_account_when_no_branch_is_chosen(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();
        $otherBranch = $this->createBranch('فرع المرقب الكبير');

        [$assetsId] = $this->createBalanceSheetRoots();

        $theirs = $this->createAccount([
            'name' => ['ar' => 'صندوق المرقب الكبير', 'en' => 'Other Branch Cash'],
            'code' => '1002',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $otherJournal = $this->insertJournalEntry([
            'serial' => 'J-ALL-BR',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherJournal,
            'account_id' => $theirs,
            'document_date' => '2026-03-22',
            'debit' => 900,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('balance_sheet.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
            ])
            ->assertOk()
            ->assertSee('صندوق المرقب الكبير');
    }

    public function test_income_statement_offers_and_honours_an_account_level_filter(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $financialYearId = $this->createFinancialYear();

        $revenuesId = $this->createAccount([
            'name' => ['ar' => 'الإيرادات', 'en' => 'Revenues'],
            'code' => '4000',
            'level' => '1',
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
        ]);
        $expensesId = $this->createAccount([
            'name' => ['ar' => 'المصروفات', 'en' => 'Expenses'],
            'code' => '5000',
            'level' => '1',
            'account_type' => 'expenses',
            'transfer_side' => 'income_statement',
        ]);
        $expensesChildId = $this->createAccount([
            'name' => ['ar' => 'مصروفات عمومية', 'en' => 'General Expenses'],
            'code' => '5100',
            'level' => '2',
            'parent_account_id' => $expensesId,
            'account_type' => 'expenses',
            'transfer_side' => 'income_statement',
        ]);
        $this->createAccount([
            'name' => ['ar' => 'كهرباء الفرع', 'en' => 'Branch Power'],
            'code' => '5101',
            'level' => '3',
            'parent_account_id' => $expensesChildId,
            'account_type' => 'expenses',
            'transfer_side' => 'income_statement',
        ]);

        $journalId = $this->insertJournalEntry([
            'serial' => 'J-LVL-1',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $journalId,
            'account_id' => $revenuesId,
            'document_date' => '2026-03-22',
            'credit' => 900,
        ]);

        // الشاشة تعرض الحقل أصلًا
        $this->actingAs($admin, 'admin-web')
            ->get(route('income_statement.index', [], false))
            ->assertOk()
            ->assertSee('name="account_level"', false)
            ->assertSee('مستوى الحساب');

        $payload = [
            'date_from' => '2026-03-22',
            'date_to' => '2026-03-22',
            'branch_id' => $admin->branch_id,
        ];

        // «حتى مستوى 2» يوقف النزول قبل المستوى الثالث
        $this->actingAs($admin, 'admin-web')
            ->post(route('income_statement.search', [], false), $payload + ['account_level' => 2])
            ->assertOk()
            ->assertSee('مصروفات عمومية')
            ->assertDontSee('كهرباء الفرع');

        // بلا مستوى تُعرض كل الشجرة
        $this->actingAs($admin, 'admin-web')
            ->post(route('income_statement.search', [], false), $payload)
            ->assertOk()
            ->assertSee('كهرباء الفرع');
    }

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
