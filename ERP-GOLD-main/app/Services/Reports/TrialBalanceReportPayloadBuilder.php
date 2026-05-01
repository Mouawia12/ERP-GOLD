<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\JournalEntryDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialBalanceReportPayloadBuilder
{
    public function __construct(
        private readonly ReportBranchSelectionService $branchSelectionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function filtersData(?User $user): array
    {
        $branchSelection = $this->branchSelectionService->resolve(Request::create('/', 'GET'), $user);

        $availableLevels = Account::query()
            ->selectRaw('DISTINCT level')
            ->orderBy('level')
            ->pluck('level')
            ->filter()
            ->values();

        return [
            'branches' => $branchSelection['branches']->where('status', 1)->values(),
            'availableLevels' => $availableLevels,
            'defaultFilters' => [
                'date_from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'date_to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request, ?User $user): array
    {
        $branchSelection = $this->branchSelectionService->resolve($request, $user);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $accountLevel = $request->input('account_level') ? (int) $request->input('account_level') : null;

        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
        ];

        $accountQuery = Account::query()->orderBy('code')->orderBy('id');
        if ($accountLevel !== null) {
            $accountQuery->where('level', $accountLevel);
        } else {
            $accountQuery->whereDoesntHave('childrens');
        }

        $accounts = $accountQuery
            ->get()
            ->filter(function (Account $account) use ($filters) {
                $metrics = $this->buildSummaryMetricsForAccount($account, $filters);

                return $this->hasVisibleActivity($metrics);
            })
            ->values();

        $accountMetrics = $accounts
            ->mapWithKeys(function (Account $account) use ($filters) {
                return [$account->id => $this->buildSummaryMetricsForAccount($account, $filters)];
            })
            ->all();

        return [
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
            'accounts' => $accounts,
            'accountMetrics' => $accountMetrics,
            'totals' => $this->totals($accountMetrics),
            'branch' => $branchSelection['single_branch'],
            'branchLabel' => $branchSelection['branch_label'],
            'branchSelection' => $branchSelection,
            'accountLevel' => $accountLevel,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolvePeriod(Request $request, string $defaultDateFrom, string $defaultDateTo): array
    {
        $periodFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->format('Y-m-d')
            : $defaultDateFrom;

        $periodTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->format('Y-m-d')
            : $defaultDateTo;

        return [$periodFrom, $periodTo];
    }

    /**
     * @param  array<string, float>  $metrics
     */
    private function hasVisibleActivity(array $metrics): bool
    {
        return abs($metrics['opening_debit']) > 0
            || abs($metrics['opening_credit']) > 0
            || abs($metrics['period_debit']) > 0
            || abs($metrics['period_credit']) > 0;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function buildSummaryMetricsForAccount(Account $account, array $filters): array
    {
        $openingDebit = 0.0;
        $openingCredit = 0.0;

        if (($filters['branch_scope_all'] ?? false) === true) {
            $openingTotals = DB::table('opening_balances')
                ->whereIn('account_id', $account->childrensIds)
                ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
                ->first();

            $openingDebit += (float) ($openingTotals->debit_total ?? 0);
            $openingCredit += (float) ($openingTotals->credit_total ?? 0);
        }

        $beforeTotals = $this->summaryDocumentsQuery($account, $filters)
            ->where('document_date', '<', $filters['period_from'])
            ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
            ->first();

        $periodTotals = $this->summaryDocumentsQuery($account, $filters)
            ->whereBetween('document_date', [$filters['period_from'], $filters['period_to']])
            ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
            ->first();

        $openingDebit += (float) ($beforeTotals->debit_total ?? 0);
        $openingCredit += (float) ($beforeTotals->credit_total ?? 0);

        $periodDebit = (float) ($periodTotals->debit_total ?? 0);
        $periodCredit = (float) ($periodTotals->credit_total ?? 0);

        $closingDebit = $openingDebit + $periodDebit;
        $closingCredit = $openingCredit + $periodCredit;

        return [
            'opening_debit' => round($openingDebit, 2),
            'opening_credit' => round($openingCredit, 2),
            'period_debit' => round($periodDebit, 2),
            'period_credit' => round($periodCredit, 2),
            'closing_debit' => round($closingDebit, 2),
            'closing_credit' => round($closingCredit, 2),
            'closing_net' => round($closingDebit - $closingCredit, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function summaryDocumentsQuery(Account $account, array $filters)
    {
        return JournalEntryDocument::query()
            ->whereIn('account_id', $account->childrensIds)
            ->when(($filters['branch_ids'] ?? []) !== [], function ($query) use ($filters) {
                return $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                    $journalQuery->whereIn('branch_id', $filters['branch_ids']);
                });
            });
    }

    /**
     * @param  array<int, array<string, float>>  $accountMetrics
     * @return array<string, float>
     */
    private function totals(array $accountMetrics): array
    {
        return collect($accountMetrics)->reduce(function (array $totals, array $metrics) {
            foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit', 'closing_net'] as $key) {
                $totals[$key] += (float) ($metrics[$key] ?? 0);
            }

            return $totals;
        }, [
            'opening_debit' => 0.0,
            'opening_credit' => 0.0,
            'period_debit' => 0.0,
            'period_credit' => 0.0,
            'closing_debit' => 0.0,
            'closing_credit' => 0.0,
            'closing_net' => 0.0,
        ]);
    }
}
