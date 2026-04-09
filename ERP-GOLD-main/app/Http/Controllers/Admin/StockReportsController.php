<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\Subscriber;
use App\Services\Reports\ReportBranchSelectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class StockReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.inventory_reports.show,admin-web');
    }

    public function sales_report_search()
    {
        return view('admin.reports.stock_reports.sales_report.search', $this->stockReportFiltersData(
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        ));
    }

    public function sales_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        $details = InvoiceDetail::with(['invoice.customer', 'item', 'carat'])
            ->whereHas('invoice', function ($query) use ($request, $periodFrom, $periodTo, $branchSelection) {
                $query->where('type', 'sale');
                $this->applyInvoiceFilters($query, $request, $branchSelection, $periodFrom, $periodTo);
            })
            ->get();

        return view('admin.reports.stock_reports.sales_report.index', compact('periodFrom', 'periodTo', 'branch', 'branchLabel', 'details'));
    }

    public function sales_total_report_search()
    {
        return view('admin.reports.stock_reports.sales_total_report.search', $this->stockReportFiltersData(
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        ));
    }

    public function sales_total_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        $sales = Invoice::with('customer')
            ->where('type', 'sale');
        $this->applyInvoiceFilters($sales, $request, $branchSelection, $periodFrom, $periodTo);
        $sales = $sales->get();

        $salesByCarat = InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', 'sale');
        $this->applyInvoiceFilters($salesByCarat, $request, $branchSelection, $periodFrom, $periodTo, 'invoices');

        $sales_by_carat = $salesByCarat
            ->selectRaw('
                invoice_details.gold_carat_id as gold_carat_id,
                gold_carats.title as carat_title_raw,
                SUM(invoice_details.out_quantity) as total_quantity,
                SUM(invoice_details.out_weight) as total_weight,
                SUM(invoice_details.line_total) as total_line_total,
                SUM(invoice_details.line_tax) as total_taxes_total,
                SUM(invoice_details.net_total) as total_net_total
            ')
            ->groupBy('invoice_details.gold_carat_id', 'gold_carats.title')
            ->get()
            ->map(function ($row) {
                $row->carat_title = $this->translatedTitle($row->carat_title_raw);

                return $row;
            });

        return view('admin.reports.stock_reports.sales_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'branchLabel', 'sales', 'sales_by_carat'));
    }

    public function sales_return_total_report_search()
    {
        return view('admin.reports.stock_reports.sales_return_total_report.search', $this->stockReportFiltersData(
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        ));
    }

    public function sales_return_total_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        $sales_return = Invoice::with('customer')
            ->where('type', 'sale_return');
        $this->applyInvoiceFilters($sales_return, $request, $branchSelection, $periodFrom, $periodTo);
        $sales_return = $sales_return->get();

        $salesReturnByCarat = InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', 'sale_return');
        $this->applyInvoiceFilters($salesReturnByCarat, $request, $branchSelection, $periodFrom, $periodTo, 'invoices');

        $sales_return_by_carat = $salesReturnByCarat
            ->selectRaw('
                invoice_details.gold_carat_id as gold_carat_id,
                gold_carats.title as carat_title_raw,
                SUM(invoice_details.in_quantity) as total_quantity,
                SUM(invoice_details.in_weight) as total_weight,
                SUM(invoice_details.line_total) as total_line_total,
                SUM(invoice_details.line_tax) as total_taxes_total,
                SUM(invoice_details.net_total) as total_net_total
            ')
            ->groupBy('invoice_details.gold_carat_id', 'gold_carats.title')
            ->get()
            ->map(function ($row) {
                $row->carat_title = $this->translatedTitle($row->carat_title_raw);

                return $row;
            });

        return view('admin.reports.stock_reports.sales_return_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'branchLabel', 'sales_return', 'sales_return_by_carat'));
    }

    public function purchases_report_search()
    {
        return view('admin.reports.stock_reports.purchases_report.search', $this->stockReportFiltersData(
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        ));
    }

    public function purchases_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        $details = InvoiceDetail::with(['invoice.customer', 'item', 'carat'])
            ->whereHas('invoice', function ($query) use ($request, $periodFrom, $periodTo, $branchSelection) {
                $query->where('type', 'purchase');
                $this->applyInvoiceFilters($query, $request, $branchSelection, $periodFrom, $periodTo);
            })
            ->get();

        return view('admin.reports.stock_reports.purchases_report.index', compact('periodFrom', 'periodTo', 'branch', 'branchLabel', 'details'));
    }

    public function purchases_total_report_search()
    {
        return view('admin.reports.stock_reports.purchases_total_report.search', $this->stockReportFiltersData(
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        ));
    }

    public function purchases_total_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];

        $purchases = Invoice::with('customer')
            ->where('type', 'purchase');
        $this->applyInvoiceFilters($purchases, $request, $branchSelection, $periodFrom, $periodTo);
        $purchases = $purchases->get();

        return view('admin.reports.stock_reports.purchases_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'branchLabel', 'purchases'));
    }

    public function daily_carat_report_search()
    {
        return view('admin.reports.stock_reports.daily_carat_report.search', $this->stockReportFiltersData(
            Carbon::now()->format('Y-m-d'),
            Carbon::now()->format('Y-m-d'),
            true
        ));
    }

    public function daily_carat_report(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->format('Y-m-d'),
            Carbon::now()->format('Y-m-d'),
        );
        $branchSelection = $this->branchSelection($request);
        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];
        $user = $this->selectedUser($request, $branchSelection['visible_branch_ids']);
        $carat = $request->filled('carat_id') ? GoldCarat::find($request->carat_id) : null;
        $fromTime = $this->normalizeTime($request->input('from_time'));
        $toTime = $this->normalizeTime($request->input('to_time'));

        $rowsQuery = InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->leftJoin('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->whereIn('invoices.type', ['sale', 'purchase', 'sale_return', 'purchase_return'])
            ->when($request->carat_id, function ($query) use ($request) {
                return $query->where('invoice_details.gold_carat_id', $request->carat_id);
            });
        $this->applyInvoiceFilters($rowsQuery, $request, $branchSelection, $periodFrom, $periodTo, 'invoices');

        $rows = $rowsQuery
            ->selectRaw('
                invoice_details.date as operation_date,
                invoices.type as operation_type,
                invoice_details.gold_carat_id as gold_carat_id,
                gold_carats.title as carat_title_raw,
                COUNT(invoice_details.id) as line_count,
                COUNT(DISTINCT invoices.id) as invoice_count,
                SUM(invoice_details.in_weight) as total_in_weight,
                SUM(invoice_details.out_weight) as total_out_weight,
                SUM(invoice_details.line_total) as total_line_total,
                SUM(invoice_details.line_tax) as total_tax_total,
                SUM(invoice_details.net_total) as total_net_total
            ')
            ->groupBy('invoice_details.date', 'invoices.type', 'invoice_details.gold_carat_id', 'gold_carats.title')
            ->orderByDesc('invoice_details.date')
            ->orderBy('invoices.type')
            ->orderBy('gold_carats.label')
            ->get()
            ->map(function ($row) {
                $row->operation_label = $this->operationLabel($row->operation_type);
                $row->carat_title = $this->translatedTitle($row->carat_title_raw);
                $row->total_in_weight = round((float) $row->total_in_weight, 3);
                $row->total_out_weight = round((float) $row->total_out_weight, 3);
                $row->total_line_total = round((float) $row->total_line_total, 2);
                $row->total_tax_total = round((float) $row->total_tax_total, 2);
                $row->total_net_total = round((float) $row->total_net_total, 2);

                return $row;
            });

        $operationSummary = $rows
            ->groupBy('operation_type')
            ->map(function ($group, $operationType) {
                return [
                    'operation_type' => $operationType,
                    'operation_label' => $this->operationLabel($operationType),
                    'days_count' => $group->pluck('operation_date')->unique()->count(),
                    'invoice_count' => $group->sum('invoice_count'),
                    'line_count' => $group->sum('line_count'),
                    'total_in_weight' => round((float) $group->sum('total_in_weight'), 3),
                    'total_out_weight' => round((float) $group->sum('total_out_weight'), 3),
                    'total_line_total' => round((float) $group->sum('total_line_total'), 2),
                    'total_tax_total' => round((float) $group->sum('total_tax_total'), 2),
                    'total_net_total' => round((float) $group->sum('total_net_total'), 2),
                ];
            })
            ->values();

        $dailyTotals = $rows
            ->groupBy('operation_date')
            ->map(function ($group, $date) {
                return [
                    'operation_date' => $date,
                    'invoice_count' => $group->sum('invoice_count'),
                    'line_count' => $group->sum('line_count'),
                    'total_in_weight' => round((float) $group->sum('total_in_weight'), 3),
                    'total_out_weight' => round((float) $group->sum('total_out_weight'), 3),
                    'total_line_total' => round((float) $group->sum('total_line_total'), 2),
                    'total_tax_total' => round((float) $group->sum('total_tax_total'), 2),
                    'total_net_total' => round((float) $group->sum('total_net_total'), 2),
                ];
            })
            ->sortByDesc('operation_date')
            ->values();

        return view('admin.reports.stock_reports.daily_carat_report.index', compact(
            'periodFrom',
            'periodTo',
            'branch',
            'branchLabel',
            'user',
            'carat',
            'fromTime',
            'toTime',
            'rows',
            'operationSummary',
            'dailyTotals'
        ));
    }

    private function operationLabel(string $type): string
    {
        return match ($type) {
            'sale' => 'بيع',
            'sale_return' => 'مرتجع بيع',
            'purchase' => 'شراء',
            'purchase_return' => 'مرتجع شراء',
            default => $type,
        };
    }

    private function translatedTitle(?string $value): string
    {
        if (blank($value)) {
            return 'بدون عيار';
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? $decoded['ar'] ?? $decoded['en'] ?? reset($decoded) ?: 'بدون عيار';
        }

        return $value;
    }

    private function stockReportFiltersData(string $defaultDateFrom, string $defaultDateTo, bool $includeCarats = false): array
    {
        $branchSelection = $this->availableBranchSelection();
        $userFilterOptions = $this->stockReportUserFilterOptions($branchSelection['visible_branch_ids']);

        $data = [
            'branches' => $branchSelection['branches']->where('status', 1)->values(),
            'users' => $userFilterOptions['users'],
            'userFilterLocked' => $userFilterOptions['locked'],
            'defaultFilters' => [
                'date_from' => $defaultDateFrom,
                'date_to' => $defaultDateTo,
                'from_time' => '',
                'to_time' => '',
                'invoice_number_from' => '',
                'invoice_number_to' => '',
                'netMoney' => '',
                'user_id' => $userFilterOptions['selected_user_id'],
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
                'carat_id' => '',
            ],
        ];

        if ($includeCarats) {
            $data['carats'] = GoldCarat::orderBy('id')->get();
        }

        return $data;
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

    private function applyInvoiceFilters($query, Request $request, array $branchSelection, string $periodFrom, string $periodTo, string $table = 'invoices'): void
    {
        $userId = $this->resolvedReportUserId($request, $branchSelection['visible_branch_ids'] ?? []);
        $invoiceNumber = $this->normalizeOptionalFilter($request->input('invoice_number', $request->input('billNumber')));
        $fromBillNumber = $this->normalizeOptionalFilter($request->input('invoice_number_from', $request->input('FromBillNumber', $invoiceNumber)));
        $toBillNumber = $this->normalizeOptionalFilter($request->input('invoice_number_to', $request->input('ToBillNumber', $invoiceNumber)));
        $netMoney = $this->normalizeOptionalFilter($request->input('netMoney'));
        $fromTime = $this->normalizeTime($request->input('from_time'));
        $toTime = $this->normalizeTime($request->input('to_time'));

        $query->whereBetween($table . '.date', [$periodFrom, $periodTo]);

        if ($branchSelection['effective_branch_ids'] !== []) {
            $query->whereIn($table . '.branch_id', $branchSelection['effective_branch_ids']);
        }

        if ($userId !== null) {
            $query->where($table . '.user_id', $userId);
        }

        if ($fromBillNumber !== null || $toBillNumber !== null) {
            if ($fromBillNumber !== null && $toBillNumber !== null) {
                $query->whereBetween($table . '.bill_number', [$fromBillNumber, $toBillNumber]);
            } elseif ($fromBillNumber !== null) {
                $query->where($table . '.bill_number', '>=', $fromBillNumber);
            } else {
                $query->where($table . '.bill_number', '<=', $toBillNumber);
            }
        }

        if ($netMoney !== null) {
            $query->where($table . '.net_total', $netMoney);
        }

        if ($fromTime !== null) {
            $query->where($table . '.time', '>=', $fromTime);
        }

        if ($toTime !== null) {
            $query->where($table . '.time', '<=', $toTime);
        }
    }

    private function normalizeOptionalFilter($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' || $value === '0' || $value === 0 ? null : $value;
    }

    private function normalizeTime(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function selectedBranch(Request $request): ?Branch
    {
        return $this->branchSelection($request)['single_branch'];
    }

    private function selectedUser(Request $request, array $visibleBranchIds = []): ?User
    {
        $userId = $this->resolvedReportUserId($request, $visibleBranchIds);

        return $userId ? $this->usersQuery($visibleBranchIds)->find($userId) : null;
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
                filled($user?->subscriber_id) && ! $this->isSubscriberPrimaryAccount($user),
                fn ($query) => $query->whereKey($user->id)
            )
            ->when(
                filled($user?->subscriber_id) && $this->isSubscriberPrimaryAccount($user),
                fn ($query) => $query->where('subscriber_id', $user->subscriber_id)
            );
    }

    /**
     * @return array{users:\Illuminate\Support\Collection<int,User>,locked:bool,selected_user_id:string}
     */
    private function stockReportUserFilterOptions(array $visibleBranchIds = []): array
    {
        $user = auth('admin-web')->user();
        $users = $this->usersQuery($visibleBranchIds)->orderBy('name')->get();
        $locked = filled($user?->subscriber_id) && ! $this->isSubscriberPrimaryAccount($user);

        return [
            'users' => $users,
            'locked' => $locked,
            'selected_user_id' => $locked && $user ? (string) $user->id : '',
        ];
    }

    private function resolvedReportUserId(Request $request, array $visibleBranchIds = []): ?int
    {
        $user = auth('admin-web')->user();

        if ($user && filled($user->subscriber_id) && ! $this->isSubscriberPrimaryAccount($user)) {
            return (int) $user->id;
        }

        $userId = $this->normalizeOptionalFilter($request->input('user_id'));

        if ($userId === null) {
            return null;
        }

        return $this->usersQuery($visibleBranchIds)->whereKey((int) $userId)->exists()
            ? (int) $userId
            : null;
    }

    private function isSubscriberPrimaryAccount(?User $user): bool
    {
        if (! $user || blank($user->subscriber_id)) {
            return false;
        }

        $subscriber = $user->relationLoaded('subscriber')
            ? $user->subscriber
            : Subscriber::query()->select('id', 'admin_user_id')->find($user->subscriber_id);

        return (int) ($subscriber?->admin_user_id ?? 0) === (int) $user->id;
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
