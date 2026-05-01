<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrailBalanceCalculationTest extends TestCase
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

    /**
     * A journal document dated before period_from must appear as opening debit,
     * not as period debit. The view renders each column separately, so we verify
     * the arithmetic: opening(300) + period(500) = closing(800).
     */
    public function test_before_period_movement_classified_as_opening_not_period(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'صندوق رئيسي', 'en' => 'Main Cash'],
            'code' => '1101',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Before period — must be classified as opening
        $jBefore = $this->insertJournalEntry(['serial' => 'JB-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id, 'journal_date' => '2026-01-10']);
        $this->insertJournalEntryDocument(['journal_id' => $jBefore, 'account_id' => $accountId, 'document_date' => '2026-01-10', 'debit' => 300]);

        // Inside period — must be classified as period
        $jIn = $this->insertJournalEntry(['serial' => 'JI-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id, 'journal_date' => '2026-02-15']);
        $this->insertJournalEntryDocument(['journal_id' => $jIn, 'account_id' => $accountId, 'document_date' => '2026-02-15', 'debit' => 500]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-02-01',
                'date_to' => '2026-02-28',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('صندوق رئيسي');
        $response->assertSee('300.00'); // opening debit
        $response->assertSee('500.00'); // period debit
        $response->assertSee('800.00'); // closing = opening + period (arithmetic proof)
    }

    /**
     * A document exactly on period_from is inside the period (between inclusive).
     */
    public function test_period_boundary_document_on_start_date_is_period_movement(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب الحدود', 'en' => 'Boundary Account'],
            'code' => '1102',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Exactly on period_from — must be period movement (not opening)
        $j = $this->insertJournalEntry(['serial' => 'JBD-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id, 'journal_date' => '2026-03-01']);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $accountId, 'document_date' => '2026-03-01', 'debit' => 750]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('حساب الحدود');
        $response->assertSee('750.00'); // period debit
        // closing = 0(opening) + 750(period) = 750 — same value, no ambiguity needed here
        // The important assertion: opening_debit column should be 0.00
        // We verify by checking that NO second occurrence of 750 exists as "opening" via closing arithmetic:
        // if 750 were opening, closing = 750 + 750 = 1,500.00
        $response->assertDontSee('1,500.00');
    }

    /**
     * A document after period_to must not appear at all (neither opening nor period).
     */
    public function test_after_period_movement_is_excluded_entirely(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب مستقبلي', 'en' => 'Future Account'],
            'code' => '1103',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $j = $this->insertJournalEntry(['serial' => 'JF-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id, 'journal_date' => '2026-05-01']);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $accountId, 'document_date' => '2026-05-01', 'debit' => 888]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        // Account has zero activity in or before the period → excluded from results
        $response->assertDontSee('حساب مستقبلي');
        $response->assertDontSee('888.00');
    }

    /**
     * When no branch filter is applied (selects_all = true), the opening_balances
     * table entries are added to the opening balance column.
     */
    public function test_opening_balances_table_included_when_all_branches_selected(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $otherBranch = $this->createBranch('فرع ثاني للميزان');
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب أرصدة افتتاحية', 'en' => 'Opening Balance Account'],
            'code' => '1201',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $this->insertOpeningBalance(['financial_year' => $fyId, 'account_id' => $accountId, 'debit' => 2000]);

        // POST without branch filter → selects_all = true → opening_balances included
        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]);

        $response->assertOk();
        $response->assertSee('حساب أرصدة افتتاحية');
        $response->assertSee('2,000.00'); // from opening_balances table
    }

    /**
     * When a specific branch is selected (selects_all = false), opening_balances
     * table entries are NOT included — only journal documents count.
     *
     * The user needs access to multiple visible branches so that selecting just
     * one produces selects_all=false → branch_scope_all=false. We achieve this
     * by inserting both branches into the branch_user pivot for the test user.
     */
    public function test_opening_balances_table_excluded_for_specific_branch_filter(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);

        // Give the user access to a second branch so selects_all becomes false when one is chosen
        $secondBranch = $this->createBranch('فرع ثاني للاستثناء');
        $this->grantBranchAccess($admin, $secondBranch->id);

        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب بدون افتتاحية فرع', 'en' => 'No Branch Opening Account'],
            'code' => '1202',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Put a large opening balance in the table — should be invisible when filtering by branch
        $this->insertOpeningBalance(['financial_year' => $fyId, 'account_id' => $accountId, 'debit' => 5000]);

        // User sees 2 branches, selects only admin's branch → selects_all=false → branch_scope_all=false
        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        // Account has no journal docs → zero activity under branch filter → excluded entirely
        $response->assertDontSee('حساب بدون افتتاحية فرع');
        $response->assertDontSee('5,000.00');
    }

    /**
     * Closing balance arithmetic: closing_debit = opening_debit + period_debit.
     */
    public function test_closing_debit_equals_opening_plus_period_debit(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب الحسابية', 'en' => 'Arithmetic Account'],
            'code' => '1301',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Before period: debit 200
        $jBefore = $this->insertJournalEntry(['serial' => 'JARITH-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jBefore, 'account_id' => $accountId, 'document_date' => '2026-01-15', 'debit' => 200]);

        // In period: debit 150
        $jIn = $this->insertJournalEntry(['serial' => 'JARITH-002', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jIn, 'account_id' => $accountId, 'document_date' => '2026-03-10', 'debit' => 150]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('200.00'); // opening_debit
        $response->assertSee('150.00'); // period_debit
        $response->assertSee('350.00'); // closing_debit = 200 + 150
    }

    /**
     * An account with zero opening and zero period activity must not appear in results.
     */
    public function test_zero_activity_account_excluded_from_results(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);

        // Account with no movements at all
        $this->createAccount([
            'name' => ['ar' => 'حساب صفري', 'en' => 'Zero Account'],
            'code' => '1401',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Active account so the response is not empty
        $fyId = $this->createFinancialYear();
        $activeId = $this->createAccount([
            'name' => ['ar' => 'حساب نشط', 'en' => 'Active Account'],
            'code' => '1402',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $j = $this->insertJournalEntry(['serial' => 'JACT-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $activeId, 'document_date' => '2026-03-05', 'debit' => 100]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('حساب نشط');
        $response->assertDontSee('حساب صفري');
    }

    /**
     * When closing_net > 0 (debit side), the view appends " / مدين" next to the amount.
     */
    public function test_positive_closing_net_shows_debit_direction_label(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب مدين', 'en' => 'Debit Account'],
            'code' => '1501',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // debit > credit → closing_net positive → مدين
        $j = $this->insertJournalEntry(['serial' => 'JDEBIT-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $accountId, 'document_date' => '2026-03-05', 'debit' => 600, 'credit' => 200]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('مدين'); // direction label for closing_net > 0
    }

    /**
     * When closing_net < 0 (credit side), the view appends " / دائن" next to the amount.
     */
    public function test_negative_closing_net_shows_credit_direction_label(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب دائن', 'en' => 'Credit Account'],
            'code' => '1601',
            'account_type' => 'liabilities',
            'transfer_side' => 'budget',
        ]);

        // credit > debit → closing_net negative → دائن
        $j = $this->insertJournalEntry(['serial' => 'JCREDIT-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $accountId, 'document_date' => '2026-03-05', 'debit' => 100, 'credit' => 700]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('دائن'); // direction label for closing_net < 0
    }

    /**
     * The tfoot "اجمالي الميزان" row must be present and its total equals the sum
     * of all account closing debits.
     */
    public function test_total_row_appears_and_sums_accounts(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();

        $acc1 = $this->createAccount(['name' => ['ar' => 'حساب أول', 'en' => 'Account One'], 'code' => '1701', 'account_type' => 'assets', 'transfer_side' => 'budget']);
        $acc2 = $this->createAccount(['name' => ['ar' => 'حساب ثاني', 'en' => 'Account Two'], 'code' => '1702', 'account_type' => 'assets', 'transfer_side' => 'budget']);

        $j1 = $this->insertJournalEntry(['serial' => 'JTOT-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j1, 'account_id' => $acc1, 'document_date' => '2026-03-05', 'debit' => 300]);

        $j2 = $this->insertJournalEntry(['serial' => 'JTOT-002', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j2, 'account_id' => $acc2, 'document_date' => '2026-03-08', 'debit' => 450]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('اجمالي الميزان');
        $response->assertSee('300.00');
        $response->assertSee('450.00');
        $response->assertSee('750.00'); // total closing debit = 300 + 450
    }

    /**
     * Multiple accounts are each rendered as a separate row.
     */
    public function test_multiple_accounts_each_rendered_in_results(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();

        $acc1 = $this->createAccount(['name' => ['ar' => 'الحساب الأول للتعدد', 'en' => 'Multi One'], 'code' => '1801', 'account_type' => 'assets', 'transfer_side' => 'budget']);
        $acc2 = $this->createAccount(['name' => ['ar' => 'الحساب الثاني للتعدد', 'en' => 'Multi Two'], 'code' => '1802', 'account_type' => 'assets', 'transfer_side' => 'budget']);

        $j1 = $this->insertJournalEntry(['serial' => 'JMUL-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j1, 'account_id' => $acc1, 'document_date' => '2026-03-05', 'debit' => 111]);

        $j2 = $this->insertJournalEntry(['serial' => 'JMUL-002', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j2, 'account_id' => $acc2, 'document_date' => '2026-03-07', 'debit' => 222]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('الحساب الأول للتعدد');
        $response->assertSee('الحساب الثاني للتعدد');
        $response->assertSee('111.00');
        $response->assertSee('222.00');
    }

    /**
     * When account_level is specified, only accounts at that level are shown.
     * A level-2 account must not appear when filtering by level 1.
     */
    public function test_account_level_filter_shows_only_accounts_at_given_level(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();

        // Level-1 parent account
        $parentId = $this->createAccount([
            'name' => ['ar' => 'حساب المستوى الأول', 'en' => 'Level One Account'],
            'code' => '1900',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'level' => 1,
        ]);

        // Level-2 child account
        $childId = $this->createAccount([
            'name' => ['ar' => 'حساب المستوى الثاني', 'en' => 'Level Two Account'],
            'code' => '1901',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'level' => 2,
            'parent_account_id' => $parentId,
        ]);

        $j1 = $this->insertJournalEntry(['serial' => 'JLVL-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j1, 'account_id' => $parentId, 'document_date' => '2026-03-05', 'debit' => 400]);

        $j2 = $this->insertJournalEntry(['serial' => 'JLVL-002', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j2, 'account_id' => $childId, 'document_date' => '2026-03-06', 'debit' => 250]);

        // Filter by level 1 → only parent appears
        $responseLevel1 = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
                'account_level' => 1,
            ]);

        $responseLevel1->assertOk();
        $responseLevel1->assertSee('حساب المستوى الأول');
        $responseLevel1->assertDontSee('حساب المستوى الثاني');

        // Filter by level 2 → only child appears
        $responseLevel2 = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
                'account_level' => 2,
            ]);

        $responseLevel2->assertOk();
        $responseLevel2->assertDontSee('حساب المستوى الأول');
        $responseLevel2->assertSee('حساب المستوى الثاني');
    }

    /**
     * When account_level is null (default), only leaf accounts (no children) are shown.
     */
    public function test_default_mode_shows_only_leaf_accounts(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();

        // Parent account (has a child → not a leaf)
        $parentId = $this->createAccount([
            'name' => ['ar' => 'حساب أب', 'en' => 'Parent Account'],
            'code' => '2001',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'level' => 1,
        ]);

        // Leaf child
        $childId = $this->createAccount([
            'name' => ['ar' => 'حساب ورقة', 'en' => 'Leaf Account'],
            'code' => '2002',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'level' => 2,
            'parent_account_id' => $parentId,
        ]);

        // Give both accounts activity
        $j1 = $this->insertJournalEntry(['serial' => 'JLEAF-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j1, 'account_id' => $parentId, 'document_date' => '2026-03-05', 'debit' => 300]);
        $this->insertJournalEntryDocument(['journal_id' => $j1, 'account_id' => $childId, 'document_date' => '2026-03-05', 'debit' => 200]);

        // No account_level param → default = leaf accounts only
        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        // Parent has child → not a leaf → excluded
        $response->assertDontSee('حساب أب');
        // Child has no children → leaf → included
        $response->assertSee('حساب ورقة');
    }

    /**
     * When all activity falls outside the requested period and there are no
     * opening balances, the report shows no account rows (only the total row).
     */
    public function test_no_results_when_all_activity_is_outside_the_period(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب خارج الفترة', 'en' => 'Out of Period Account'],
            'code' => '2101',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Doc is after the requested period
        $j = $this->insertJournalEntry(['serial' => 'JOUT-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $j, 'account_id' => $accountId, 'document_date' => '2026-06-01', 'debit' => 400]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertDontSee('حساب خارج الفترة');
        $response->assertDontSee('400.00');
    }

    /**
     * Credit before-period document appears as opening_credit, not opening_debit.
     * Verified via closing arithmetic: opening_credit(400) reduces closing_net.
     */
    public function test_before_period_credit_classified_as_opening_credit(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب افتتاح دائن', 'en' => 'Credit Opening Account'],
            'code' => '2201',
            'account_type' => 'liabilities',
            'transfer_side' => 'budget',
        ]);

        // Before period: credit 400
        $jBefore = $this->insertJournalEntry(['serial' => 'JCOB-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jBefore, 'account_id' => $accountId, 'document_date' => '2026-01-10', 'credit' => 400]);

        // In period: debit 150
        $jIn = $this->insertJournalEntry(['serial' => 'JCOI-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jIn, 'account_id' => $accountId, 'document_date' => '2026-03-10', 'debit' => 150]);

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'branch_id' => $admin->branch_id,
            ]);

        $response->assertOk();
        $response->assertSee('400.00'); // opening_credit
        $response->assertSee('150.00'); // period_debit
        // closing_net = (0+150) - (400+0) = -250 → 250.00 دائن
        $response->assertSee('250.00');
        $response->assertSee('دائن');
    }

    /**
     * Opening balance table debit is combined with before-period journal debit
     * when branch_scope_all = true.
     */
    public function test_opening_balances_combined_with_before_period_movements(): void
    {
        $admin = $this->createAdminUser(['employee.accounting_reports.show']);
        $otherBranch = $this->createBranch('فرع ثالث للتجميع');
        $fyId = $this->createFinancialYear();
        $accountId = $this->createAccount([
            'name' => ['ar' => 'حساب تجميع الافتتاحية', 'en' => 'Combined Opening Account'],
            'code' => '2301',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        // Opening balance table: 1000
        $this->insertOpeningBalance(['financial_year' => $fyId, 'account_id' => $accountId, 'debit' => 1000]);

        // Before-period journal doc: 200
        $jBefore = $this->insertJournalEntry(['serial' => 'JCOMB-001', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jBefore, 'account_id' => $accountId, 'document_date' => '2026-01-05', 'debit' => 200]);

        // In period: 300
        $jIn = $this->insertJournalEntry(['serial' => 'JCOMB-002', 'financial_year' => $fyId, 'branch_id' => $admin->branch_id]);
        $this->insertJournalEntryDocument(['journal_id' => $jIn, 'account_id' => $accountId, 'document_date' => '2026-03-10', 'debit' => 300]);

        // POST without branch filter → selects_all = true → opening = 1000 + 200 = 1200
        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('trail_balance.search', [], false), [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]);

        $response->assertOk();
        $response->assertSee('حساب تجميع الافتتاحية');
        $response->assertSee('1,200.00'); // combined opening = 1000 (table) + 200 (before-period)
        $response->assertSee('300.00');   // period debit
        $response->assertSee('1,500.00'); // closing = 1200 + 300
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions): User
    {
        $branch = $this->createBranch('فرع اختبار الميزان');

        $role = Role::create([
            'name' => ['ar' => 'مدير الميزان', 'en' => 'Trail Balance Admin'],
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
            'name' => 'Trail Balance Test Admin',
            'email' => 'trail-balance-'.uniqid().'@example.com',
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

    private function grantBranchAccess(User $user, int $branchId): void
    {
        DB::table('branch_user')->insertOrIgnore([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
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
        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode(['ar' => 'حساب', 'en' => 'Account'], JSON_UNESCAPED_UNICODE),
            'code' => '1000',
            'old_id' => null,
            'level' => 1,
            'parent_account_id' => null,
            'subscriber_id' => null,
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
            'journal_date' => '2026-03-05',
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
            'document_date' => '2026-03-05',
            'credit' => 0,
            'debit' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertOpeningBalance(array $attributes): int
    {
        return DB::table('opening_balances')->insertGetId(array_merge([
            'financial_year' => null,
            'account_id' => null,
            'debit' => 0,
            'credit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
