<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\GoldCaratType;
use App\Models\Invoice;
use App\Models\ItemUnit;
use App\Models\Tax;
use App\Services\Branches\BranchAccessService;
use App\Services\Invoices\InvoiceTermsService;
use App\Services\Invoices\InvoicePartySnapshotService;
use App\Services\Payments\InvoicePaymentService;
use App\Services\Shifts\ShiftService;
use App\Services\Zatca\SendZatcaInvoice;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use DataTables;

class SalesController extends Controller
{
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
        $currentUser = $request->user('admin-web');
        $type = $request->type;

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = Invoice::query()
            ->where('type', 'sale')
            ->where('sale_type', $type);

        $this->branchAccessService->scopeToAccessibleBranch($query, $currentUser);

        $query
            ->when(filled($validated['branch_id'] ?? null), fn ($builder) => $builder->where('branch_id', (int) $validated['branch_id']))
            ->when(filled($validated['date_from'] ?? null), fn ($builder) => $builder->whereDate('date', '>=', $validated['date_from']))
            ->when(filled($validated['date_to'] ?? null), fn ($builder) => $builder->whereDate('date', '<=', $validated['date_to']));

        $data = $query->orderBy('id', 'DESC')->get();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($type) {
                    if (auth()->user()->canany(['employee.simplified_tax_invoices.show', 'employee.tax_invoices.show'])) {
                        $btn = '<a href=' . route('sales.show', $row->id) . ' class="btn btn-primary" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button" target="_blank" >
                                    <i class="fa fa-eye"></i>معاينة</a>';
                    }
                    if ($row->returnInvoices()->sum('net_total') < $row->net_total) {
                        if (auth()->user()->canany(['employee.sales_returns.add', 'employee.sales_returns.show'])) {
                            $btn = $btn . '<a style="margin:0 5px;" href=' . route('sales_return.create', ['type' => $type, 'id' => $row->id]) . ' class="btn btn-info" 
                                   value="' . $row->id . '" role="button"  data-bs-toggle="button" ><i class="fa fa-retweet"></i> عمل مرتجع</a>';
                        }
                    }
                    return $btn;
                })
                ->addColumn('bill_number', function ($row) use ($type) {
                    return $row->bill_number;
                })
                ->addColumn('customer', function ($row) use ($type) {
                    return $row->customer->name;
                })
                ->addColumn('net_money', function ($row) use ($type) {
                    return round($row->net_total, 2);
                })
                ->addColumn('total_money', function ($row) use ($type) {
                    return round($row->lines_total, 2);
                })
                ->addColumn('tax', function ($row) use ($type) {
                    return round($row->taxes_total, 2);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $branches = $this->branchAccessService->visibleBranches($currentUser);

        return view('admin.sales.index', compact('data', 'type', 'branches'));
    }

    public function create($type)
    {
        $currentUser = Auth::guard('admin-web')->user();
        $customers = Customer::when($type == 'simplified', function ($query) {
            return $query->where('tax_number', null);
        })->where('type', '=', 'customer')->get();
        $branches = $this->branchAccessService->visibleBranches($currentUser);
        $caratTypes = GoldCaratType::query()
            ->whereIn('key', ['crafted', 'scrap', 'pure'])
            ->get()
            ->sortBy(fn (GoldCaratType $caratType) => array_search($caratType->key, ['crafted', 'scrap', 'pure'], true))
            ->values();

        return view('admin.sales.create', [
            'type' => $type,
            'customers' => $customers,
            'branches' => $branches,
            'caratTypes' => $caratTypes,
            'defaultInvoiceTerms' => $this->invoiceTermsService->defaultTerms(),
            'invoiceTermTemplates' => $this->invoiceTermsService->templates(),
            'defaultInvoiceTermsTemplateKey' => $this->invoiceTermsService->defaultTemplateKey(),
        ]);
    }

    public function store(Request $request)
    {
        return $this->sellInvoice($request);
    }

    public function sales_payment_show(Request $request)
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
        $html = view('admin.sales.payment', compact('money', 'type', 'bankAccounts', 'branchId'))->render();
        return $html;
    }

    public function sellInvoice($request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'bill_date' => 'required',
                'customer_id' => 'required|exists:customers,id,type,customer',
                'branch_id' => 'required',
                'bill_client_name' => 'nullable|string|max:255',
                'bill_client_phone' => 'nullable|string|max:50',
                'bill_client_identity_number' => 'nullable|string|max:100',
            ],
                [
                    'bill_date.required' => __('validations.bill_date_required'),
                    'customer_id.required' => __('validations.customer_id_required'),
                    'customer_id.exists' => __('validations.customer_id_exists'),
                    'branch_id.required' => __('validations.branch_id_required'),
                ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()->all()
                ], 422);
            }

            $this->branchAccessService->enforceBranchAccess(Auth::user(), (int) $request->branch_id);

            $lines = array();
            if (count($request->unit_id)) {
                // store header
                $branch = Branch::findOrFail($request->branch_id);
                $activeShift = $this->shiftService->requireActiveShift(Auth::user(), (int) $request->branch_id);
                $customer = Customer::find($request->customer_id);
                $warehouse = $branch->warehouses->first();

                $linesTotal = 0;
                $linesDiscount = 0;
                $linesTotalAfterDiscount = 0;
                $linesTax = 0;
                $linesNetTotal = 0;

                $craftedCostTotal = 0;
                $scrapCostTotal = 0;
                $pureCostTotal = 0;

                foreach ($request->unit_id as $key => $unit_id) {
                    $unit = ItemUnit::find($request->unit_id[$key]);
                    if (!$unit->is_default) {
                        $unit->update([
                            'is_sold' => true,
                        ]);
                    }

                    $item = $unit->item;
                    $taxData = $this->resolveItemTaxData($item);
                    $lineWeight = floatval($request->weight[$key]);
                    $unitPrice = floatval($request->gram_price[$key]);
                    $unitTaxAmount = $unitPrice * $taxData['rate'] / 100;

                    $lineTotal = $unitPrice * $lineWeight;
                    $linesTotal += $lineTotal;

                    $lineDiscount = $request->discount[$key] ?? 0;
                    $linesDiscount += $lineDiscount;

                    $lineTotalAfterDiscount = $lineTotal - $lineDiscount;
                    $linesTotalAfterDiscount += $lineTotalAfterDiscount;

                    $lineTax = $unitTaxAmount * $lineWeight;
                    $linesTax += $lineTax;

                    $lineNetTotal = $lineTotalAfterDiscount + $lineTax;

                    $linesNetTotal += $lineNetTotal;

                    $unitCost = $item->defaultUnit->average_cost_per_gram;

                    $lineNoMetal = floatval($request->no_metal[$key]) * $lineWeight;
                    $line = [
                        'warehouse_id' => $warehouse->id ?? null,
                        'item_id' => $item->id,
                        'no_metal' => $lineNoMetal,
                        'unit_id' => $unit->id,
                        'gold_carat_id' => $item->gold_carat_id,
                        'gold_carat_type_id' => $item->gold_carat_type_id,
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'in_quantity' => 0,
                        'out_quantity' => $request->quantity[$key],
                        'in_weight' => 0,
                        'out_weight' => $lineWeight,
                        'unit_cost' => $unitCost,
                        'unit_price' => $unitPrice,
                        'unit_discount' => 0,
                        'unit_tax' => $unitTaxAmount,
                        'unit_tax_rate' => $taxData['rate'],
                        'unit_tax_id' => $taxData['id'],
                        'line_total' => $lineTotal,
                        'line_discount' => $lineDiscount ?? 0,
                        'line_tax' => $lineTax,
                        'net_total' => $lineNetTotal,
                    ];

                    $lines[] = $line;

                    $caratTypeTotalVariable = $this->resolveInventoryCostBucketForItem($item) . 'CostTotal';
                    ${$caratTypeTotalVariable} += $unitCost * $request->weight[$key];
                }

                $paymentLines = $this->invoicePaymentService->normalizeSalesLines(
                    $request->all(),
                    (int) $branch->id,
                    $linesNetTotal
                );
                $paymentType = $this->invoicePaymentService->resolveStoredPaymentType($paymentLines);

                $invoiceData = [
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->customer_id,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'sale',
                    'payment_type' => $paymentType,
                    'sale_type' => $request->type,
                    'notes' => $request->notes ?? '',
                    'invoice_terms' => $this->invoiceTermsService->resolveSnapshot(
                        $request->input('invoice_terms'),
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
                ];
                $invoiceData = array_merge(
                    $invoiceData,
                    $this->invoicePartySnapshotService->resolve(
                        $customer,
                        $request->input('bill_client_name'),
                        $request->input('bill_client_phone'),
                        $request->input('bill_client_identity_number'),
                    ),
                );
                $invoice = Invoice::create($invoiceData);
                $this->invoicePaymentService->persist($invoice, $paymentLines);

                JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->sales_prepare_journal_entry_details($invoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal));
                $invoice->details()->createMany($lines);
                $sendInvoice = new SendZatcaInvoice($invoice);
                $sendInvoice->send();
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => __('main.created'),
                    'url' => route('sales.show', ['id' => $invoice->id])
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => __('main.nodetails'),
                ]);
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
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);
        if (!in_array($invoice->type, ['sale', 'sale_return'])) {
            abort(404);
        }
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);
        return view('admin.sales_and_sales_return.print', compact('invoice'));
    }

    public function sales_return_index(Request $request)
    {
        $currentUser = $request->user('admin-web');
        $type = $request->type;
        $query = Invoice::query()
            ->where('type', 'sale_return')
            ->where('sale_type', $type);

        $this->branchAccessService->scopeToAccessibleBranch($query, $currentUser);

        $data = $query->orderBy('id', 'DESC')->get();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($type) {
                    if (auth()->user()->canany(['employee.sales_returns.show'])) {
                        $btn = '<a href=' . route('sales_return.show', $row->id) . ' class="btn btn-primary" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button" target="_blank" >
                                    <i class="fa fa-eye"></i>معاينة</a>';
                    }
                    return $btn;
                })
                ->addColumn('bill_number', function ($row) use ($type) {
                    return $row->bill_number;
                })
                ->addColumn('parent_invoice', function ($row) use ($type) {
                    return $row->parent->bill_number;
                })
                ->addColumn('customer', function ($row) use ($type) {
                    return $row->customer->name;
                })
                ->addColumn('net_money', function ($row) use ($type) {
                    return $row->net_total;
                })
                ->addColumn('total_money', function ($row) use ($type) {
                    return $row->lines_total;
                })
                ->addColumn('tax', function ($row) use ($type) {
                    return $row->taxes_total;
                })
                ->addColumn('paid_money', function ($row) use ($type) {
                    return $row->paid_money ?? 0;
                })
                ->addColumn('remain_money', function ($row) use ($type) {
                    return $row->remain_money ?? 0;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.sales_return.index', compact('data', 'type'));
    }

    public function sales_return_create($type, $id)
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->type === 'sale', 404);
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);
        $bankAccounts = BankAccount::query()
            ->active()
            ->where('branch_id', $invoice->branch_id)
            ->orderByDesc('is_default')
            ->orderBy('account_name')
            ->get();

        return view('admin.sales_return.create', compact('type', 'invoice', 'bankAccounts'));
    }

    public function sales_return_store(Request $request, $type, $id)
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->type === 'sale', 404);
        $this->branchAccessService->enforceInvoiceAccess(Auth::user(), $invoice);

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
                    'checkDetail' => ['يجب اختيار صنف واحد على الأقل قبل حفظ المرتجع.'],
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
            $lines = [];

            $craftedCostTotal = 0;
            $scrapCostTotal = 0;
            $pureCostTotal = 0;

            foreach ($returnedDetails as $detail) {
                $unit = ItemUnit::find($detail->unit_id);
                $item = $unit->item;
                $unitTaxAmount = $detail->unit_tax;

                $lineTotal = $detail->line_total;
                $linesTotal += $lineTotal;

                $lineDiscount = $detail->line_discount;
                $linesDiscount += $lineDiscount;

                $lineTotalAfterDiscount = $lineTotal - $lineDiscount;
                $linesTotalAfterDiscount += $lineTotalAfterDiscount;

                $lineTax = $detail->line_tax;
                $linesTax += $lineTax;

                $lineNetTotal = $lineTotalAfterDiscount + $lineTax;

                $linesNetTotal += $lineNetTotal;

                $line = [
                    'parent_id' => $detail->id,
                    'warehouse_id' => $invoice->warehouse_id ?? null,
                    'item_id' => $detail->item_id,
                    'unit_id' => $unit->id,
                    'gold_carat_id' => $detail->gold_carat_id,
                    'gold_carat_type_id' => $detail->gold_carat_type_id,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'in_quantity' => $detail->out_quantity,
                    'out_quantity' => 0,
                    'in_weight' => $detail->out_weight,
                    'out_weight' => 0,
                    'unit_cost' => $detail->unit_cost,
                    'unit_price' => $detail->unit_price,
                    'unit_discount' => $detail->unit_discount,
                    'unit_tax' => $unitTaxAmount,
                    'unit_tax_rate' => $detail->unit_tax_rate,
                    'unit_tax_id' => $detail->unit_tax_id,
                    'line_total' => $lineTotal,
                    'line_discount' => $lineDiscount ?? 0,
                    'line_tax' => $lineTax,
                    'net_total' => $lineNetTotal,
                ];

                $lines[] = $line;

                $caratTypeTotalVariable = $this->resolveInventoryCostBucketForItem($item) . 'CostTotal';
                ${$caratTypeTotalVariable} += $detail->unit_cost * $detail->out_weight;
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
                'type' => 'sale_return',
                'sale_type' => $type,
                'notes' => $request->notes ?? '',
                'invoice_terms' => $invoice->invoice_terms,
                'date' => Carbon::now()->format('Y-m-d'),
                'time' => Carbon::now()->format('H:i:s'),
                'lines_total' => $linesTotal,
                'discount_total' => $linesDiscount,
                'lines_total_after_discount' => $linesTotalAfterDiscount,
                'taxes_total' => $linesTax,
                'net_total' => $linesNetTotal,
                'payment_type' => $this->invoicePaymentService->resolveStoredPaymentType($paymentLines),
                'user_id' => Auth::user()->id,
                'shift_id' => $activeShift->id,
            ] + $this->invoicePartySnapshotService->fromInvoice($invoice));

            $returnInvoice->details()->createMany($lines);
            $this->invoicePaymentService->persist($returnInvoice, $paymentLines);
            JournalEntriesService::invoiceGenerateJournalEntries($returnInvoice, $this->sales_return_prepare_journal_entry_details($returnInvoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal));
            $sendInvoice = new SendZatcaInvoice($returnInvoice);
            $sendInvoice->send();

            DB::commit();
            return redirect()->route('sales_return.index', ['type' => $type])->with('success', __('main.created'));
        } catch (ValidationException $ex) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors($ex->errors())
                ->withInput();
        } catch (\Throwable $ex) {
            DB::rollBack();
            return redirect()->route('sales_return.create', ['type' => $type, 'id' => $id])->with('error', $ex->getMessage());
        }
    }

    public function sales_return_show($id)
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->type === 'sale_return', 404);
        $this->branchAccessService->enforceInvoiceAccess(auth('admin-web')->user(), $invoice);
        return view('admin.sales_and_sales_return.print', compact('invoice'));
    }

    public function sales_prepare_journal_entry_details($invoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal)
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

        // customer account
        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => $invoice->net_total,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        // customer account
        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => 0,
            'credit' => $invoice->net_total,
            'document_date' => $documentDate,
        ];

        // sales account
        $lines[] = [
            'account_id' => $accountSetting->sales_account,
            'debit' => 0,
            'credit' => $invoice->lines_total_after_discount,
            'document_date' => $documentDate,
        ];

        // sales account
        $lines[] = [
            'account_id' => $accountSetting->sales_tax_account,
            'debit' => 0,
            'credit' => $invoice->taxes_total,
            'document_date' => $documentDate,
        ];

        if ($craftedCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_crafted,
                'debit' => 0,
                'credit' => $craftedCostTotal,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_crafted,
                'debit' => $craftedCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($scrapCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_scrap,
                'debit' => 0,
                'credit' => $scrapCostTotal,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_scrap,
                'debit' => $scrapCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($pureCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_pure,
                'debit' => 0,
                'credit' => $pureCostTotal,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_pure,
                'debit' => $pureCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }
        return $lines;
    }

    public function sales_return_prepare_journal_entry_details($invoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        $lines = array_merge(
            $lines,
            $this->invoicePaymentService->journalCreditLines(
                $invoice,
                (int) $accountSetting->safe_account,
                $accountSetting->bank_account ? (int) $accountSetting->bank_account : null,
            ),
        );

        // customer account
        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => 0,
            'credit' => $invoice->net_total,
            'document_date' => $documentDate,
        ];

        // customer account
        $lines[] = [
            'account_id' => $invoice->customer->account_id,
            'debit' => $invoice->net_total,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        // sales account
        $lines[] = [
            'account_id' => $accountSetting->return_sales_account,
            'debit' => $invoice->lines_total_after_discount,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        // sales account
        $lines[] = [
            'account_id' => $accountSetting->sales_tax_account,
            'debit' => $invoice->taxes_total,
            'credit' => 0,
            'document_date' => $documentDate,
        ];

        if ($craftedCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_crafted,
                'debit' => $craftedCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_crafted,
                'debit' => 0,
                'credit' => $craftedCostTotal,
                'document_date' => $documentDate,
            ];
        }

        if ($scrapCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_scrap,
                'debit' => $scrapCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_scrap,
                'debit' => 0,
                'credit' => $scrapCostTotal,
                'document_date' => $documentDate,
            ];
        }

        if ($pureCostTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_pure,
                'debit' => $pureCostTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $accountSetting->cost_account_pure,
                'debit' => 0,
                'credit' => $pureCostTotal,
                'document_date' => $documentDate,
            ];
        }

        return $lines;
    }

    private function resolveItemTaxData($item): array
    {
        $tax = $item->goldCarat?->tax;

        if ($tax) {
            return [
                'id' => $tax->id,
                'rate' => (float) $tax->rate,
            ];
        }

        $fallbackTax = Tax::query()->where('zatca_code', 'O')->first();

        return [
            'id' => $fallbackTax?->id,
            'rate' => (float) ($fallbackTax?->rate ?? 0),
        ];
    }

    private function resolveInventoryCostBucketForItem($item): string
    {
        $bucket = $item->goldCaratType?->key;

        return in_array($bucket, ['crafted', 'scrap', 'pure'], true) ? $bucket : 'crafted';
    }
}
