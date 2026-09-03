<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\JournalEntryDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        // «حتى مستوى N» يعرض الشجرة مجمّعة: رقم كل حساب يشمل فروعه.
        // و«تفصيلي» يعرض كل حساب بحركته المباشرة وحدها، فلا يتكرّر مبلغ.
        $aggregated = $accountLevel !== null;

        $accountQuery = Account::query()->orderBy('code')->orderBy('id');

        if ($aggregated) {
            $accountQuery->where('level', '<=', $accountLevel);
        } else {
            // الأطراف، ومعها كل حساب سُجّل عليه قيد مباشرةً وإن صار له أبناء
            // بعد ذلك — وإلا سقط مبلغه من التقرير ومن الإجمالي فاختلّ الميزان.
            $postedAccountIds = $this->accountIdsCarryingEntries();

            $accountQuery->where(function ($query) use ($postedAccountIds) {
                $query
                    ->whereDoesntHave('childrens')
                    ->orWhereIn('id', $postedAccountIds);
            });
        }

        $accounts = $accountQuery
            ->get()
            ->filter(function (Account $account) use ($filters, $aggregated) {
                $metrics = $this->buildSummaryMetricsForAccount($account, $filters, $aggregated);

                return $this->hasVisibleActivity($metrics);
            })
            ->values();

        $accountMetrics = $accounts
            ->mapWithKeys(function (Account $account) use ($filters, $aggregated) {
                return [$account->id => $this->buildSummaryMetricsForAccount($account, $filters, $aggregated)];
            })
            ->all();

        return [
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
            'accounts' => $accounts,
            'accountMetrics' => $accountMetrics,
            'totals' => $this->totals($accounts, $accountMetrics, $aggregated),
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
    private function buildSummaryMetricsForAccount(Account $account, array $filters, bool $includeChildren = true): array
    {
        $accountIds = $includeChildren ? $account->childrensIds : [(int) $account->id];
        $openingDebit = 0.0;
        $openingCredit = 0.0;

        if (($filters['branch_scope_all'] ?? false) === true) {
            $openingTotals = DB::table('opening_balances')
                ->whereIn('account_id', $accountIds)
                ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
                ->first();

            $openingDebit += (float) ($openingTotals->debit_total ?? 0);
            $openingCredit += (float) ($openingTotals->credit_total ?? 0);
        }

        $beforeTotals = $this->summaryDocumentsQuery($accountIds, $filters)
            ->where('document_date', '<', $filters['period_from'])
            ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
            ->first();

        $periodTotals = $this->summaryDocumentsQuery($accountIds, $filters)
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
     * @param  array<int, int>  $accountIds
     * @param  array<string, mixed>  $filters
     */
    private function summaryDocumentsQuery(array $accountIds, array $filters)
    {
        return JournalEntryDocument::query()
            ->whereIn('account_id', $accountIds)
            ->when(($filters['branch_ids'] ?? []) !== [], function ($query) use ($filters) {
                return $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                    $journalQuery->whereIn('branch_id', $filters['branch_ids']);
                });
            });
    }

    /**
     * الحسابات التي سُجّل عليها قيد أو رصيد افتتاحي مباشرةً — تُستعمل مرشّحةً
     * لصفوف الوضع التفصيلي، ثم تُنقّى بعدها بحسب الفرع والمدة.
     *
     * @return array<int, int>
     */
    private function accountIdsCarryingEntries(): array
    {
        return DB::table('journal_entry_documents')
            ->select('account_id')
            ->distinct()
            ->pluck('account_id')
            ->merge(
                DB::table('opening_balances')->select('account_id')->distinct()->pluck('account_id')
            )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * إجمالي الميزان.
     *
     * في الوضع التفصيلي كل صفّ يحمل حركته المباشرة وحدها، ولا يشمل صفٌّ صفًّا
     * آخر، فتُجمع الصفوف كلها.
     *
     * وفي وضع المستوى تُجمع الصفوف العليا وحدها — أي صفّ لا يظهر فوقه أبٌ
     * له في التقرير.
     *
     * أرقام كل حساب تشمل حساباته الفرعية، فحين يُطلب «حتى مستوى 3» يظهر الأب
     * وابنه معًا؛ ولو جُمع كل صفّ لحُسب المبلغ مرّتين أو ثلاثًا، فيتضاعف
     * الإجمالي بارتفاع المستوى وهو لم يتغيّر. أما في الوضع التفصيلي فالصفوف
     * كلها أطراف لا أب لأحدها في القائمة، فتُجمع جميعًا.
     *
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, array<string, float>>  $accountMetrics
     * @return array<string, float>
     */
    private function totals(Collection $accounts, array $accountMetrics, bool $aggregated = true): array
    {
        $displayedIds = $accounts->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $parentOf = $aggregated ? Account::query()->pluck('parent_account_id', 'id') : collect();

        $totals = [
            'opening_debit' => 0.0,
            'opening_credit' => 0.0,
            'period_debit' => 0.0,
            'period_credit' => 0.0,
            'closing_debit' => 0.0,
            'closing_credit' => 0.0,
            'closing_net' => 0.0,
        ];

        foreach ($accounts as $account) {
            if ($aggregated && $this->hasDisplayedAncestor($account, $displayedIds, $parentOf)) {
                continue;
            }

            $metrics = $accountMetrics[$account->id] ?? [];

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + (float) ($metrics[$key] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * @param  Collection<int, int>  $displayedIds  المعرّفات الظاهرة مفاتيحَ
     * @param  Collection<int, int|null>  $parentOf
     */
    private function hasDisplayedAncestor(Account $account, Collection $displayedIds, Collection $parentOf): bool
    {
        $parentId = $parentOf->get($account->id);

        // الحدّ الأقصى يحمي من شجرة معطوبة تدور على نفسها
        for ($depth = 0; $parentId !== null && $depth < 50; $depth++) {
            $parentId = (int) $parentId;

            if ($displayedIds->has($parentId)) {
                return true;
            }

            $parentId = $parentOf->get($parentId);
        }

        return false;
    }
}
