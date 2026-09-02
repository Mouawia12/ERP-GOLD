<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\JournalEntryDocument;
use App\Models\User;
use App\Services\Printing\PrintFormatResolver;
use App\Services\Printing\PrintUrlBuilder;
use App\Services\Reports\CostCenterReportBuilder;
use App\Services\Reports\Export\AccountingReportTables;
use App\Services\Reports\Export\ReportTable;
use App\Services\Reports\Export\XlsxWriter;
use App\Services\Reports\ReportBranchSelectionService;
use App\Services\Reports\TrialBalanceReportPayloadBuilder;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class AccountingReportsController extends Controller
{
    public function trail_balance(Request $request, TrialBalanceReportPayloadBuilder $payloadBuilder)
    {
        return view('admin.reports.trail_balance.search', $payloadBuilder->filtersData($request->user('admin-web')));
    }

    public function trail_balance_search(Request $request, TrialBalanceReportPayloadBuilder $payloadBuilder)
    {
        return view('admin.reports.trail_balance.index', $payloadBuilder->build($request, $request->user('admin-web')));
    }

    public function trail_balance_print(
        Request $request,
        TrialBalanceReportPayloadBuilder $payloadBuilder,
        PrintFormatResolver $formatResolver,
        PrintUrlBuilder $urlBuilder
    ) {
        $payload = $payloadBuilder->build($request, $request->user('admin-web'));
        $printFormat = $formatResolver->resolve($request, 'a4', 'landscape');

        return view('admin.reports.trail_balance.print', $payload + [
            'printFormat' => $printFormat,
            'company' => $this->companyPrintPayload($payload['branch'] ?? null),
            'backUrl' => route('trail_balance.index'),
            'pdfUrl' => $urlBuilder->routeFromRequest('trail_balance.pdf', $request),
            'excelUrl' => $urlBuilder->routeFromRequest('trail_balance.excel', $request),
        ]);
    }

    public function trail_balance_pdf(
        Request $request,
        TrialBalanceReportPayloadBuilder $payloadBuilder,
        PrintFormatResolver $formatResolver
    ) {
        $payload = $payloadBuilder->build($request, $request->user('admin-web'));
        $printFormat = $formatResolver->resolve($request, 'a4', 'landscape');

        $pdf = DomPdf::loadView('admin.reports.trail_balance.print', $payload + [
            'printFormat' => $printFormat,
            'company' => $this->companyPrintPayload($payload['branch'] ?? null),
            'hidePrintActions' => true,
            'pdfMode' => true,
            'pdfUrl' => null,
            'excelUrl' => null,
            'backUrl' => null,
        ])
            // dompdf يقرأ أنماط الشاشة افتراضًا، فتضيع قواعد @media print التي
            // تُلغي هوامش الصفحة وظلّها — ومعها يخرج آخر عمود خارج الورقة.
            ->setOption('defaultMediaType', 'print')
            ->setPaper(strtolower($printFormat['format']) === 'a5' ? 'a5' : 'a4', $printFormat['orientation']);

        return $pdf->download('trial-balance-' . now()->format('Ymd-His') . '.pdf');
    }

    public function trail_balance_excel(Request $request, TrialBalanceReportPayloadBuilder $payloadBuilder)
    {
        $payload = $payloadBuilder->build($request, $request->user('admin-web'));

        return $this->reportExcel($this->tables()->trialBalance($payload));
    }

    public function reports_trial_balance_print(
        Request $request,
        TrialBalanceReportPayloadBuilder $payloadBuilder,
        PrintFormatResolver $formatResolver,
        PrintUrlBuilder $urlBuilder
    ) {
        return $this->trail_balance_print($request, $payloadBuilder, $formatResolver, $urlBuilder);
    }

    public function reports_trial_balance_pdf(
        Request $request,
        TrialBalanceReportPayloadBuilder $payloadBuilder,
        PrintFormatResolver $formatResolver
    ) {
        return $this->trail_balance_pdf($request, $payloadBuilder, $formatResolver);
    }

    private function companyPrintPayload(?Branch $branch): array
    {
        $user = auth('admin-web')->user();
        $subscriber = $user?->subscriber;

        return [
            'name' => $subscriber?->name ?: ($branch?->name ?: config('app.name')),
            'tax_number' => $branch?->tax_number,
            'commercial_register' => $branch?->commercial_register,
            'phone' => $branch?->phone ?: $subscriber?->contact_phone,
            'address' => $branch?->full_address,
        ];
    }

    /**
     * أدوات بناء جداول التصدير — تُحلّ عند الحاجة بدل إقحامها في توقيع كل دالة.
     */
    private function tables(): AccountingReportTables
    {
        return app(AccountingReportTables::class);
    }

    /**
     * صفحة عرض التقرير للطباعة، وفيها زرّا حفظ PDF و Excel — على منوال صفحة
     * ميزان المراجعة حتى لا تختلف التقارير في شكلها ولا في سلوكها.
     */
    private function reportPrintView(
        Request $request,
        ReportTable $table,
        ?Branch $branch,
        string $backRoute,
        string $pdfRoute,
        string $excelRoute,
        string $defaultOrientation = 'landscape'
    ) {
        $printFormat = app(PrintFormatResolver::class)->resolve($request, 'a4', $defaultOrientation);
        $urlBuilder = app(PrintUrlBuilder::class);

        return view('admin.reports.print', [
            'table' => $table,
            'printFormat' => $printFormat,
            'company' => $this->companyPrintPayload($branch),
            'backUrl' => route($backRoute),
            'pdfUrl' => $urlBuilder->routeFromRequest($pdfRoute, $request),
            'excelUrl' => $urlBuilder->routeFromRequest($excelRoute, $request),
        ]);
    }

    private function reportPdf(
        Request $request,
        ReportTable $table,
        ?Branch $branch,
        string $defaultOrientation = 'landscape'
    ) {
        $printFormat = app(PrintFormatResolver::class)->resolve($request, 'a4', $defaultOrientation);

        $pdf = DomPdf::loadView('admin.reports.print', [
            'table' => $table,
            'printFormat' => $printFormat,
            'company' => $this->companyPrintPayload($branch),
            'hidePrintActions' => true,
            'pdfMode' => true,
            'pdfUrl' => null,
            'excelUrl' => null,
            'backUrl' => null,
        ])
            // dompdf يقرأ أنماط الشاشة افتراضًا، فتضيع قواعد @media print التي
            // تُلغي هوامش الصفحة وظلّها — ومعها يخرج آخر عمود خارج الورقة.
            ->setOption('defaultMediaType', 'print')
            ->setPaper(strtolower($printFormat['format']) === 'a5' ? 'a5' : 'a4', $printFormat['orientation']);

        return $pdf->download($table->fileName . '-' . now()->format('Ymd-His') . '.pdf');
    }

    private function reportExcel(ReportTable $table): Response
    {
        $workbook = app(XlsxWriter::class)->build($table);
        $fileName = $table->fileName . '-' . now()->format('Ymd-His') . '.xlsx';

        return response($workbook, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => (string) strlen($workbook),
        ]);
    }

    /**
     * حمولة قائمة الدخل. تُبنى مرة واحدة ويقرأها العرض والطباعة و PDF و Excel،
     * فلا تفترق أرقام ملف عن أرقام شاشة.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function incomeStatementPayload(Request $request): array|RedirectResponse
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );
        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
        ];

        $accountLevel = $request->input('account_level') ? (int) $request->input('account_level') : null;

        $revenuesAccount = Account::where('parent_account_id', null)->where('account_type', 'revenues')->where('transfer_side', 'income_statement')->first();
        $expensesAccount = Account::where('parent_account_id', null)->where('account_type', 'expenses')->where('transfer_side', 'income_statement')->first();

        if (!$revenuesAccount || !$expensesAccount) {
            return redirect()->back()->with('error', 'Revenues or Expenses account not found');
        }

        $accountMetrics = $this->buildSummaryMetricsTree([
            $revenuesAccount,
            $expensesAccount,
        ], $filters);

        // صافي الربح = الإيرادات - المصروفات. الإيرادات دائنة فـ `closing_net`
        // سالب لها، لذلك يُعكس مجموعهما بالإشارة بدل أخذ القيمة المطلقة.
        $profitTotal = -(
            $accountMetrics[$revenuesAccount->id]['closing_net']
            + $accountMetrics[$expensesAccount->id]['closing_net']
        );

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];
        // When a specific branch is chosen, hide accounts with no movement in it
        // so other branches' accounts don't appear as empty (0.00) rows.
        $hideEmpty = $branchSelection['selects_all'] !== true;

        return compact(
            'periodFrom',
            'periodTo',
            'revenuesAccount',
            'expensesAccount',
            'profitTotal',
            'accountMetrics',
            'branch',
            'branchLabel',
            'hideEmpty',
            'accountLevel'
        );
    }

    public function income_statement()
    {
        return view('admin.reports.income_statement.search', $this->summaryReportFiltersData());
    }

    public function income_statement_search(Request $request)
    {
        $payload = $this->incomeStatementPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return view('admin.reports.income_statement.index', $payload);
    }

    public function income_statement_print(Request $request)
    {
        $payload = $this->incomeStatementPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportPrintView(
            $request,
            $this->tables()->incomeStatement($payload),
            $payload['branch'],
            'income_statement.index',
            'income_statement.pdf',
            'income_statement.excel',
            'portrait'
        );
    }

    public function income_statement_pdf(Request $request)
    {
        $payload = $this->incomeStatementPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportPdf($request, $this->tables()->incomeStatement($payload), $payload['branch'], 'portrait');
    }

    public function income_statement_excel(Request $request)
    {
        $payload = $this->incomeStatementPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportExcel($this->tables()->incomeStatement($payload));
    }

    public function cost_centers()
    {
        return view('admin.reports.cost_centers.search', $this->summaryReportFiltersData());
    }

    public function cost_centers_search(Request $request)
    {
        return view('admin.reports.cost_centers.index', $this->costCentersPayload($request));
    }

    public function cost_centers_print(Request $request)
    {
        return $this->reportPrintView(
            $request,
            $this->tables()->costCenters($this->costCentersPayload($request)),
            null,
            'cost_centers.index',
            'cost_centers.pdf',
            'cost_centers.excel'
        );
    }

    public function cost_centers_pdf(Request $request)
    {
        return $this->reportPdf($request, $this->tables()->costCenters($this->costCentersPayload($request)), null);
    }

    public function cost_centers_excel(Request $request)
    {
        return $this->reportExcel($this->tables()->costCenters($this->costCentersPayload($request)));
    }

    /**
     * قائمة الدخل موزّعة على الفروع. الفروع المعروضة هي المختارة، أو كل ما
     * يراه المستخدم حين لا يختار.
     *
     * @return array<string, mixed>
     */
    private function costCentersPayload(Request $request): array
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $accountLevel = $request->input('account_level') ? (int) $request->input('account_level') : null;

        $report = app(CostCenterReportBuilder::class)->build(
            $branchSelection['effective_branch_ids'],
            $periodFrom,
            $periodTo,
            $accountLevel
        );

        $branchLabel = $branchSelection['branch_label'];

        return compact('report', 'periodFrom', 'periodTo', 'branchLabel', 'accountLevel');
    }

    /**
     * حمولة الميزانية، مشتركة بين العرض والطباعة والتصدير.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function balanceSheetPayload(Request $request): array|RedirectResponse
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );
        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
        ];

        $accountLevel = $request->input('account_level') ? (int) $request->input('account_level') : null;

        $assetsAccount = Account::where('parent_account_id', null)->where('account_type', 'assets')->where('transfer_side', 'budget')->first();
        $equityAccount = Account::where('parent_account_id', null)->where('account_type', 'equity')->where('transfer_side', 'budget')->first();
        $liabilitiesAccount = Account::where('parent_account_id', null)->where('account_type', 'liabilities')->where('transfer_side', 'budget')->first();

        if (!$assetsAccount || !$equityAccount || !$liabilitiesAccount) {
            return redirect()->back()->with('error', 'Assets, Equity or Liabilities account not found');
        }

        $accountMetrics = $this->buildSummaryMetricsTree([
            $assetsAccount,
            $equityAccount,
            $liabilitiesAccount,
        ], $filters);

        // الأصول = الخصوم + حقوق الملكية + صافي الربح.
        // `closing_net` = مدين - دائن، فالأصول موجبة والخصوم وحقوق الملكية سالبة
        // في وضعها الطبيعي، ومن ثم يكون الربح مجموعها الجبري. الجمع بالإشارة لا
        // بالقيمة المطلقة: حساب على الجانب المعاكس (خسائر مبقاة مدينة مثلًا)
        // تقلبه `abs` فتنكسر المعادلة.
        $profitTotal = $accountMetrics[$assetsAccount->id]['closing_net']
            + $accountMetrics[$liabilitiesAccount->id]['closing_net']
            + $accountMetrics[$equityAccount->id]['closing_net'];

        // نتيجة الفترة ما زالت في حسابات الإيرادات والمصروفات ولم تُقفل بعد إلى
        // حقوق الملكية، فتُحمَّل على «حساب الربح أو الخسارة» عرضًا لا قيدًا —
        // وبها وحدها تتوازن الميزانية: مدينها يساوي دائنها.
        $accountMetrics = $this->carryPeriodResultToEquity($accountMetrics, $equityAccount, $profitTotal);

        $totals = $this->balanceSheetTotals($accountMetrics, [$assetsAccount, $liabilitiesAccount, $equityAccount]);

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];
        // عند اختيار فرع معيّن تُخفى الحسابات التي لا حركة لها فيه، وإلا ظهرت
        // حسابات الفروع الأخرى بأصفار داخل ميزانية فرع لا تخصّه.
        $hideEmpty = $branchSelection['selects_all'] !== true;

        return compact(
            'periodFrom',
            'periodTo',
            'assetsAccount',
            'equityAccount',
            'liabilitiesAccount',
            'profitTotal',
            'totals',
            'accountMetrics',
            'branch',
            'branchLabel',
            'accountLevel',
            'hideEmpty'
        );
    }

    /**
     * تُضاف نتيجة الفترة إلى «حساب الربح أو الخسارة» وإلى كل ما فوقه حتى جذر
     * حقوق الملكية، فتظهر حيث ينتظرها المحاسب بدل أن تتدلّى سطرًا وحده أسفل
     * التقرير.
     *
     * `$periodResult` هو مجموع الأصول والخصوم وحقوق الملكية بالإشارة، وهو
     * بحكم معادلة القيد المزدوج يساوي صافي ربح قائمة الدخل نفسه. يُحمَّل
     * بإشارته المعاكسة: الربح دائن يزيد حقوق الملكية، والخسارة مدينة تنقصها.
     * ولأنه محسوب من الأرصدة القائمة فهو لا يُضاعف شيئًا لو أُقفلت الفترة
     * فعلًا بقيد — عندها يصير صفرًا من تلقائه.
     *
     * @param  array<int, array<string, float>>  $accountMetrics
     * @return array<int, array<string, float>>
     */
    private function carryPeriodResultToEquity(array $accountMetrics, Account $equityRoot, float $periodResult): array
    {
        $carried = -$periodResult;

        if (abs($carried) < 0.005) {
            return $accountMetrics;
        }

        foreach ($this->periodResultCarriers($equityRoot) as $accountId) {
            if (! isset($accountMetrics[$accountId])) {
                continue;
            }

            if ($carried > 0) {
                $accountMetrics[$accountId]['closing_debit'] = round($accountMetrics[$accountId]['closing_debit'] + $carried, 2);
            } else {
                $accountMetrics[$accountId]['closing_credit'] = round($accountMetrics[$accountId]['closing_credit'] - $carried, 2);
            }

            $accountMetrics[$accountId]['closing_net'] = round($accountMetrics[$accountId]['closing_net'] + $carried, 2);
        }

        return $accountMetrics;
    }

    /**
     * الحسابات التي تحمل نتيجة الفترة: «حساب الربح أو الخسارة» وجذر حقوق
     * الملكية فوقه.
     *
     * يُعرَف الحساب من «حساب صافي الربح» المضبوط في إعدادات الحسابات، صعودًا
     * منه إلى أول ابن مباشر لجذر حقوق الملكية — لا من ترتيب الكود. فشجرة
     * الحسابات القديمة تجعل الكود «31» رأسَ المال الابتدائي وتضع الربح
     * والخسارة في «33»، فاستدلالٌ بالكود كان يحمّل نتيجة الفترة على رأس المال.
     *
     * وإن تعذّر التعرّف حُملت النتيجة على جذر حقوق الملكية وحده — تتوازن
     * الميزانية ولا يُمسّ حساب لا يخصّها.
     *
     * @return array<int, int>
     */
    private function periodResultCarriers(Account $equityRoot): array
    {
        $carrier = $this->profitAndLossAccount($equityRoot);

        return $carrier
            ? [(int) $carrier->id, (int) $equityRoot->id]
            : [(int) $equityRoot->id];
    }

    private function profitAndLossAccount(Account $equityRoot): ?Account
    {
        $profitAccountId = AccountSetting::query()
            ->whereNotNull('profit_account')
            ->value('profit_account');

        if (! $profitAccountId) {
            return null;
        }

        $account = Account::query()->find($profitAccountId);
        $rootId = (int) $equityRoot->id;

        // صعودًا حتى الابن المباشر لجذر حقوق الملكية. الحدّ الأقصى يحمي من
        // شجرة معطوبة تدور على نفسها.
        for ($depth = 0; $account && $depth < 20; $depth++) {
            if ((int) $account->id === $rootId) {
                return null; // حساب الربح هو الجذر نفسه، فلا وسيط تحته
            }

            if ((int) $account->parent_account_id === $rootId) {
                return $account;
            }

            $account = $account->parent;
        }

        return null;
    }

    /**
     * إجمالي المدين والدائن: يُجمعان من جذور الميزانية الثلاثة وحدها، فمجاميع
     * الآباء تشمل أبناءها ولو جُمع كل صف لتضاعفت الأرقام.
     *
     * @param  array<int, array<string, float>>  $accountMetrics
     * @param  array<int, Account>  $roots
     * @return array{debit: float, credit: float}
     */
    private function balanceSheetTotals(array $accountMetrics, array $roots): array
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($roots as $root) {
            $debit += (float) ($accountMetrics[$root->id]['closing_debit'] ?? 0);
            $credit += (float) ($accountMetrics[$root->id]['closing_credit'] ?? 0);
        }

        return ['debit' => round($debit, 2), 'credit' => round($credit, 2)];
    }

    public function balance_sheet()
    {
        return view('admin.reports.balance_sheet.search', $this->summaryReportFiltersData());
    }

    public function balance_sheet_search(Request $request)
    {
        $payload = $this->balanceSheetPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return view('admin.reports.balance_sheet.index', $payload);
    }

    public function balance_sheet_print(Request $request)
    {
        $payload = $this->balanceSheetPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportPrintView(
            $request,
            $this->tables()->balanceSheet($payload),
            $payload['branch'],
            'balance_sheet.index',
            'balance_sheet.pdf',
            'balance_sheet.excel',
            'portrait'
        );
    }

    public function balance_sheet_pdf(Request $request)
    {
        $payload = $this->balanceSheetPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportPdf($request, $this->tables()->balanceSheet($payload), $payload['branch'], 'portrait');
    }

    public function balance_sheet_excel(Request $request)
    {
        $payload = $this->balanceSheetPayload($request);

        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        return $this->reportExcel($this->tables()->balanceSheet($payload));
    }

    /**
     * حمولة حركة حساب، مشتركة بين العرض والطباعة والتصدير.
     *
     * @return array<string, mixed>
     */
    private function accountStatementPayload(Request $request): array
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'account_id' => (int) $request->input('account_id'),
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
            'user_id' => $this->normalizeOptionalFilter($request->input('user_id')),
            'invoice_number' => $this->normalizeOptionalFilter($request->input('invoice_number', $request->input('billNumber'))),
            'source_type' => $this->normalizeOptionalFilter($request->input('source_type')),
            'from_time' => $this->normalizeTime($request->input('from_time')),
            'to_time' => $this->normalizeTime($request->input('to_time')),
        ];

        $account = Account::query()->findOrFail($filters['account_id']);
        $openingBalance = $this->openingBalanceForAccountStatement($account, $filters);

        $documents = $this->accountStatementDocumentsQuery($account, $filters)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(function (JournalEntryDocument $document) {
                return $this->mapAccountStatementDocument($document);
            })
            ->values();

        return compact(
            'periodFrom',
            'periodTo',
            'account',
            'documents',
            'openingBalance',
            'branchSelection'
        );
    }

    public function account_statement()
    {
        $accounts = Account::query()->orderBy('code')->orderBy('id')->get();

        return view('admin.reports.account_statement.search', [
            'accounts' => $accounts,
        ] + $this->accountStatementFiltersData());
    }

    public function account_statement_search(Request $request)
    {
        return view('admin.reports.account_statement.index', $this->accountStatementPayload($request));
    }

    public function account_statement_print(Request $request)
    {
        $payload = $this->accountStatementPayload($request);

        return $this->reportPrintView(
            $request,
            $this->tables()->accountStatement($payload),
            $payload['branchSelection']['single_branch'] ?? null,
            'account_statement.index',
            'account_statement.pdf',
            'account_statement.excel'
        );
    }

    public function account_statement_pdf(Request $request)
    {
        $payload = $this->accountStatementPayload($request);

        return $this->reportPdf(
            $request,
            $this->tables()->accountStatement($payload),
            $payload['branchSelection']['single_branch'] ?? null
        );
    }

    public function account_statement_excel(Request $request)
    {
        return $this->reportExcel($this->tables()->accountStatement($this->accountStatementPayload($request)));
    }

    public function tax_declaration_print(Request $request)
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod($request, Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d'));

        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
            'user_id' => $this->normalizeOptionalFilter($request->input('user_id')),
            'invoice_number' => $this->normalizeOptionalFilter($request->input('invoice_number', $request->input('billNumber'))),
            'from_time' => $this->normalizeTime($request->input('from_time')),
            'to_time' => $this->normalizeTime($request->input('to_time')),
        ];

        $saleTotal = $this->taxDeclarationTotals('sale', 15, $filters);
        $saleReturnTotal = $this->taxDeclarationTotals('sale_return', 15, $filters);
        $salesTaxTotal = $saleTotal->tax_total - $saleReturnTotal->tax_total;
        $salesTotal = $saleTotal->total - $saleReturnTotal->total;

        $saleZeroTotal = $this->taxDeclarationTotals('sale', 0, $filters);
        $saleZeroReturnTotal = $this->taxDeclarationTotals('sale_return', 0, $filters);
        $salesZeroTaxTotal = $saleZeroTotal->tax_total - $saleZeroReturnTotal->tax_total;
        $salesZeroTotal = $saleZeroTotal->total - $saleZeroReturnTotal->total;
        $salesFinalTaxTotal = $salesTaxTotal + $salesZeroTaxTotal;
        $salesFinalTotal = $salesTotal + $salesZeroTotal;

        $purchaseTotalAggregate = $this->taxDeclarationTotals('purchase', 15, $filters);
        $purchaseReturnTotal = $this->taxDeclarationTotals('purchase_return', 15, $filters);
        $purchaseTaxTotal = $purchaseTotalAggregate->tax_total - $purchaseReturnTotal->tax_total;
        $purchaseTotal = $purchaseTotalAggregate->total - $purchaseReturnTotal->total;

        $purchaseZeroTotalAggregate = $this->taxDeclarationTotals('purchase', 0, $filters);
        $purchaseZeroReturnTotal = $this->taxDeclarationTotals('purchase_return', 0, $filters);
        $purchaseZeroTaxTotal = $purchaseZeroTotalAggregate->tax_total - $purchaseZeroReturnTotal->tax_total;
        $purchaseZeroTotal = $purchaseZeroTotalAggregate->total - $purchaseZeroReturnTotal->total;
        $purchaseFinalTaxTotal = $purchaseTaxTotal + $purchaseZeroTaxTotal;
        $purchaseFinalTotal = $purchaseTotal + $purchaseZeroTotal;
        $fullTaxTotal = $salesFinalTaxTotal - $purchaseFinalTaxTotal;
        $fullTotal = $salesFinalTotal - $purchaseFinalTotal;

        return view('admin.reports.tax_declaration.index', compact(
            'periodFrom', 'periodTo', 'salesTaxTotal', 'salesTotal', 'salesZeroTaxTotal', 'salesZeroTotal',
            'salesFinalTaxTotal', 'salesFinalTotal', 'purchaseTaxTotal', 'purchaseTotal',
            'purchaseZeroTaxTotal', 'purchaseZeroTotal', 'purchaseFinalTaxTotal', 'purchaseFinalTotal',
            'fullTaxTotal', 'fullTotal', 'branchSelection'
        ));
    }

    public function tax_declaration()
    {
        return view('admin.reports.tax_declaration.search', $this->taxDeclarationFiltersData());
    }

    public function tax_declaration_search(Request $request)
    {
        $branchSelection = $this->branchSelection($request);
        [$periodFrom, $periodTo] = $this->resolvePeriod($request, Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d'));

        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'branch_scope_all' => $branchSelection['selects_all'],
            'user_id' => $this->normalizeOptionalFilter($request->input('user_id')),
            'invoice_number' => $this->normalizeOptionalFilter($request->input('invoice_number', $request->input('billNumber'))),
            'from_time' => $this->normalizeTime($request->input('from_time')),
            'to_time' => $this->normalizeTime($request->input('to_time')),
        ];

        $saleTotal = $this->taxDeclarationTotals('sale', 15, $filters);
        $saleReturnTotal = $this->taxDeclarationTotals('sale_return', 15, $filters);

        $salesTaxTotal = $saleTotal->tax_total - $saleReturnTotal->tax_total;
        $salesTotal = $saleTotal->total - $saleReturnTotal->total;

        $saleZeroTotal = $this->taxDeclarationTotals('sale', 0, $filters);
        $saleZeroReturnTotal = $this->taxDeclarationTotals('sale_return', 0, $filters);

        $salesZeroTaxTotal = $saleZeroTotal->tax_total - $saleZeroReturnTotal->tax_total;
        $salesZeroTotal = $saleZeroTotal->total - $saleZeroReturnTotal->total;
        $salesFinalTaxTotal = $salesTaxTotal + $salesZeroTaxTotal;
        $salesFinalTotal = $salesTotal + $salesZeroTotal;

        $purchaseTotalAggregate = $this->taxDeclarationTotals('purchase', 15, $filters);
        $purchaseReturnTotal = $this->taxDeclarationTotals('purchase_return', 15, $filters);

        $purchaseTaxTotal = $purchaseTotalAggregate->tax_total - $purchaseReturnTotal->tax_total;
        $purchaseTotal = $purchaseTotalAggregate->total - $purchaseReturnTotal->total;

        $purchaseZeroTotalAggregate = $this->taxDeclarationTotals('purchase', 0, $filters);
        $purchaseZeroReturnTotal = $this->taxDeclarationTotals('purchase_return', 0, $filters);

        $purchaseZeroTaxTotal = $purchaseZeroTotalAggregate->tax_total - $purchaseZeroReturnTotal->tax_total;
        $purchaseZeroTotal = $purchaseZeroTotalAggregate->total - $purchaseZeroReturnTotal->total;

        $purchaseFinalTaxTotal = $purchaseTaxTotal + $purchaseZeroTaxTotal;
        $purchaseFinalTotal = $purchaseTotal + $purchaseZeroTotal;

        $fullTaxTotal = $salesFinalTaxTotal - $purchaseFinalTaxTotal;
        $fullTotal = $salesFinalTotal - $purchaseFinalTotal;

        return view('admin.reports.tax_declaration.index', compact(
            'periodFrom',
            'periodTo',
            'salesTaxTotal',
            'salesTotal',
            'salesZeroTaxTotal',
            'salesZeroTotal',
            'salesFinalTaxTotal',
            'salesFinalTotal',
            'purchaseTaxTotal',
            'purchaseTotal',
            'purchaseZeroTaxTotal',
            'purchaseZeroTotal',
            'purchaseFinalTaxTotal',
            'purchaseFinalTotal',
            'fullTaxTotal',
            'fullTotal',
            'branchSelection'
        ));
    }

    private function taxDeclarationFiltersData(): array
    {
        $today = Carbon::now()->format('Y-m-d');
        $branchSelection = $this->availableBranchSelection();

        return [
            'branches' => $branchSelection['branches']->where('status', 1)->values(),
            'users' => $this->usersQuery($branchSelection['visible_branch_ids'])->orderBy('name')->get(),
            'defaultFilters' => [
                'date_from' => $today,
                'date_to' => $today,
                'from_time' => '',
                'to_time' => '',
                'invoice_number' => '',
                'user_id' => '',
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
            ],
        ];
    }

    private function summaryReportFiltersData(): array
    {
        $branchSelection = $this->availableBranchSelection();

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

    private function accountStatementFiltersData(): array
    {
        $branchSelection = $this->availableBranchSelection();

        return [
            'branches' => $branchSelection['branches']->where('status', 1)->values(),
            'users' => $this->usersQuery($branchSelection['visible_branch_ids'])->orderBy('name')->get(),
            'defaultFilters' => [
                'date_from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'date_to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'from_time' => '',
                'to_time' => '',
                'invoice_number' => '',
                'source_type' => '',
                'user_id' => '',
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
                'account_id' => '',
            ],
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
     * @param  array<string, mixed>  $filters
     */
    private function taxDeclarationTotals(string $invoiceType, int $taxRate, array $filters): object
    {
        return InvoiceDetail::query()
            ->whereHas('tax', function ($query) use ($taxRate) {
                $query->where('rate', $taxRate);
            })
            ->whereHas('invoice', function ($query) use ($invoiceType, $filters) {
                $query->where('type', $invoiceType)
                    ->whereBetween('date', [$filters['period_from'], $filters['period_to']]);

                if (($filters['branch_ids'] ?? []) !== []) {
                    $query->whereIn('branch_id', $filters['branch_ids']);
                }

                if ($filters['user_id'] !== null) {
                    $query->where('user_id', $filters['user_id']);
                }

                if ($filters['invoice_number'] !== null) {
                    $query->where('bill_number', $filters['invoice_number']);
                }

                if ($filters['from_time'] !== null) {
                    $query->where('time', '>=', $filters['from_time']);
                }

                if ($filters['to_time'] !== null) {
                    $query->where('time', '<=', $filters['to_time']);
                }
            })
            ->select(DB::raw('COALESCE(SUM(line_tax), 0) as tax_total, COALESCE(SUM(line_total), 0) as total'))
            ->first();
    }

    private function accountStatementDocumentsQuery(Account $account, array $filters)
    {
        $query = JournalEntryDocument::query()
            ->with(['journal_entry.branch', 'journal_entry.journalable'])
            ->whereIn('account_id', $account->childrensIds)
            ->whereBetween('document_date', [$filters['period_from'], $filters['period_to']]);

        $this->applyAccountStatementFilters($query, $filters);

        return $query;
    }

    private function applyAccountStatementFilters($query, array $filters): void
    {
        // When "all branches" is selected we must NOT restrict by branch_id:
        // effective_branch_ids is the full subscriber branch list, and any entry
        // stamped with a NULL/foreign branch (e.g. legacy manual journal entries)
        // would be silently dropped by whereIn — the exact "posted entry not
        // showing in the statement" bug. Account scoping already keeps the query
        // within the current subscriber, so skipping the branch gate is safe.
        if (empty($filters['branch_scope_all']) && ($filters['branch_ids'] ?? []) !== []) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                $journalQuery->whereIn('branch_id', $filters['branch_ids']);
            });
        }

        if ($filters['source_type'] !== null) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                match ($filters['source_type']) {
                    'manual' => $journalQuery->whereNull('journalable_type'),
                    'invoice' => $journalQuery->whereHasMorph('journalable', [Invoice::class]),
                    'voucher' => $journalQuery->whereHasMorph('journalable', [FinancialVoucher::class]),
                    default => null,
                };
            });
        }

        if ($filters['invoice_number'] !== null) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                $journalQuery->where(function ($referenceQuery) use ($filters) {
                    $referenceQuery
                        ->where('serial', $filters['invoice_number'])
                        ->orWhereHasMorph('journalable', [Invoice::class], function ($invoiceQuery) use ($filters) {
                            $invoiceQuery->where('bill_number', $filters['invoice_number']);
                        })
                        ->orWhereHasMorph('journalable', [FinancialVoucher::class], function ($voucherQuery) use ($filters) {
                            $voucherQuery->where('bill_number', $filters['invoice_number']);
                        });
                });
            });
        }

        if ($filters['user_id'] !== null) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                $journalQuery->whereHasMorph('journalable', [Invoice::class], function ($invoiceQuery) use ($filters) {
                    $invoiceQuery->where('user_id', $filters['user_id']);
                });
            });
        }

        if ($filters['from_time'] !== null) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                $journalQuery->whereHasMorph('journalable', [Invoice::class], function ($invoiceQuery) use ($filters) {
                    $invoiceQuery->where('time', '>=', $filters['from_time']);
                });
            });
        }

        if ($filters['to_time'] !== null) {
            $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                $journalQuery->whereHasMorph('journalable', [Invoice::class], function ($invoiceQuery) use ($filters) {
                    $invoiceQuery->where('time', '<=', $filters['to_time']);
                });
            });
        }
    }

    /**
     * @return array{debit:float,credit:float,net:float}
     */
    private function openingBalanceForAccountStatement(Account $account, array $filters): array
    {
        $debit = 0.0;
        $credit = 0.0;

        if ($this->canUseGlobalOpeningBalance($filters)) {
            $openingTotals = DB::table('opening_balances')
                ->whereIn('account_id', $account->childrensIds)
                ->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))
                ->first();

            $debit += (float) ($openingTotals->debit_total ?? 0);
            $credit += (float) ($openingTotals->credit_total ?? 0);
        }

        $query = JournalEntryDocument::query()
            ->whereIn('account_id', $account->childrensIds)
            ->where(function ($builder) use ($filters) {
                $builder->where('document_date', '<', $filters['period_from']);

                if ($filters['from_time'] !== null) {
                    $builder->orWhere(function ($sameDayQuery) use ($filters) {
                        $sameDayQuery
                            ->where('document_date', $filters['period_from'])
                            ->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                                $journalQuery->whereHasMorph('journalable', [Invoice::class], function ($invoiceQuery) use ($filters) {
                                    $invoiceQuery->where('time', '<', $filters['from_time']);
                                });
                            });
                    });
                }
            });

        $this->applyAccountStatementFilters($query, $filters);

        $totals = $query->select(DB::raw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total'))->first();

        $debit += (float) ($totals->debit_total ?? 0);
        $credit += (float) ($totals->credit_total ?? 0);

        return [
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'net' => round($debit - $credit, 2),
        ];
    }

    private function canUseGlobalOpeningBalance(array $filters): bool
    {
        return ($filters['branch_scope_all'] ?? false) === true
            && $filters['user_id'] === null
            && $filters['invoice_number'] === null
            && $filters['source_type'] === null
            && $filters['from_time'] === null
            && $filters['to_time'] === null;
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
     * @param  array<int, Account>  $roots
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, float>>
     */
    private function buildSummaryMetricsTree(array $roots, array $filters): array
    {
        $metrics = [];

        foreach ($roots as $root) {
            foreach (Account::query()->whereIn('id', $root->childrensIds)->get() as $account) {
                $metrics[$account->id] = $this->buildSummaryMetricsForAccount($account, $filters);
            }
        }

        return $metrics;
    }

    private function summaryDocumentsQuery(Account $account, array $filters)
    {
        return JournalEntryDocument::query()
            ->whereIn('account_id', $account->childrensIds)
            ->when(empty($filters['branch_scope_all']) && ($filters['branch_ids'] ?? []) !== [], function ($query) use ($filters) {
                return $query->whereHas('journal_entry', function ($journalQuery) use ($filters) {
                    $journalQuery->whereIn('branch_id', $filters['branch_ids']);
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAccountStatementDocument(JournalEntryDocument $document): array
    {
        $journal = $document->journal_entry;
        $source = $journal?->journalable;
        $isInvoice = $source instanceof Invoice;
        $isVoucher = $source instanceof FinancialVoucher;

        return [
            'id' => $document->id,
            'date' => $document->document_date,
            'time' => $isInvoice ? $source->time : null,
            'branch_name' => $journal?->branch?->name ?? '-',
            'user_name' => $isInvoice ? ($source->user?->name ?? '-') : '-',
            'source_type' => $isInvoice ? 'invoice' : ($isVoucher ? 'voucher' : 'manual'),
            'source_type_label' => $isInvoice ? 'فاتورة' : ($isVoucher ? 'سند مالي' : 'قيد يدوي'),
            'reference_number' => $isInvoice || $isVoucher ? ($source->bill_number ?? $journal?->serial) : ($journal?->serial ?? '-'),
            'document_label' => $journal?->custom_notes ?? $document->notes ?? '-',
            'debit' => (float) $document->debit,
            'credit' => (float) $document->credit,
        ];
    }

    private function normalizeOptionalFilter($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }

    private function normalizeTime(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function branchesQuery()
    {
        $visibleBranchIds = $this->availableBranchSelection()['visible_branch_ids'];

        return Branch::query()
            ->when($visibleBranchIds !== [], fn ($query) => $query->whereIn('id', $visibleBranchIds));
    }

    private function usersQuery(array $visibleBranchIds = [])
    {
        $user = auth('admin-web')->user();

        return User::query()
            ->when(
                filled($user?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $user->subscriber_id)
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function availableBranchSelection(): array
    {
        $request = Request::create('/', 'GET');

        return app(ReportBranchSelectionService::class)->resolve($request, auth('admin-web')->user());
    }

    /**
     * @return array<string, mixed>
     */
    private function branchSelection(Request $request): array
    {
        return app(ReportBranchSelectionService::class)->resolve($request, auth('admin-web')->user());
    }
}
