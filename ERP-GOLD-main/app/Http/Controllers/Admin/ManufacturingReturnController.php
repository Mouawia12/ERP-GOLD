<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchAccessService;
use App\Services\Manufacturing\ManufacturingReceiptService;
use App\Services\Manufacturing\ManufacturingReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManufacturingReturnController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly ManufacturingReturnService $manufacturingReturnService,
        private readonly ManufacturingReceiptService $manufacturingReceiptService,
    ) {
        $this->middleware('permission:employee.manufacturing_orders.show,admin-web')->only(['show']);
        $this->middleware('permission:employee.manufacturing_orders.add,admin-web')->only(['create', 'store']);
    }

    public function create(Request $request, int $orderId)
    {
        $order = $this->manufacturingReturnService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $lineProgress = $this->manufacturingReceiptService->lineProgress($order)
            ->filter(fn (array $line) => $line['remaining_weight'] > 0 || $line['available_for_return_weight'] > 0)
            ->values();

        if ($lineProgress->isEmpty()) {
            return redirect()
                ->route('manufacturing_orders.show', $order->id)
                ->with('error', 'لا توجد أوزان قابلة للإرجاع من أو إلى التصنيع في هذا الأمر.');
        }

        $defaultDirection = old(
            'return_direction',
            $lineProgress->sum('remaining_weight') > 0 ? 'from_manufacturer' : 'to_manufacturer'
        );

        return view('admin.manufacturing_returns.create', compact('order', 'lineProgress', 'defaultDirection'));
    }

    public function store(Request $request, int $orderId)
    {
        $order = $this->manufacturingReturnService->loadOrder($orderId);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $order);

        $validator = Validator::make($request->all(), [
            'bill_date' => ['required', 'date'],
            'return_direction' => ['required', 'in:from_manufacturer,to_manufacturer'],
            'notes' => ['nullable', 'string'],
            'parent_detail_id' => ['required', 'array', 'min:1'],
            'parent_detail_id.*' => ['nullable', 'integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0.001'],
            'weight' => ['required', 'array'],
            'weight.*' => ['nullable', 'numeric', 'min:0.001'],
        ], [
            'bill_date.required' => 'تاريخ الإرجاع مطلوب.',
            'return_direction.required' => 'اتجاه الإرجاع مطلوب.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $invoice = $this->manufacturingReturnService->create(
                $order,
                $validator->validated(),
                $request->user('admin-web')
            );

            return redirect()
                ->route('manufacturing_returns.show', $invoice->id)
                ->with('success', 'تم حفظ مستند الإرجاع بنجاح.');
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }
    }

    public function show(Request $request, int $id)
    {
        $invoice = $this->manufacturingReturnService->loadReturn($id);
        $this->branchAccessService->enforceInvoiceAccess($request->user('admin-web'), $invoice);

        $isFromManufacturer = $invoice->manufacturing_return_direction === 'from_manufacturer';
        $summary = [
            'lines_count' => $invoice->details->count(),
            'total_quantity' => round((float) $invoice->details->sum($isFromManufacturer ? 'in_quantity' : 'out_quantity'), 3),
            'total_weight' => round((float) $invoice->details->sum($isFromManufacturer ? 'in_weight' : 'out_weight'), 3),
            'total_value' => round((float) $invoice->net_total, 2),
        ];

        return view('admin.manufacturing_returns.show', compact('invoice', 'summary', 'isFromManufacturer'));
    }
}
