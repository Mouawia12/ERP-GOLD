<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\ItemUnit;
use App\Services\Zatca\SendZatcaInvoice;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $data = Invoice::where('type', 'sale')
            ->where('sale_type', $type)
            ->orderBy('id', 'DESC')
            ->get();

        $branches = Branch::all();

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

        return view('admin.sales.index', compact('data', 'type'));
    }

    public function create($type)
    {
        $customers = Customer::when($type == 'simplified', function ($query) {
            return $query->where('tax_number', null);
        })->where('type', '=', 'customer')->get();
        $branches = Branch::all();

        return view('admin.sales.create', compact('type', 'customers', 'branches'));
    }

    public function store(Request $request)
    {
        return $this->sellInvoice($request);
    }

    public function sales_payment_show(Request $request)
    {
        $money = $request->net_after_discount;
        $type = $request->document_type;
        $html = view('admin.sales.payment', compact('money', 'type'))->render();
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

            $lines = array();
            if (count($request->unit_id)) {
                // store header
                $branch = Branch::find($request->branch_id);
                $warehouse = $branch->warehouses->first();

                $linesTotal = 0;
                $linesDiscount = 0;
                $linesTotalAfterDiscount = 0;
                $linesTax = 0;
                $linesNetTotal = 0;

                $craftedCostTotal = 0;
                $scrapCostTotal = 0;
                $pureCostTotal = 0;

                $paymentType = (floatval($request->cash) > 0) ? 'cash' : 'credit_card';
                foreach ($request->unit_id as $key => $unit_id) {
                    $unit = ItemUnit::find($request->unit_id[$key]);
                    if (!$unit->is_default) {
                        $unit->update([
                            'is_sold' => true,
                        ]);
                    }

                    $item = $unit->item;
                    $taxTax = $item->goldCarat->tax;
                    $lineWeight = floatval($request->weight[$key]);
                    $unitPrice = floatval($request->gram_price[$key]);
                    $unitTaxAmount = $unitPrice * floatval($taxTax->rate) / 100;

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
                        'unit_tax_rate' => $taxTax->rate,
                        'unit_tax_id' => $taxTax->id,
                        'line_total' => $lineTotal,
                        'line_discount' => $lineDiscount ?? 0,
                        'line_tax' => $lineTax,
                        'net_total' => $lineNetTotal,
                    ];

                    $lines[] = $line;

                    $caratTypeTotalVariable = $item->goldCaratType->key . 'CostTotal';
                    ${$caratTypeTotalVariable} += $unitCost * $request->weight[$key];
                }

                $invoiceData = [
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->customer_id,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'sale',
                    'payment_type' => $paymentType,
                    'sale_type' => $request->type,
                    'notes' => $request->notes ?? '',
                    'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                    'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                    'lines_total' => $linesTotal,
                    'discount_total' => $linesDiscount,
                    'lines_total_after_discount' => $linesTotalAfterDiscount,
                    'taxes_total' => $linesTax,
                    'net_total' => $linesNetTotal,
                    'user_id' => Auth::user()->id,
                ];
                if ($request->bill_client_phone) {
                    $invoiceData['bill_client_phone'] = $request->bill_client_phone;
                }
                if ($request->bill_client_name) {
                    $invoiceData['bill_client_name'] = $request->bill_client_name;
                }
                $invoice = Invoice::create($invoiceData);

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
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::find($id);
        if (!in_array($invoice->type, ['sale', 'sale_return'])) {
            return redirect()->route('sales.index')->with('error', __('main.not_found'));
        }
        return view('admin.sales_and_sales_return.print', compact('invoice'));
    }

    public function sales_return_index(Request $request)
    {
        $type = $request->type;
        $data = Invoice::where('type', 'sale_return')
            ->where('sale_type', $type)
            ->orderBy('id', 'DESC')
            ->get();

        $branches = Branch::all();

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
        $invoice = Invoice::find($id);
        return view('admin.sales_return.create', compact('type', 'invoice'));
    }

    public function sales_return_store(Request $request, $type, $id)
    {
        $invoice = Invoice::find($id);

        try {
            DB::beginTransaction();
            $returnedDetails = $invoice->details()->whereIn('id', $request->checkDetail)->get();

            $linesTotal = 0;
            $linesDiscount = 0;
            $linesTotalAfterDiscount = 0;
            $linesTax = 0;
            $linesNetTotal = 0;

            $craftedCostTotal = 0;
            $scrapCostTotal = 0;
            $pureCostTotal = 0;

            foreach ($returnedDetails as $detail) {
                $unit = ItemUnit::find($detail->unit_id);
                $item = $unit->item;
                $goldCaratType = $item->goldCaratType;

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

                $caratTypeTotalVariable = $item->goldCaratType->key . 'CostTotal';
                ${$caratTypeTotalVariable} += $detail->unit_cost * $detail->out_weight;
            }

            $returnInvoice = $invoice->returnInvoices()->create([
                'branch_id' => $invoice->branch_id,
                'warehouse_id' => $invoice->warehouse_id ?? null,
                'customer_id' => $invoice->customer_id,
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'type' => 'sale_return',
                'sale_type' => $type,
                'notes' => $request->notes ?? '',
                'date' => Carbon::now()->format('Y-m-d'),
                'time' => Carbon::now()->format('H:i:s'),
                'lines_total' => $linesTotal,
                'discount_total' => $linesDiscount,
                'lines_total_after_discount' => $linesTotalAfterDiscount,
                'taxes_total' => $linesTax,
                'net_total' => $linesNetTotal,
                'user_id' => Auth::user()->id,
            ]);
            JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->sales_return_prepare_journal_entry_details($invoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal));
            $returnInvoice->details()->createMany($lines);
            $sendInvoice = new SendZatcaInvoice($returnInvoice);
            $sendInvoice->send();

            DB::commit();
            return redirect()->route('sales_return.index', ['type' => $type])->with('success', __('main.created'));
        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->route('sales_return.create', ['type' => $type, 'id' => $id])->with('error', $ex->getMessage());
        }
    }

    public function sales_return_show($id)
    {
        $invoice = Invoice::find($id);
        return view('admin.sales_and_sales_return.print', compact('invoice'));
    }

    public function sales_prepare_journal_entry_details($invoice, $craftedCostTotal, $scrapCostTotal, $pureCostTotal)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        if ($invoice->payment_type == 'cash') {
            // safe account
            $lines[] = [
                'account_id' => $accountSetting->safe_account,
                'debit' => $invoice->net_total,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        } else {
            // bank account
            $lines[] = [
                'account_id' => $accountSetting->bank_account,
                'debit' => $invoice->net_total,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

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

        if ($invoice->payment_type == 'cash') {
            // safe account
            $lines[] = [
                'account_id' => $accountSetting->safe_account,
                'debit' => 0,
                'credit' => $invoice->net_total,
                'document_date' => $documentDate,
            ];
        } else {
            // bank account
            $lines[] = [
                'account_id' => $accountSetting->bank_account,
                'debit' => 0,
                'credit' => $invoice->net_total,
                'document_date' => $documentDate,
            ];
        }

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
}
