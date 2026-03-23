<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Branches\BranchAccessService;
use App\Services\Manufacturing\ManufacturingLossSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManufacturingLossSettlementController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly ManufacturingLossSettlementService $manufacturingLossSettlementService,
    ) {
        $this->middleware('permission:employee.manufacturing_orders.show,admin-web')->only(['show']);
        $this->middleware('permission:employee.manufacturing_orders.add,admin-web')->only(['create', 'store']);
    }

    public function create(Request $request, int $orderId)
    {
        $order = $this->manufacturingLossSettlementService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $lineProgress = app(\App\Services\Manufacturing\ManufacturingReceiptService::class)
            ->lineProgress($order)
            ->filter(fn (array $line) => $line['remaining_weight'] > 0)
            ->values();

        if ($lineProgress->isEmpty()) {
            return redirect()
                ->route('manufacturing_orders.show', $order->id)
                ->with('error', 'لا يوجد وزن متبقٍ يحتاج إلى تسوية في هذا الأمر.');
        }

        $accounts = Account::query()->whereDoesntHave('childrens')->orderBy('name')->get();

        return view('admin.manufacturing_loss_settlements.create', compact('order', 'lineProgress', 'accounts'));
    }

    public function store(Request $request, int $orderId)
    {
        $order = $this->manufacturingLossSettlementService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $validator = Validator::make($request->all(), [
            'bill_date' => ['required', 'date'],
            'account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string'],
            'parent_detail_id' => ['required', 'array', 'min:1'],
            'parent_detail_id.*' => ['nullable', 'integer'],
            'settlement_type' => ['required', 'array'],
            'settlement_type.*' => ['nullable', 'string'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['required', 'array'],
            'weight.*' => ['nullable', 'numeric', 'min:0.001'],
            'line_notes' => ['nullable', 'array'],
        ], [
            'bill_date.required' => 'تاريخ التسوية مطلوب.',
            'account_id.required' => 'حساب خسائر الفاقد مطلوب.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $invoice = $this->manufacturingLossSettlementService->create(
                $order,
                $validator->validated(),
                $request->user('admin-web')
            );

            return redirect()
                ->route('manufacturing_loss_settlements.show', $invoice->id)
                ->with('success', 'تم حفظ تسوية الفاقد/الهالك بنجاح.');
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }
    }

    public function show(Request $request, int $id)
    {
        $invoice = $this->manufacturingLossSettlementService->loadSettlement($id);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $invoice);

        $summary = [
            'lines_count' => $invoice->manufacturingLossSettlementLines->count(),
            'total_quantity' => round((float) $invoice->manufacturingLossSettlementLines->sum('settled_quantity'), 3),
            'total_weight' => round((float) $invoice->manufacturingLossSettlementLines->sum('settled_weight'), 3),
            'total_value' => round((float) $invoice->net_total, 2),
        ];

        return view('admin.manufacturing_loss_settlements.show', compact('invoice', 'summary'));
    }
}
