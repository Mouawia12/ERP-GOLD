<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\JournalEntryDocument;
use App\Models\User;
use App\Services\Printing\PrintFormatResolver;
use App\Services\Printing\PrintUrlBuilder;
use App\Services\Reports\ReportBranchSelectionService;
use App\Services\Reports\TrialBalanceReportPayloadBuilder;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            'pdfUrl' => null,
            'backUrl' => null,
        ])->setPaper(strtolower($printFormat['format']) === 'a5' ? 'a5' : 'a4', $printFormat['orientation']);

        return $pdf->download('trial-balance-' . now()->format('Ymd-His') . '.pdf');
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

    public function income_statement_print(Request $request)
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

        $revenuesAccount = Account::where('parent_account_id', null)->where('account_type', 'revenues')->where('transfer_side', 'income_statement')->first();
        $expensesAccount = Account::where('parent_account_id', null)->where('account_type', 'expenses')->where('transfer_side', 'income_statement')->first();

        if (!$revenuesAccount || !$expensesAccount) {
            return redirect()->back()->with('error', 'Revenues or Expenses account not found');
        }

        $accountMetrics = $this->buildSummaryMetricsTree([
            $revenuesAccount,
            $expensesAccount,
        ], $filters);

        $profitTotal = abs($accountMetrics[$revenuesAccount->id]['closing_net'])
            - abs($accountMetrics[$expensesAccount->id]['closing_net']);

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        return view('admin.reports.income_statement.index', compact(
            'periodFrom', 'periodTo', 'revenuesAccount', 'expensesAccount',
            'profitTotal', 'accountMetrics', 'branch', 'branchLabel'
        ));
    }

    public function income_statement()
    {
        return view('admin.reports.income_statement.search', $this->summaryReportFiltersData());
    }

    public function income_statement_search(Request $request)
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

        $revenuesAccount = Account::where('parent_account_id', null)->where('account_type', 'revenues')->where('transfer_side', 'income_statement')->first();
        $expensesAccount = Account::where('parent_account_id', null)->where('account_type', 'expenses')->where('transfer_side', 'income_statement')->first();

        if (!$revenuesAccount || !$expensesAccount) {
            return redirect()->back()->with('error', 'Revenues or Expenses account not found');
        }

        $accountMetrics = $this->buildSummaryMetricsTree([
            $revenuesAccount,
            $expensesAccount,
        ], $filters);

        $profitTotal = abs($accountMetrics[$revenuesAccount->id]['closing_net'])
            - abs($accountMetrics[$expensesAccount->id]['closing_net']);

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        return view('admin.reports.income_statement.index', compact(
            'periodFrom',
            'periodTo',
            'revenuesAccount',
            'expensesAccount',
            'profitTotal',
            'accountMetrics',
            'branch',
            'branchLabel'
        ));
    }

    public function balance_sheet_print(Request $request)
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

        $assetsAccount = Account::where('parent_account_id', null)->where('account_type', 'assets')->where('transfer_side', 'budget')->first();
        $equityAccount = Account::where('parent_account_id', null)->where('account_type', 'equity')->where('transfer_side', 'budget')->first();
        $liabilitiesAccount = Account::where('parent_account_id', null)->where('account_type', 'liabilities')->where('transfer_side', 'budget')->first();

        if (!$assetsAccount || !$equityAccount || !$liabilitiesAccount) {
            return redirect()->back()->with('error', 'Assets, Equity or Liabilities account not found');
        }

        $accountMetrics = $this->buildSummaryMetricsTree([
            $assetsAccount, $equityAccount, $liabilitiesAccount,
        ], $filters);

        $assetsTotal = abs($accountMetrics[$assetsAccount->id]['closing_net']);
        $liabilitiesTotal = abs($accountMetrics[$liabilitiesAccount->id]['closing_net']);
        $equityTotal = abs($accountMetrics[$equityAccount->id]['closing_net']);
        $profitTotal = $assetsTotal - ($liabilitiesTotal + $equityTotal);

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        return view('admin.reports.balance_sheet.index', compact(
            'periodFrom', 'periodTo', 'assetsAccount', 'equityAccount', 'liabilitiesAccount',
            'profitTotal', 'accountMetrics', 'branch', 'branchLabel'
        ));
    }

    public function balance_sheet()
    {
        return view('admin.reports.balance_sheet.search', $this->summaryReportFiltersData());
    }

    public function balance_sheet_search(Request $request)
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

        $assetsTotal = abs($accountMetrics[$assetsAccount->id]['closing_net']);
        $liabilitiesTotal = abs($accountMetrics[$liabilitiesAccount->id]['closing_net']);
        $equityTotal = abs($accountMetrics[$equityAccount->id]['closing_net']);
        $profitTotal = $assetsTotal - ($liabilitiesTotal + $equityTotal);

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        return view('admin.reports.balance_sheet.index', compact(
            'periodFrom',
            'periodTo',
            'assetsAccount',
            'equityAccount',
            'liabilitiesAccount',
            'profitTotal',
            'accountMetrics',
            'branch',
            'branchLabel'
        ));
    }

    public function account_statement_print(Request $request)
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

        return view('admin.reports.account_statement.index', compact(
            'periodFrom', 'periodTo', 'account', 'documents', 'openingBalance', 'branchSelection'
        ));
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

        return view('admin.reports.account_statement.index', compact(
            'periodFrom',
            'periodTo',
            'account',
            'documents',
            'openingBalance',
            'branchSelection'
        ));
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
        if (($filters['branch_ids'] ?? []) !== []) {
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
            ->when(($filters['branch_ids'] ?? []) !== [], function ($query) use ($filters) {
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
