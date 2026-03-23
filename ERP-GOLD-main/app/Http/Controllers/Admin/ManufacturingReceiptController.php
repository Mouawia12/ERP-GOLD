<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchAccessService;
use App\Services\Manufacturing\ManufacturingReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManufacturingReceiptController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly ManufacturingReceiptService $manufacturingReceiptService,
    ) {
        $this->middleware('permission:employee.manufacturing_orders.show,admin-web')->only(['show']);
        $this->middleware('permission:employee.manufacturing_orders.add,admin-web')->only(['create', 'store']);
    }

    public function create(Request $request, int $orderId)
    {
        $order = $this->manufacturingReceiptService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $lineProgress = $this->manufacturingReceiptService->lineProgress($order)
            ->filter(fn (array $line) => $line['remaining_weight'] > 0 && $line['remaining_quantity'] > 0)
            ->values();

        if ($lineProgress->isEmpty()) {
            return redirect()
                ->route('manufacturing_orders.show', $order->id)
                ->with('error', 'لا توجد كميات أو أوزان متبقية لاستلامها من هذا الأمر.');
        }

        return view('admin.manufacturing_receipts.create', compact('order', 'lineProgress'));
    }

    public function store(Request $request, int $orderId)
    {
        $order = $this->manufacturingReceiptService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $validator = Validator::make($request->all(), [
            'bill_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'parent_detail_id' => ['required', 'array', 'min:1'],
            'parent_detail_id.*' => ['nullable', 'integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0.001'],
            'weight' => ['required', 'array'],
            'weight.*' => ['nullable', 'numeric', 'min:0.001'],
        ], [
            'bill_date.required' => 'تاريخ الاستلام مطلوب.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $receipt = $this->manufacturingReceiptService->create(
                $order,
                $validator->validated(),
                $request->user('admin-web')
            );

            return redirect()
                ->route('manufacturing_receipts.show', $receipt->id)
                ->with('success', 'تم حفظ استلام التصنيع بنجاح.');
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }
    }

    public function show(Request $request, int $id)
    {
        $receipt = $this->manufacturingReceiptService->loadInvoice($id);

        if ($receipt->type !== 'manufacturing_receipt') {
            return redirect()->route('manufacturing_orders.index')->with('error', __('main.not_found'));
        }

        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $receipt);

        $summary = [
            'lines_count' => $receipt->details->count(),
            'total_quantity' => round((float) $receipt->details->sum('in_quantity'), 3),
            'total_weight' => round((float) $receipt->details->sum('in_weight'), 3),
            'total_value' => round((float) $receipt->net_total, 2),
        ];

        return view('admin.manufacturing_receipts.show', compact('receipt', 'summary'));
    }
}
