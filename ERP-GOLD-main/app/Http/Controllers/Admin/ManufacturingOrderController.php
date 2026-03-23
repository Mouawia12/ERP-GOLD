<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Branches\BranchAccessService;
use App\Services\Manufacturing\ManufacturingOrderService;
use App\Services\Manufacturing\ManufacturingReceiptService;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManufacturingOrderController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly ManufacturingOrderService $manufacturingOrderService,
        private readonly ManufacturingReceiptService $manufacturingReceiptService,
    ) {
        $this->middleware('permission:employee.manufacturing_orders.show,admin-web')->only(['index', 'show']);
        $this->middleware('permission:employee.manufacturing_orders.add,admin-web')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with([
                'branch',
                'user',
                'customer',
                'details',
                'manufacturingReceipts.details',
                'manufacturingReturns.details',
                'manufacturingLossSettlements.manufacturingLossSettlementLines',
            ])
            ->where('type', 'manufacturing_order');

        $this->branchAccessService->scopeToAccessibleBranch($query, $request->user('admin-web'));
        $orders = $query->latest('id')->get();
        $statusSummary = $this->statusSummary($orders);
        $selectedStatus = $request->input('status', 'all');
        $filteredOrders = $this->applyStatusFilter($orders, $selectedStatus);

        if ($request->ajax()) {
            return Datatables::of($filteredOrders)
                ->addIndexColumn()
                ->addColumn('manufacturer_name', fn (Invoice $invoice) => $invoice->customer?->name ?? $invoice->bill_client_name ?? '-')
                ->addColumn('branch_name', fn (Invoice $invoice) => $invoice->branch?->name ?? '-')
                ->addColumn('user_name', fn (Invoice $invoice) => $invoice->user?->name ?? '-')
                ->addColumn('total_weight', fn (Invoice $invoice) => number_format((float) $invoice->details->sum('out_weight'), 3))
                ->addColumn('received_weight', fn (Invoice $invoice) => number_format((float) $this->orderMetrics($invoice)['received_weight'], 3))
                ->addColumn('settled_weight', fn (Invoice $invoice) => number_format((float) $this->orderMetrics($invoice)['settled_weight'], 3))
                ->addColumn('remaining_weight', fn (Invoice $invoice) => number_format((float) $this->orderMetrics($invoice)['remaining_weight'], 3))
                ->addColumn('status_badge', function (Invoice $invoice) {
                    $metrics = $this->orderMetrics($invoice);
                    $badgeClass = match ($metrics['status']) {
                        'completed' => 'success',
                        'late' => 'danger',
                        default => 'warning',
                    };

                    return '<span class="badge badge-' . $badgeClass . '">' . $metrics['status_label'] . '</span>';
                })
                ->addColumn('action', function (Invoice $invoice) {
                    $buttons = '<a href="' . route('manufacturing_orders.show', $invoice->id) . '" class="btn btn-sm btn-success mr-1"><i class="fa fa-eye"></i></a>';
                    if ($this->orderMetrics($invoice)['remaining_weight'] > 0) {
                        $buttons .= '<a href="' . route('manufacturing_receipts.create', $invoice->id) . '" class="btn btn-sm btn-warning mr-1"><i class="fa fa-download"></i></a>';
                        $buttons .= '<a href="' . route('manufacturing_loss_settlements.create', $invoice->id) . '" class="btn btn-sm btn-danger"><i class="fa fa-balance-scale"></i></a>';
                    }
                    if ($this->canCreateReturn($invoice)) {
                        $buttons .= '<a href="' . route('manufacturing_returns.create', $invoice->id) . '" class="btn btn-sm btn-info"><i class="fa fa-exchange"></i></a>';
                    }

                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('admin.manufacturing_orders.index', compact('statusSummary', 'selectedStatus'));
    }

    public function create(Request $request)
    {
        $user = $request->user('admin-web');
        $branches = $this->branchAccessService->visibleBranches($user);
        $currentBranchId = $this->resolvedCurrentBranchId($branches, $user?->branch_id);
        $itemsByBranch = $this->itemsByBranch($branches);
        $manufacturers = Customer::query()->where('type', 'supplier')->orderBy('name')->get();
        $accounts = Account::query()->whereDoesntHave('childrens')->orderBy('name')->get();
        $prefilledLines = $this->prefilledLinesFromOldInput();

        return view('admin.manufacturing_orders.create', compact(
            'branches',
            'currentBranchId',
            'itemsByBranch',
            'manufacturers',
            'accounts',
            'prefilledLines'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'manufacturer_id' => ['required', 'exists:customers,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['nullable', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0.001'],
            'weight' => ['required', 'array'],
            'weight.*' => ['nullable', 'numeric', 'min:0.001'],
        ], [
            'bill_date.required' => 'تاريخ أمر التصنيع مطلوب.',
            'branch_id.required' => 'الفرع مطلوب.',
            'manufacturer_id.required' => 'المصنع الخارجي مطلوب.',
            'account_id.required' => 'حساب مخزون تحت التصنيع مطلوب.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->branchAccessService->enforceBranchAccess($request->user('admin-web'), (int) $request->branch_id);

        try {
            $invoice = $this->manufacturingOrderService->create($validator->validated(), $request->user('admin-web'));

            return redirect()
                ->route('manufacturing_orders.show', $invoice->id)
                ->with('success', 'تم إنشاء أمر إرسال للتصنيع بنجاح.');
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }
    }

    public function show(Request $request, int $id)
    {
        $invoice = $this->manufacturingReceiptService->loadOrder($id);

        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $invoice);
        $lineProgress = $this->manufacturingReceiptService->lineProgress($invoice);
        $receiptSummaries = $this->manufacturingReceiptService->receiptSummaries($invoice);
        $returnSummaries = $this->manufacturingReceiptService->returnSummaries($invoice);
        $settlementSummaries = $this->manufacturingReceiptService->settlementSummaries($invoice);
        $receiptSummary = $this->manufacturingReceiptService->summary($invoice);

        $summary = [
            'lines_count' => $invoice->details->count(),
            'total_weight' => round((float) $invoice->details->sum('out_weight'), 3),
            'total_quantity' => round((float) $invoice->details->sum('out_quantity'), 3),
            'total_value' => round((float) $invoice->net_total, 2),
        ];

        return view('admin.manufacturing_orders.show', compact(
            'invoice',
            'summary',
            'lineProgress',
            'receiptSummaries',
            'returnSummaries',
            'receiptSummary',
            'settlementSummaries'
        ));
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function itemsByBranch(Collection $branches): array
    {
        return $branches->mapWithKeys(function (Branch $branch) {
            return [$branch->id => $this->manufacturingOrderService->goldItemsForBranch($branch)->all()];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function prefilledLinesFromOldInput(): array
    {
        $itemIds = old('item_id', []);
        $quantities = old('quantity', []);
        $weights = old('weight', []);

        $lines = collect($itemIds)->map(function ($itemId, $index) use ($quantities, $weights) {
            return [
                'item_id' => $itemId,
                'quantity' => $quantities[$index] ?? 1,
                'weight' => $weights[$index] ?? null,
            ];
        })->filter(fn (array $line) => filled($line['item_id']) || filled($line['weight']))->values()->all();

        return $lines !== [] ? $lines : [['item_id' => null, 'quantity' => 1, 'weight' => null]];
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    private function resolvedCurrentBranchId(Collection $branches, ?int $preferredBranchId): ?int
    {
        if ($preferredBranchId && $branches->pluck('id')->contains($preferredBranchId)) {
            return $preferredBranchId;
        }

        return $branches->first()?->id;
    }

    /**
     * @return array<string, float|string>
     */
    private function orderMetrics(Invoice $invoice): array
    {
        $sentWeight = round((float) $invoice->details->sum('out_weight'), 3);
        $receivedWeight = round((float) $invoice->manufacturingReceipts->sum(function (Invoice $receipt) {
            return (float) $receipt->details->sum('in_weight');
        }), 3);
        $returnedToBranchWeight = round((float) $invoice->manufacturingReturns
            ->where('manufacturing_return_direction', 'from_manufacturer')
            ->sum(function (Invoice $return) {
                return (float) $return->details->sum('in_weight');
            }), 3);
        $returnedToManufacturerWeight = round((float) $invoice->manufacturingReturns
            ->where('manufacturing_return_direction', 'to_manufacturer')
            ->sum(function (Invoice $return) {
                return (float) $return->details->sum('out_weight');
            }), 3);
        $settledWeight = round((float) $invoice->manufacturingLossSettlements->sum(function (Invoice $settlement) {
            return (float) $settlement->manufacturingLossSettlementLines->sum('settled_weight');
        }), 3);
        $remainingWeight = round(max($sentWeight - $receivedWeight - $returnedToBranchWeight + $returnedToManufacturerWeight - $settledWeight, 0), 3);
        $status = 'open';
        $statusLabel = 'مفتوح';

        if ($remainingWeight <= 0.0001) {
            $status = 'completed';
            $statusLabel = 'مكتمل';
        } elseif ($invoice->date < Carbon::today()->format('Y-m-d')) {
            $status = 'late';
            $statusLabel = 'متأخر';
        }

        return [
            'sent_weight' => $sentWeight,
            'received_weight' => $receivedWeight,
            'returned_to_branch_weight' => $returnedToBranchWeight,
            'returned_to_manufacturer_weight' => $returnedToManufacturerWeight,
            'settled_weight' => $settledWeight,
            'remaining_weight' => $remainingWeight,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    private function canCreateReturn(Invoice $invoice): bool
    {
        return $this->manufacturingReceiptService->lineProgress($invoice)->contains(function (array $line) {
            return $line['remaining_weight'] > 0 || $line['available_for_return_weight'] > 0;
        });
    }

    /**
     * @param  Collection<int, Invoice>  $orders
     * @return Collection<int, Invoice>
     */
    private function applyStatusFilter(Collection $orders, string $status): Collection
    {
        if (! in_array($status, ['open', 'completed', 'late'], true)) {
            return $orders->values();
        }

        return $orders->filter(function (Invoice $invoice) use ($status) {
            return $this->orderMetrics($invoice)['status'] === $status;
        })->values();
    }

    /**
     * @param  Collection<int, Invoice>  $orders
     * @return array<string, int>
     */
    private function statusSummary(Collection $orders): array
    {
        $summary = [
            'all' => $orders->count(),
            'open' => 0,
            'completed' => 0,
            'late' => 0,
        ];

        foreach ($orders as $order) {
            $summary[$this->orderMetrics($order)['status']]++;
        }

        return $summary;
    }
}
