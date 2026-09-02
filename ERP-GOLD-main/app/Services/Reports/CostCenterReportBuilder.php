<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * قائمة الدخل موزّعة على الفروع في جدول واحد: صفوف الحسابات وأعمدة الفروع.
 *
 * قائمة الدخل تعرض فرعًا واحدًا في كل مرة، فمقارنة ستة فروع تحتاج فتحها ست
 * مرات وجمع الأرقام يدويًا. هنا تُقرأ كلها معًا مع صافي ربح كل فرع.
 *
 * التجميع بفرع القيد لا باسم الحساب، فيعمل سواء كانت الحسابات مسمّاة بأسماء
 * فروع أو مشتركة بينها — ويغني عن تكرار الحساب لكل فرع.
 *
 * كل صف يُعرض في اتجاهه الطبيعي: الإيراد دائن موجب، والمصروف مدين موجب،
 * وصافي الربح = الإيرادات - المصروفات.
 */
class CostCenterReportBuilder
{
    /**
     * @param  array<int, int>  $branchIds
     * @return array<string, mixed>
     */
    public function build(array $branchIds, string $periodFrom, string $periodTo, ?int $accountLevel = null): array
    {
        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->orderBy('id')
            ->get(['id', 'name']);

        $roots = Account::query()
            ->whereNull('parent_account_id')
            ->whereIn('account_type', ['revenues', 'expenses'])
            ->where('transfer_side', 'income_statement')
            ->orderBy('code')
            ->get();

        if ($roots->isEmpty() || $branches->isEmpty()) {
            return [
                'branches' => $branches,
                'sections' => [],
                'branch_totals' => [],
                'net_by_branch' => [],
                'grand_net' => 0.0,
            ];
        }

        $accounts = Account::query()
            ->whereIn('account_type', ['revenues', 'expenses'])
            ->where('transfer_side', 'income_statement')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'level', 'parent_account_id', 'account_type']);

        $movement = $this->movementByAccountAndBranch(
            $accounts->pluck('id')->all(),
            $branchIds,
            $periodFrom,
            $periodTo
        );

        $childrenOf = $accounts->groupBy('parent_account_id');
        $sections = [];
        $branchTotals = [];
        $netByBranch = [];

        foreach ($roots as $root) {
            $rows = [];
            $this->collect($root, $accounts, $childrenOf, $movement, $branchIds, $rows);

            $sectionTotals = $rows[0]['values'] ?? array_fill_keys($branchIds, 0.0);

            // «مستوى الحساب» يخفي ما هو أعمق منه — بعد أن دخل في مجاميع آبائه،
            // فالمجاميع تبقى كاملة مهما كان المستوى المعروض.
            if ($accountLevel !== null) {
                $rows = array_values(array_filter(
                    $rows,
                    fn ($row) => $row['level'] <= $accountLevel
                ));
            }

            $sections[] = [
                'account_type' => $root->account_type,
                'name' => $root->name,
                'rows' => $rows,
                'totals' => $sectionTotals,
                'total' => array_sum($sectionTotals),
            ];

            $branchTotals[$root->account_type] = $sectionTotals;
        }

        foreach ($branchIds as $branchId) {
            $netByBranch[$branchId] =
                ($branchTotals['revenues'][$branchId] ?? 0.0)
                - ($branchTotals['expenses'][$branchId] ?? 0.0);
        }

        return [
            'branches' => $branches,
            'sections' => $sections,
            'branch_totals' => $branchTotals,
            'net_by_branch' => $netByBranch,
            'grand_net' => array_sum($netByBranch),
        ];
    }

    /**
     * مجاميع كل حساب في كل فرع، باستعلام واحد بدل استعلام لكل خانة.
     *
     * @param  array<int, int>  $accountIds
     * @param  array<int, int>  $branchIds
     * @return array<int, array<int, array{debit: float, credit: float}>>
     */
    private function movementByAccountAndBranch(
        array $accountIds,
        array $branchIds,
        string $periodFrom,
        string $periodTo
    ): array {
        if ($accountIds === [] || $branchIds === []) {
            return [];
        }

        $rows = DB::table('journal_entry_documents as d')
            ->join('journal_entries as j', 'j.id', '=', 'd.journal_id')
            ->whereIn('d.account_id', $accountIds)
            ->whereIn('j.branch_id', $branchIds)
            ->whereBetween('d.document_date', [$periodFrom, $periodTo])
            ->groupBy('d.account_id', 'j.branch_id')
            ->selectRaw('d.account_id, j.branch_id, SUM(d.debit) as debit_total, SUM(d.credit) as credit_total')
            ->get();

        $movement = [];

        foreach ($rows as $row) {
            $movement[(int) $row->account_id][(int) $row->branch_id] = [
                'debit' => (float) $row->debit_total,
                'credit' => (float) $row->credit_total,
            ];
        }

        return $movement;
    }

    /**
     * يبني صفوف القسم ويعيد مجاميع الفرع الواحد صاعدةً من الأبناء إلى الأب.
     *
     * @param  Collection<int, Account>  $accounts
     * @param  Collection<int|string, Collection<int, Account>>  $childrenOf
     * @param  array<int, array<int, array{debit: float, credit: float}>>  $movement
     * @param  array<int, int>  $branchIds
     * @param  array<int, mixed>  $rows
     * @return array<int, float>
     */
    private function collect(
        Account $account,
        Collection $accounts,
        Collection $childrenOf,
        array $movement,
        array $branchIds,
        array &$rows
    ): array {
        $own = array_fill_keys($branchIds, 0.0);
        $isRevenue = $account->account_type === 'revenues';

        foreach ($branchIds as $branchId) {
            $entry = $movement[$account->id][$branchId] ?? null;

            if ($entry === null) {
                continue;
            }

            // كل صف في اتجاهه الطبيعي: الإيراد دائن، والمصروف مدين.
            $own[$branchId] = $isRevenue
                ? $entry['credit'] - $entry['debit']
                : $entry['debit'] - $entry['credit'];
        }

        $position = count($rows);
        $rows[] = null; // نحجز موضع الأب ليأتي قبل أبنائه بعد حساب مجاميعه

        $totals = $own;

        foreach ($childrenOf->get($account->id, collect()) as $child) {
            $childTotals = $this->collect(
                $child,
                $accounts,
                $childrenOf,
                $movement,
                $branchIds,
                $rows
            );

            foreach ($branchIds as $branchId) {
                $totals[$branchId] += $childTotals[$branchId];
            }
        }

        $rows[$position] = [
            'id' => (int) $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'level' => (int) $account->level,
            'account_type' => $account->account_type,
            'values' => $totals,
            'total' => array_sum($totals),
        ];

        return $totals;
    }
}
