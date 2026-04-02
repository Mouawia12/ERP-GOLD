<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Invoice;
use App\Models\ItemUnit;
use App\Models\Tax;
use App\Services\Branches\BranchAccessService;
use App\Services\Invoices\InvoicePartySnapshotService;
use App\Services\Invoices\InvoiceTermsService;
use App\Services\JournalEntriesService;
use App\Services\Payments\InvoicePaymentService;
use App\Services\Shifts\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use DataTables;

class PurchasesController extends Controller
{
    private const NON_GOLD_CARAT_TYPE = 'non_gold';

    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly InvoiceTermsService $invoiceTermsService,
        private readonly InvoicePartySnapshotService $invoicePartySnapshotService,
        private readonly InvoicePaymentService $invoicePaymentService,
        private readonly ShiftService $shiftService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Invoice::query()->where('type', 'purchase');
        $this->branchAccessService->scopeToAccessibleBranch($query, $request->user('admin-web'));
        $data = $query->orderBy('id', 'DESC')->get();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '';

                    if (auth()->user()->canany(['employee.purchase_invoices.show'])) {
                        $btn .= '<a href=' . route('purchases.show', $row->id) . ' class="btn btn-primary" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button" target="_blank" >
                                    <i class="fa fa-eye"></i>معاينة</a>';
                    }

                    if (
                        auth()->user()->canany(['employee.purchase_invoices.add'])
                        && ($row->purchase_type ?? 'normal') === 'normal'
                    ) {
                        $btn .= '<a style="margin:0 5px;" href=' . route('purchase_return.create', $row->id) . ' class="btn btn-info" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button">
                                    <i class="fa fa-retweet"></i> عمل مردود</a>';
                    }

                    return $btn ?? '';
                })
                ->addColumn('bill_number', function ($row) {
                    return $row->bill_number;
                })
                ->addColumn('purchase_carat', function ($row) {
                    return $row->purchaseCaratType->title ?? '';
                })
                ->addColumn('customer', function ($row) {
                    return $row->customer->name;
                })
                ->addColumn('net_money', function ($row) {
                    return round($row->net_total, 2);
                })
                ->addColumn('total_money', function ($row) {
                    return round($row->lines_total, 2);
                })
                ->addColumn('tax', function ($row) {
                    return round($row->taxes_total, 2);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.purchases.index', compact('data'));
    }

    public function create()
    {
        $currentUser = Auth::guard('admin-web')->user();
        $customers = Customer::query()
            ->visibleToUser($currentUser)
            ->where('type', '=', 'supplier')
            ->get();
        $branches = $this->branchAccessService->visibleBranches($currentUser);
        $caratTypes = GoldCaratType::all();

        return view('admin.purchases.create', [
            'customers' => $customers,
            'branches' => $branches,
            'caratTypes' => $caratTypes,
            'defaultInvoiceTerms' => $this->invoiceTermsService->defaultTerms(InvoiceTermsService::CONTEXT_PURCHASES),
        ]);
    }

    public function purchase_payment_show(Request $request)
    {
        $money = $request->net_after_discount;
        $type = $request->document_type;
        $branchId = (int) $request->branch_id;
        $this->branchAccessService->enforceBranchAccess($request->user('admin-web'), $branchId);
        $bankAccounts = BankAccount::query()
            ->active()
            ->where('branch_id', $branchId)
            ->orderByDesc('is_default')
            ->orderBy('account_name')
            ->get();

        return view('admin.purchases.payment', compact('money', 'type', 'bankAccounts', 'branchId'))->render();
    }

    public function purchase_return_index(Request $request)
    {
        $query = Invoice::query()->where('type', 'purchase_return');
        $this->branchAccessService->scopeToAccessibleBranch($query, $request->user('admin-web'));
        $data = $query->orderByDesc('id')->get();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    if (auth()->user()->canany(['employee.purchase_invoices.show'])) {
                        return '<a href=' . route('purchase_return.show', $row->id) . ' class="btn btn-primary" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button" target="_blank" >
                                    <i class="fa fa-eye"></i>معاينة</a>';
                    }

                    return '';
                })
                ->addColumn('bill_number', function ($row) {
                    return $row->bill_number;
                })
                ->addColumn('parent_invoice', function ($row) {
                    return $row->parent?->bill_number ?? '-';
                })
                ->addColumn('customer', function ($row) {
                    return $row->customer->name ?? ($row->bill_client_name ?? '-');
                })
                ->addColumn('net_money', function ($row) {
                    return round($row->net_total, 2);
                })
                ->addColumn('total_money', function ($row) {
                    return round($row->lines_total, 2);
                })
                ->addColumn('tax', function ($row) {
                    return round($row->taxes_total, 2);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.purchase_return.index', compact('data'));
    }

    public function purchase_return_create($id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);

        if ($invoice->type !== 'purchase') {
            return redirect()->route('purchases.index')->with('error', __('main.not_found'));
        }

        if (($invoice->purchase_type ?? 'normal') !== 'normal') {
            return redirect()->route('purchases.index')->with('error', 'مردود المشتريات متاح حاليًا لفواتير الشراء العادي فقط.');
        }

        $bankAccounts = BankAccount::query()
            ->active()
            ->where('branch_id', $invoice->branch_id)
            ->orderByDesc('is_default')
            ->orderBy('account_name')
            ->get();

        return view('admin.purchase_return.create', compact('invoice', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'bill_date' => 'required',
                'branch_id' => 'required',
                'carat_type' => ['required', Rule::in(array_merge(
                    GoldCaratType::query()->pluck('key')->all(),
                    [self::NON_GOLD_CARAT_TYPE]
                ))],
                'purchase_type' => 'required|in:' . implode(',', config('settings.purchase_types')),
                'supplier_id' => [
                    'required',
                    'integer',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                        $exists = Customer::query()
                            ->visibleToUser($request->user('admin-web'))
                            ->where('type', 'supplier')
                            ->whereKey($value)
                            ->exists();

                        if (! $exists) {
                            $fail(__('validations.supplier_id_exists'));
                        }
                    },
                ],
                'weight' => 'required|array',
                'bill_client_name' => 'nullable|string|max:255',
                'bill_client_phone' => 'nullable|string|max:50',
                'bill_client_identity_number' => 'nullable|string|max:100',
            ], [
                'bill_date.required' => __('validations.bill_date_required'),
                'branch_id.required' => __('validations.branch_id_required'),
                'carat_type.required' => __('validations.carat_type_required'),
                'carat_type.exists' => __('validations.carat_type_exists'),
                'purchase_type.in' => __('validations.purchase_type_in'),
                'supplier_id.required' => __('validations.supplier_id_required'),
                'supplier_id.exists' => __('validations.supplier_id_exists'),
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()->all()
                ], 422);
            }
            $this->branchAccessService->enforceBranchAccess(Auth::user(), (int) $request->branch_id);
            $purchaseType = $request->purchase_type;
            $financialYear = FinancialYear::where('is_active', 1)->first();
            $supplier = Customer::query()
                ->visibleToUser($request->user('admin-web'))
                ->where('type', 'supplier')
                ->findOrFail($request->supplier_id);
            $isNonGoldFlow = $request->carat_type === self::NON_GOLD_CARAT_TYPE;

            if ($isNonGoldFlow && $purchaseType !== 'normal') {
                return response()->json([
                    'status' => false,
                    'errors' => ['الأصناف غير الذهبية تدعم الشراء العادي فقط في هذه المرحلة.'],
                ], 422);
            }

            if (! $isNonGoldFlow && $purchaseType != 'normal') {
                $goldCaratType = ($purchaseType == 'discount_from_scrap') ? GoldCaratType::where('key', 'scrap')->first() : GoldCaratType::where('key', 'pure')->first();
                $totalWeight = collect($request->weight)
                    ->map(function ($weight, $index) use ($request) {
                        $lineCaratId = $request->carats_id[$index];
                        $lineCarat = GoldCarat::find($lineCaratId);

                        $fromFactor = $lineCarat->transform_factor;
                        $toFactor = GoldCarat::where('label', 'C21')->first()->transform_factor ?? 1;
                        return $this->convertCarat($weight, $fromFactor, $toFactor);
                    })
                    ->sum();

                if ($totalWeight > $goldCaratType->getStock()) {
                    return response()->json([
                        'status' => false,
                        'errors' => [__('validations.purchase_weight_exceeds_stock', ['stock_type' => $goldCaratType->title])]
                    ], 422);
                }
            }

            $branch = Branch::findOrFail($request->branch_id);
            $accountSetting = $branch->accountSetting;
            $caratType = $request->carat_type;
            $lines = array();
            $stockLines = array();
            if (count($request->unit_id)) {
                $activeShift = $this->shiftService->requireActiveShift(Auth::user(), (int) $request->branch_id);
                // store header
                $branch = Branch::find($request->branch_id);
                $warehouse = $branch->warehouses->first();

                $totalCost = 0;
                $laborTotal = 0;
                $linesTotal = 0;
                $linesDiscount = 0;
                $linesTotalAfterDiscount = 0;
                $linesTax = 0;
                $linesNetTotal = 0;
                $discountFromScraptotal = 0;
                $discountFromPuretotal = 0;

                foreach ($request->unit_id as $key => $unit_id) {
                    $unit = ItemUnit::find($request->unit_id[$key]);

                    $lineTotalWeight = round((float) ($request->weight[$key] ?? 0), 3);
                    if ($lineTotalWeight <= 0) {
                        return response()->json([
                            'status' => false,
                            'errors' => ['وزن الصنف يجب أن يكون أكبر من صفر لكل سطر في الفاتورة.'],
                        ], 422);
                    }

                    $lineTotalLaborCost = round((float) ($request->item_total_labor_cost[$key] ?? 0), 2);
                    $laborTotal += $lineTotalLaborCost;
                    $unitLaborCost = $lineTotalLaborCost / $lineTotalWeight;

                    if ($purchaseType == 'normal') {
                        $lineTotalCost = round((float) ($request->item_total_cost[$key] ?? 0), 2);
                        $totalCost += $lineTotalCost;

                        $lineTotal = $lineTotalCost + $lineTotalLaborCost;

                        $unitCost = $lineTotalCost / $lineTotalWeight;

                        $gramPrice = $lineTotal / $lineTotalWeight;
                    } else {
                        if ($purchaseType == 'discount_from_scrap') {
                            $goldCaratType = GoldCaratType::where('key', 'scrap')->first();
                            $scapAccount = Account::find($accountSetting->stock_account_scrap);
                            $stockBalance = (float) $goldCaratType->getStock();
                            if ($stockBalance <= 0) {
                                return response()->json([
                                    'status' => false,
                                    'errors' => ['لا يمكن تنفيذ خصم من خامة السكراب لأن المخزون الحالي يساوي صفر.'],
                                ], 422);
                            }
                            $unitCost = $scapAccount->closingBalance($financialYear->from, $financialYear->to) / $stockBalance;
                            $discountFromScraptotal += $unitCost * $lineTotalWeight;
                        } else {
                            $goldCaratType = GoldCaratType::where('key', 'pure')->first();
                            $pureAccount = Account::find($accountSetting->stock_account_pure);
                            $stockBalance = (float) $goldCaratType->getStock();
                            if ($stockBalance <= 0) {
                                return response()->json([
                                    'status' => false,
                                    'errors' => ['لا يمكن تنفيذ خصم من خامة الذهب الصافي لأن المخزون الحالي يساوي صفر.'],
                                ], 422);
                            }
                            $unitCost = $pureAccount->closingBalance($financialYear->from, $financialYear->to) / $stockBalance;
                            $discountFromPuretotal += $unitCost * $lineTotalWeight;
                        }
                        $lineTotalCost = $unitCost * $lineTotalWeight;
                        $totalCost += $lineTotalCost;
                        $lineTotal = $lineTotalLaborCost;
                        $gramPrice = ($lineTotalCost + $lineTotalLaborCost) / $lineTotalWeight;
                    }

                    $item = $unit->item;
                    $taxData = $this->resolvePurchaseTaxData($item, $caratType);
                    $taxRate = $taxData['rate'];
                    $unitTaxAmount = ($lineTotal * $taxRate / 100) / $lineTotalWeight;

                    $linesTotal += $lineTotal;

                    $lineDiscount = $request->discount[$key] ?? 0;
                    $linesDiscount += $lineDiscount;

                    $lineTotalAfterDiscount = $lineTotal - $lineDiscount;
                    $linesTotalAfterDiscount += $lineTotalAfterDiscount;

                    $lineTax = $unitTaxAmount * $lineTotalWeight;
                    $linesTax += $lineTax;

                    $lineNetTotal = $lineTotalAfterDiscount + $lineTax;

                    $linesNetTotal += $lineNetTotal;

                    $line = [
                        'warehouse_id' => $warehouse->id ?? null,
                        'item_id' => $item->id,
                        'unit_id' => $unit->id,
                        'gold_carat_id' => $item->gold_carat_id,
                        'gold_carat_type_id' => $item->gold_carat_type_id,
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'in_quantity' => 0,
                        'out_quantity' => 1,
                        'in_weight' => $lineTotalWeight,
                        'out_weight' => 0,
                        'unit_cost' => $unitCost,
                        'labor_cost_per_gram' => $unitLaborCost,
                        'unit_price' => $gramPrice,
                        'unit_discount' => 0,
                        'unit_tax' => $unitTaxAmount,
                        'unit_tax_rate' => $taxData['rate'],
                        'unit_tax_id' => $taxData['id'],
                        'line_total' => $lineTotal,
                        'line_discount' => $lineDiscount ?? 0,
                        'line_tax' => $lineTax,
                        'net_total' => $lineNetTotal,
                    ];
                    if (! $isNonGoldFlow && $purchaseType != 'normal') {
                        $goldCarat = ($purchaseType == 'discount_from_scrap') ? GoldCarat::where('transform_factor', 1)->first() : GoldCarat::where('is_pure', true)->first();
                        $goldCaratType = ($purchaseType == 'discount_from_scrap') ? GoldCaratType::where('key', 'scrap')->first() : GoldCaratType::where('key', 'pure')->first();
                        $outWeight = $this->convertCarat($lineTotalWeight, $item->goldCarat->transform_factor, $goldCarat->transform_factor);
                        $stockLine = [
                            'warehouse_id' => $warehouse->id ?? null,
                            'item_id' => $item->id,
                            'unit_id' => $unit->id,
                            'gold_carat_id' => $goldCarat->id,
                            'gold_carat_type_id' => $goldCaratType->id,
                            'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                            'in_quantity' => 0,
                            'out_quantity' => 1,
                            'in_weight' => 0,
                            'out_weight' => $outWeight,
                            'unit_cost' => $unitCost,
                            'labor_cost_per_gram' => $unitLaborCost,
                            'unit_price' => $gramPrice,
                            'unit_discount' => 0,
                            'unit_tax' => $unitTaxAmount,
                            'unit_tax_rate' => $taxData['rate'],
                            'unit_tax_id' => $taxData['id'],
                            'line_total' => $lineTotal,
                            'line_discount' => $lineDiscount ?? 0,
                            'line_tax' => $lineTax,
                            'net_total' => $lineNetTotal,
                        ];
                    }

                    $actualBalance = $item->actual_balance;
                    if ($actualBalance < 0) {
                        $actualBalance = 0;
                    }
                    $newBalance = $actualBalance + $lineTotalWeight;
                    if ($newBalance <= 0) {
                        return response()->json([
                            'status' => false,
                            'errors' => ['تعذر تحديث متوسط التكلفة لأن الرصيد الناتج للصنف غير صالح.'],
                        ], 422);
                    }
                    $averageCost = (($item->defaultUnit->average_cost_per_gram * $actualBalance) + ($unitCost * $lineTotalWeight)) / $newBalance;

                    $item->defaultUnit()->update(['initial_cost_per_gram' => $unitCost, 'average_cost_per_gram' => $averageCost, 'current_cost_per_gram' => $unitCost]);

                    $lines[] = $line;
                    if (! $isNonGoldFlow && $purchaseType != 'normal') {
                        $stockLines[] = $stockLine;
                    }
                }

                $paymentPayload = $request->all();
                if (! array_key_exists('cash', $paymentPayload) && empty($paymentPayload['payment_lines'] ?? [])) {
                    $paymentPayload['cash'] = $linesNetTotal;
                }

                $paymentLines = $this->invoicePaymentService->normalizeSalesLines(
                    $paymentPayload,
                    (int) $branch->id,
                    $linesNetTotal
                );
                $paymentType = $this->invoicePaymentService->resolveStoredPaymentType($paymentLines);

                $invoice = Invoice::create([
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->supplier_id,
                    'supplier_bill_number' => $request->supplier_bill_number ?? null,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'purchase',
                    'payment_type' => $paymentType,
                    'purchase_type' => $request->purchase_type ?? 'normal',
                    'purchase_carat_type_id' => $isNonGoldFlow ? null : GoldCaratType::where('key', $request->carat_type)->first()->id,
                    'notes' => $request->notes ?? '',
                    'invoice_terms' => $this->invoiceTermsService->resolveSnapshot(
                        $request->input('invoice_terms'),
                        InvoiceTermsService::CONTEXT_PURCHASES,
                        array_key_exists('invoice_terms', $request->all())
                    ),
                    'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                    'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                    'lines_total' => $linesTotal,
                    'discount_total' => $linesDiscount,
                    'lines_total_after_discount' => $linesTotalAfterDiscount,
                    'taxes_total' => $linesTax,
                    'net_total' => $linesNetTotal,
                    'user_id' => Auth::user()->id,
                    'shift_id' => $activeShift->id,
                ] + $this->invoicePartySnapshotService->resolve(
                    $supplier,
                    $request->input('bill_client_name'),
                    $request->input('bill_client_phone'),
                    $request->input('bill_client_identity_number'),
                ));
                $this->invoicePaymentService->persist($invoice, $paymentLines);

                if (! $isNonGoldFlow && $purchaseType != 'normal' && count($stockLines) > 0) {
                    $stockMovementInvoice = Invoice::create([
                        'branch_id' => $request->branch_id,
                        'warehouse_id' => $warehouse->id ?? null,
                        'customer_id' => $request->supplier_id,
                        'supplier_bill_number' => $request->supplier_bill_number ?? null,
                        'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                        'type' => 'stock_movement',
                        'purchase_type' => $request->purchase_type ?? 'normal',
                        'purchase_carat_type_id' => GoldCaratType::where('key', $request->carat_type)->first()->id,
                        'notes' => $request->notes ?? '',
                        'invoice_terms' => $this->invoiceTermsService->resolveSnapshot(
                            $request->input('invoice_terms'),
                            InvoiceTermsService::CONTEXT_PURCHASES,
                            array_key_exists('invoice_terms', $request->all())
                        ),
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                        'lines_total' => $linesTotal,
                        'discount_total' => $linesDiscount,
                        'lines_total_after_discount' => $linesTotalAfterDiscount,
                        'taxes_total' => $linesTax,
                        'net_total' => $linesNetTotal,
                        'user_id' => Auth::user()->id,
                        'shift_id' => $activeShift->id,
                    ] + $this->invoicePartySnapshotService->resolve(
                        $supplier,
                        $request->input('bill_client_name'),
                        $request->input('bill_client_phone'),
                        $request->input('bill_client_identity_number'),
                    ));
                    $stockMovementInvoice->details()->createMany($stockLines);
                }

                JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->purchase_prepare_journal_entry_details($invoice, $laborTotal, $totalCost, $discountFromScraptotal, $discountFromPuretotal));
                $invoice->details()->createMany($lines);
                DB::commit();
                return response()->json([
                    'status' => true,
                    'message' => __('main.saved')
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'errors' => [__('main.nodetails')],
                ], 422);
            }
        } catch (ValidationException $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => collect($ex->errors())->flatten()->values()->all(),
            ], 422);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    public function purchase_return_store(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->branchAccessService->enforceInvoiceAccess(Auth::user(), $invoice);

        if ($invoice->type !== 'purchase') {
            return redirect()->route('purchases.index')->with('error', __('main.not_found'));
        }

        if (($invoice->purchase_type ?? 'normal') !== 'normal') {
            return redirect()->route('purchases.index')->with('error', 'مردود المشتريات متاح حاليًا لفواتير الشراء العادي فقط.');
        }

        try {
            DB::beginTransaction();
            $activeShift = $this->shiftService->requireActiveShift(Auth::user(), (int) $invoice->branch_id);
            $selectedDetailIds = collect($request->input('checkDetail', []))
                ->filter()
                ->map(fn ($detailId) => (int) $detailId)
                ->unique()
                ->values()
                ->all();

            if (count($selectedDetailIds) === 0) {
                throw ValidationException::withMessages([
                    'checkDetail' => ['يجب اختيار صنف واحد على الأقل قبل حفظ مردود المشتريات.'],
                ]);
            }

            $returnedDetails = $invoice->details()
                ->whereIn('id', $selectedDetailIds)
                ->whereNotIn('id', $invoice->returnInvoicesDetailsIds)
                ->get();

            if ($returnedDetails->isEmpty()) {
                throw ValidationException::withMessages([
                    'checkDetail' => ['العناصر المحددة غير صالحة أو تم إرجاعها سابقًا.'],
                ]);
            }

            $linesTotal = 0;
            $linesDiscount = 0;
            $linesTotalAfterDiscount = 0;
            $linesTax = 0;
            $linesNetTotal = 0;
            $totalCost = 0;
            $laborTotal = 0;
            $lines = [];

            foreach ($returnedDetails as $detail) {
                $weight = (float) ($detail->in_weight ?: $detail->out_weight);
                $lineTotal = (float) $detail->line_total;
                $lineDiscount = (float) $detail->line_discount;
                $lineTax = (float) $detail->line_tax;
                $lineNetTotal = (float) $detail->net_total;

                $linesTotal += $lineTotal;
                $linesDiscount += $lineDiscount;
                $linesTotalAfterDiscount += ($lineTotal - $lineDiscount);
                $linesTax += $lineTax;
                $linesNetTotal += $lineNetTotal;
                $totalCost += (float) $detail->unit_cost * $weight;
                $laborTotal += (float) ($detail->labor_cost_per_gram ?? 0) * $weight;

                $lines[] = [
                    'parent_id' => $detail->id,
                    'warehouse_id' => $invoice->warehouse_id ?? null,
                    'item_id' => $detail->item_id,
                    'unit_id' => $detail->unit_id,
                    'gold_carat_id' => $detail->gold_carat_id,
                    'gold_carat_type_id' => $detail->gold_carat_type_id,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'in_quantity' => 0,
                    'out_quantity' => $detail->out_quantity ?: 1,
                    'in_weight' => 0,
                    'out_weight' => $weight,
                    'unit_cost' => $detail->unit_cost,
                    'labor_cost_per_gram' => $detail->labor_cost_per_gram,
                    'unit_price' => $detail->unit_price,
                    'unit_discount' => $detail->unit_discount,
                    'unit_tax' => $detail->unit_tax,
                    'unit_tax_rate' => $detail->unit_tax_rate,
                    'unit_tax_id' => $detail->unit_tax_id,
                    'line_total' => $lineTotal,
                    'line_discount' => $lineDiscount,
                    'line_tax' => $lineTax,
                    'net_total' => $lineNetTotal,
                ];
            }

            $paymentPayload = $request->all();
            $paymentPayload['cash'] = $request->filled('cash')
                ? $request->input('cash')
                : $linesNetTotal;

            $paymentLines = $this->invoicePaymentService->normalizeSalesLines(
                $paymentPayload,
                (int) $invoice->branch_id,
                (float) $linesNetTotal,
            );

            $returnInvoice = $invoice->returnInvoices()->create([
                'branch_id' => $invoice->branch_id,
                'warehouse_id' => $invoice->warehouse_id ?? null,
                'customer_id' => $invoice->customer_id,
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'type' => 'purchase_return',
                'payment_type' => $this->invoicePaymentService->resolveStoredPaymentType($paymentLines),
                'purchase_type' => $invoice->purchase_type ?? 'normal',
                'purchase_carat_type_id' => $invoice->purchase_carat_type_id,
                'notes' => $request->notes ?? '',
                'invoice_terms' => $invoice->invoice_terms,
                'date' => Carbon::now()->format('Y-m-d'),
                'time' => Carbon::now()->format('H:i:s'),
                'lines_total' => $linesTotal,
                'discount_total' => $linesDiscount,
                'lines_total_after_discount' => $linesTotalAfterDiscount,
                'taxes_total' => $linesTax,
                'net_total' => $linesNetTotal,
                'user_id' => Auth::user()->id,
                'shift_id' => $activeShift->id,
            ] + $this->invoicePartySnapshotService->fromInvoice($invoice));

            $returnInvoice->details()->createMany($lines);
            $this->invoicePaymentService->persist($returnInvoice, $paymentLines);
            JournalEntriesService::invoiceGenerateJournalEntries(
                $returnInvoice,
                $this->purchase_return_prepare_journal_entry_details($returnInvoice, $laborTotal, $totalCost),
            );

            DB::commit();

            return redirect()->route('purchase_return.index')->with('success', __('main.created'));
        } catch (ValidationException $ex) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors($ex->errors())
                ->withInput();
        } catch (\Throwable $ex) {
            DB::rollBack();

            return redirect()
                ->route('purchase_return.create', ['id' => $id])
                ->with('error', $ex->getMessage());
        }
    }

    function convertCarat($weight, $fromFactor, $toFactor)
    {
        return round($weight * ($fromFactor / $toFactor), 3);
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);
        if (!in_array($invoice->type, ['purchase', 'purchase_return'])) {
            return redirect()->route('purchases.index')->with('error', __('main.not_found'));
        }
        return view('admin.purchases_and_purchases_return.print', compact('invoice'));
    }

    public function purchase_return_show($id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);

        if ($invoice->type !== 'purchase_return') {
            return redirect()->route('purchase_return.index')->with('error', __('main.not_found'));
        }

        return view('admin.purchases_and_purchases_return.print', compact('invoice'));
    }

    public function purchase_prepare_journal_entry_details($invoice, $laborTotal, $totalCost, $discountFromScraptotal = 0, $discountFromPuretotal = 0)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        // supplier account
        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => 0,
            'credit' => $invoice->net_total,
            'document_date' => $documentDate,
        ];

        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => $invoice->net_total,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        $lines = array_merge(
            $lines,
            $this->invoicePaymentService->journalCreditLines(
                $invoice,
                (int) $accountSetting->safe_account,
                $accountSetting->bank_account ? (int) $accountSetting->bank_account : null,
            ),
        );

        if ($laborTotal > 0) {
            // labor cost account
            $lines[] = [
                'account_id' => $accountSetting->made_account,
                'debit' => $laborTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        // purchase tax account
        if ($invoice->taxes_total > 0) {
            $lines[] = [
                'account_id' => $accountSetting->purchase_tax_account,
                'debit' => $invoice->taxes_total,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($totalCost > 0) {
            // stock account
            $stockAccount = 'stock_account_' . ($invoice->purchaseCaratType?->key ?? 'crafted');
            $lines[] = [
                'account_id' => $accountSetting->{$stockAccount},
                'debit' => $totalCost,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($discountFromScraptotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_scrap,
                'debit' => 0,
                'credit' => $discountFromScraptotal,
                'document_date' => $documentDate,
            ];
        }

        if ($discountFromPuretotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_pure,
                'debit' => 0,
                'credit' => $discountFromPuretotal,
                'document_date' => $documentDate,
            ];
        }

        return $lines;
    }

    public function purchase_return_prepare_journal_entry_details($invoice, $laborTotal, $totalCost)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        $lines = array_merge(
            $lines,
            $this->invoicePaymentService->journalDebitLines(
                $invoice,
                (int) $accountSetting->safe_account,
                $accountSetting->bank_account ? (int) $accountSetting->bank_account : null,
            ),
        );

        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => $invoice->net_total,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => 0,
            'credit' => $invoice->net_total,
            'document_date' => $documentDate,
        ];

        if ($laborTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->made_account,
                'debit' => 0,
                'credit' => $laborTotal,
                'document_date' => $documentDate,
            ];
        }

        if ($invoice->taxes_total > 0) {
            $lines[] = [
                'account_id' => $accountSetting->purchase_tax_account,
                'debit' => 0,
                'credit' => $invoice->taxes_total,
                'document_date' => $documentDate,
            ];
        }

        if ($totalCost > 0) {
            $stockAccount = 'stock_account_' . ($invoice->purchaseCaratType?->key ?? 'crafted');
            $lines[] = [
                'account_id' => $accountSetting->{$stockAccount},
                'debit' => 0,
                'credit' => $totalCost,
                'document_date' => $documentDate,
            ];
        }

        return $lines;
    }

    private function resolvePurchaseTaxData($item, string $caratType): array
    {
        if ($caratType === 'crafted' && $item->goldCarat?->tax) {
            return [
                'id' => $item->goldCarat->tax->id,
                'rate' => (float) $item->goldCarat->tax->rate,
            ];
        }

        $fallbackTax = Tax::query()->where('zatca_code', 'O')->first();

        return [
            'id' => $fallbackTax?->id,
            'rate' => (float) ($fallbackTax?->rate ?? 0),
        ];
    }
}
