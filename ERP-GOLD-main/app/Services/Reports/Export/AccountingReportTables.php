<?php

namespace App\Services\Reports\Export;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * يحوّل حمولة كل تقرير محاسبي إلى ReportTable واحد، فتخرج منه صفحة الطباعة
 * وملف PDF وملف Excel بالأرقام والصفوف نفسها.
 *
 * قواعد الإخفاء والنزول في الشجرة هنا هي نفسها الموجودة في قالبي
 * admin.reports.income_statement.recursive و admin.reports.balance_sheet.recursive
 * اللذين يرسمان التقرير على الشاشة؛ أي تعديل في أحدهما يُنقل إلى الآخر.
 */
class AccountingReportTables
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function trialBalance(array $payload): ReportTable
    {
        $accounts = $payload['accounts'];
        $metrics = $payload['accountMetrics'] ?? [];
        $totals = $payload['totals'] ?? [];

        $moneyColumns = ['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'];

        $bodyRows = [];

        foreach ($accounts as $account) {
            $row = $metrics[$account->id] ?? [];
            $cells = [ReportCell::label($account->name . ' - ' . $account->code)];

            foreach ($moneyColumns as $column) {
                $cells[] = ReportCell::number(abs((float) ($row[$column] ?? 0)));
            }

            $cells[] = ReportCell::text($this->signedBalance($row['closing_net'] ?? 0));
            $bodyRows[] = ReportRow::normal($cells);
        }

        $footerCells = [ReportCell::label('اجمالي الميزان', 0, true)];

        foreach ($moneyColumns as $column) {
            $footerCells[] = ReportCell::number(abs((float) ($totals[$column] ?? 0)), true);
        }

        $footerCells[] = ReportCell::text($this->signedBalance($totals['closing_net'] ?? 0), 1, true);

        return new ReportTable(
            title: __('main.balance_report'),
            meta: array_merge($this->meta($payload), [
                'المستوى: ' . ($payload['accountLevel'] ?? null ? 'حتى مستوى ' . $payload['accountLevel'] : 'تفصيلي (آخر مستوى)'),
            ]),
            headerRows: [
                [
                    ReportCell::header(__('main.account_name'), 1, 2),
                    ReportCell::header(__('main.Before_Debit'), 2),
                    ReportCell::header(__('main.movement'), 2),
                    ReportCell::header('الاغلاق', 3),
                ],
                [
                    ReportCell::header(__('main.Debit')),
                    ReportCell::header(__('main.Credit')),
                    ReportCell::header(__('main.Debit')),
                    ReportCell::header(__('main.Credit')),
                    ReportCell::header(__('main.Debit')),
                    ReportCell::header(__('main.Credit')),
                    ReportCell::header('الرصيد'),
                ],
            ],
            bodyRows: $bodyRows,
            footerRows: [ReportRow::total($footerCells)],
            fileName: 'trial-balance',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function incomeStatement(array $payload): ReportTable
    {
        $rows = $this->summaryRows(
            [$payload['revenuesAccount'], $payload['expensesAccount']],
            $payload,
            keepRoots: false
        );

        return new ReportTable(
            title: __('main.incoming_list'),
            meta: $this->meta($payload),
            headerRows: [$this->summaryHeader()],
            bodyRows: $rows,
            footerRows: [$this->profitRow($payload['profitTotal'] ?? 0)],
            fileName: 'income-statement',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function balanceSheet(array $payload): ReportTable
    {
        $rows = $this->summaryRows(
            [$payload['assetsAccount'], $payload['liabilitiesAccount'], $payload['equityAccount']],
            $payload,
            keepRoots: true
        );

        return new ReportTable(
            title: __('main.Balance_Sheet'),
            meta: array_merge($this->meta($payload), [
                'المستوى: ' . ($payload['accountLevel'] ?? null ? 'حتى مستوى ' . $payload['accountLevel'] : 'تفصيلي (كل المستويات)'),
            ]),
            headerRows: [$this->summaryHeader()],
            bodyRows: $rows,
            footerRows: [$this->profitRow($payload['profitTotal'] ?? 0)],
            fileName: 'balance-sheet',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function costCenters(array $payload): ReportTable
    {
        $report = $payload['report'];
        $branches = $report['branches'];
        $branchIds = $branches->pluck('id')->all();

        $header = [ReportCell::header('الحساب')];

        foreach ($branches as $branch) {
            $header[] = ReportCell::header($branch->name);
        }

        $header[] = ReportCell::header('الإجمالي');

        $bodyRows = [];

        foreach ($report['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                $cells = [ReportCell::label($row['name'], $row['level'], $row['level'] === 1)];

                foreach ($branchIds as $branchId) {
                    $cells[] = ReportCell::number((float) ($row['values'][$branchId] ?? 0), $row['level'] === 1);
                }

                $cells[] = ReportCell::number((float) $row['total'], true);

                $bodyRows[] = $row['level'] === 1
                    ? ReportRow::section($cells)
                    : ReportRow::normal($cells);
            }
        }

        $footerRows = [];

        if (! empty($report['sections'])) {
            $cells = [ReportCell::label('صافي الربح', 0, true)];

            foreach ($branchIds as $branchId) {
                $cells[] = ReportCell::number((float) ($report['net_by_branch'][$branchId] ?? 0), true);
            }

            $cells[] = ReportCell::number((float) $report['grand_net'], true);
            $footerRows[] = ReportRow::total($cells);
        }

        $meta = [
            '[ ' . $payload['periodFrom'] . ' - ' . $payload['periodTo'] . ' ]',
            'الفروع: ' . ($payload['branchLabel'] ?? 'جميع الفروع'),
        ];

        if ($payload['accountLevel'] ?? null) {
            $meta[] = 'المستوى: حتى مستوى ' . $payload['accountLevel'];
        }

        return new ReportTable(
            title: 'مراكز التكلفة — قائمة الدخل حسب الفروع',
            meta: $meta,
            headerRows: [$header],
            bodyRows: $bodyRows,
            footerRows: $footerRows,
            emptyMessage: 'لا توجد حسابات إيرادات أو مصروفات في شجرة الحسابات.',
            fileName: 'cost-centers',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function accountStatement(array $payload): ReportTable
    {
        $opening = $payload['openingBalance'];
        $documents = $payload['documents'];
        $running = (float) $opening['net'];

        $bodyRows = [];

        if ($opening['net'] != 0 || $opening['debit'] != 0 || $opening['credit'] != 0) {
            $bodyRows[] = ReportRow::normal([
                ReportCell::text('1'),
                ReportCell::text('--'),
                ReportCell::text('--'),
                ReportCell::text('--'),
                ReportCell::text('--'),
                ReportCell::text('--'),
                ReportCell::text('--'),
                ReportCell::text('رصيد اول المده'),
                ReportCell::number((float) $opening['debit']),
                ReportCell::number((float) $opening['credit']),
                ReportCell::text($this->signedBalance($running)),
            ]);
        }

        foreach ($documents as $index => $document) {
            $running += (float) $document['debit'] - (float) $document['credit'];

            $bodyRows[] = ReportRow::normal([
                ReportCell::text((string) ($index + 1)),
                ReportCell::text(\Carbon\Carbon::parse($document['date'])->format('d-m-Y')),
                ReportCell::text($document['time'] ?? '-'),
                ReportCell::text($document['branch_name']),
                ReportCell::text($document['user_name']),
                ReportCell::text($document['source_type_label']),
                ReportCell::text((string) $document['reference_number']),
                ReportCell::text((string) $document['document_label']),
                ReportCell::number((float) $document['debit']),
                ReportCell::number((float) $document['credit']),
                ReportCell::text($this->signedBalance($running)),
            ]);
        }

        $meta = [
            '[ ' . $payload['periodFrom'] . ' - ' . $payload['periodTo'] . ' ]',
            $payload['account']->name,
            'الفرع: ' . ($payload['branchSelection']['branch_label'] ?? 'جميع الفروع'),
        ];

        return new ReportTable(
            title: __('main.account_movement_report'),
            meta: $meta,
            headerRows: [[
                ReportCell::header('#'),
                ReportCell::header(__('main.date')),
                ReportCell::header('الوقت'),
                ReportCell::header('الفرع'),
                ReportCell::header('المستخدم'),
                ReportCell::header('نوع المصدر'),
                ReportCell::header('المرجع'),
                ReportCell::header(__('main.document_type')),
                ReportCell::header(__('main.Debit')),
                ReportCell::header(__('main.Credit')),
                ReportCell::header(__('main.balance')),
            ]],
            bodyRows: $bodyRows,
            footerRows: [ReportRow::total([
                ReportCell::blank(7),
                ReportCell::text(__('main.total_balance'), 1, true),
                ReportCell::blank(2),
                ReportCell::text($this->signedBalance($running), 1, true),
            ])],
            fileName: 'account-statement',
        );
    }

    /**
     * صافي الربح: موجبه دائن هنا، عكس أعمدة الأرصدة فوقه.
     */
    private function profitRow(float|int $profitTotal): ReportRow
    {
        return ReportRow::total([
            ReportCell::label('صافي الربح', 0, true),
            ReportCell::blank(2),
            ReportCell::text($this->signedBalance($profitTotal, reversed: true), 1, true),
        ]);
    }

    /**
     * @return list<ReportCell>
     */
    private function summaryHeader(): array
    {
        return [
            ReportCell::header(__('main.account_name')),
            ReportCell::header(__('main.total_debit')),
            ReportCell::header(__('main.total_credit')),
            ReportCell::header(__('main.balance')),
        ];
    }

    /**
     * صفوف شجرة الحسابات كما يرسمها القالب التكراري على الشاشة.
     *
     * @param  list<Account>  $roots
     * @param  array<string, mixed>  $payload
     * @return list<ReportRow>
     */
    private function summaryRows(array $roots, array $payload, bool $keepRoots): array
    {
        $rows = [];

        foreach ($roots as $root) {
            $this->collectSummaryRows(
                $root,
                $payload['accountMetrics'] ?? [],
                ($payload['hideEmpty'] ?? false) === true,
                $payload['accountLevel'] ?? null,
                $payload['periodFrom'],
                $payload['periodTo'],
                $keepRoots,
                $rows
            );
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, float>>  $metrics
     * @param  list<ReportRow>  $rows
     */
    private function collectSummaryRows(
        Account $account,
        array $metrics,
        bool $hideEmpty,
        ?int $accountLevel,
        string $periodFrom,
        string $periodTo,
        bool $keepRoots,
        array &$rows
    ): void {
        $accountMetrics = $metrics[$account->id] ?? null;
        $balance = $accountMetrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);

        // عند اختيار فرع، يُسقَط الحساب الذي صافيه صفر فيه هو وكل ما تحته حتى لا
        // تظهر حسابات الفروع الأخرى بأصفار. الميزانية تُبقي أقسامها الرئيسية.
        $subtreeEmpty = $accountMetrics !== null && abs((float) ($accountMetrics['closing_net'] ?? 0)) < 0.005;
        $skip = $hideEmpty && $subtreeEmpty && (! $keepRoots || (int) $account->level > 1);

        if ($skip) {
            return;
        }

        $rows[] = ReportRow::normal([
            ReportCell::label($account->name, (int) $account->level, (int) $account->level === 1),
            ReportCell::number((float) ($accountMetrics['closing_debit'] ?? $account->closingBalance($periodFrom, $periodTo, 'debit'))),
            ReportCell::number((float) ($accountMetrics['closing_credit'] ?? $account->closingBalance($periodFrom, $periodTo, 'credit'))),
            ReportCell::text($this->signedBalance($balance)),
        ]);

        // «مستوى الحساب» يوقف النزول عند العمق المطلوب، فتُقرأ القائمة مجمّعة.
        $shouldDescend = $accountLevel === null || $account->level < $accountLevel;

        if (! $shouldDescend) {
            return;
        }

        $children = $account->childrens;

        if (! $children instanceof Collection || $children->isEmpty()) {
            return;
        }

        foreach ($children as $child) {
            $this->collectSummaryRows($child, $metrics, $hideEmpty, $accountLevel, $periodFrom, $periodTo, $keepRoots, $rows);
        }
    }

    /**
     * الرصيد كما يُعرض: قيمته المطلقة يليها جانبه. `$reversed` لصفوف الربح
     * حيث الموجب دائن لا مدين.
     */
    private function signedBalance(float|int|string|null $value, bool $reversed = false): string
    {
        $value = (float) $value;
        $text = number_format(abs($value), 2);

        if ($value == 0.0) {
            return $text;
        }

        $isDebit = $reversed ? $value < 0 : $value > 0;

        return $text . ' / ' . ($isDebit ? __('main.debit') : __('main.credit'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function meta(array $payload): array
    {
        return [
            '[ ' . $payload['periodFrom'] . ' - ' . $payload['periodTo'] . ' ]',
            'الفرع: ' . ($payload['branchLabel'] ?? ($payload['branch']->name ?? 'جميع الفروع')),
        ];
    }
}
