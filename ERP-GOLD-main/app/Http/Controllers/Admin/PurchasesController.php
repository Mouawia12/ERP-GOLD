<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Invoice;
use App\Models\ItemUnit;
use App\Models\Tax;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;

class PurchasesController extends Controller
{
    public function index(Request $request)
    {
        $data = Invoice::where('type', 'purchase')
            ->orderBy('id', 'DESC')
            ->get();

        $branches = Branch::all();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    if (auth()->user()->canany(['employee.purchase_invoices.show'])) {
                        $btn = '<a href=' . route('purchases.show', $row->id) . ' class="btn btn-primary" 
                                    value="' . $row->id . '" role="button" data-bs-toggle="button" target="_blank" >
                                    <i class="fa fa-eye"></i>معاينة</a>';
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
        $customers = Customer::where('type', '=', 'supplier')->get();
        $branches = Branch::all();
        $caratTypes = GoldCaratType::all();

        return view('admin.purchases.create', compact('customers', 'branches', 'caratTypes'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'bill_date' => 'required',
                'branch_id' => 'required',
                'carat_type' => 'required|exists:gold_carat_types,key',
                'purchase_type' => 'required|in:' . implode(',', config('settings.purchase_types')),
                'supplier_id' => 'required|exists:customers,id,type,supplier',
                'weight' => 'required|array'
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
            $purchaseType = $request->purchase_type;
            $financialYear = FinancialYear::where('is_active', 1)->first();

            if ($purchaseType != 'normal') {
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

            $branch = Branch::find($request->branch_id);
            $accountSetting = $branch->accountSetting;
            $caratType = $request->carat_type;
            $lines = array();
            $stockLines = array();
            if (count($request->unit_id)) {
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

                    $lineTotalWeight = $request->weight[$key];
                    $lineTotalLaborCost = $request->item_total_labor_cost[$key];
                    $laborTotal += $lineTotalLaborCost;
                    $unitLaborCost = $lineTotalLaborCost / $lineTotalWeight;

                    if ($purchaseType == 'normal') {
                        $lineTotalCost = $request->item_total_cost[$key];
                        $totalCost += $lineTotalCost;

                        $lineTotal = $lineTotalCost + $lineTotalLaborCost;

                        $unitCost = $lineTotalCost / $lineTotalWeight;

                        $gramPrice = $lineTotal / $lineTotalWeight;
                    } else {
                        if ($purchaseType == 'discount_from_scrap') {
                            $goldCaratType = GoldCaratType::where('key', 'scrap')->first();
                            $scapAccount = Account::find($accountSetting->stock_account_scrap);
                            $unitCost = $scapAccount->closingBalance($financialYear->from, $financialYear->to) / $goldCaratType->getStock();
                            $discountFromScraptotal += $unitCost * $lineTotalWeight;
                        } else {
                            $goldCaratType = GoldCaratType::where('key', 'pure')->first();
                            $pureAccount = Account::find($accountSetting->stock_account_pure);
                            $unitCost = $pureAccount->closingBalance($financialYear->from, $financialYear->to) / $goldCaratType->getStock();
                            $discountFromPuretotal += $unitCost * $lineTotalWeight;
                        }
                        $lineTotalCost = $unitCost * $lineTotalWeight;
                        $totalCost += $lineTotalCost;
                        $lineTotal = $lineTotalLaborCost;
                        $gramPrice = ($lineTotalCost + $lineTotalLaborCost) / $lineTotalWeight;
                    }

                    $item = $unit->item;
                    $taxTax = ($caratType == 'crafted') ? $item->goldCarat->tax : Tax::where('zatca_code', 'O')->first();
                    $taxRate = $taxTax->rate;
                    $unitTaxAmount = ($lineTotal * $taxRate / 100) / $request->weight[$key];

                    $linesTotal += $lineTotal;

                    $lineDiscount = $request->discount[$key] ?? 0;
                    $linesDiscount += $lineDiscount;

                    $lineTotalAfterDiscount = $lineTotal - $lineDiscount;
                    $linesTotalAfterDiscount += $lineTotalAfterDiscount;

                    $lineTax = $unitTaxAmount * $request->weight[$key];
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
                        'unit_tax_rate' => $taxTax->rate,
                        'unit_tax_id' => $taxTax->id,
                        'line_total' => $lineTotal,
                        'line_discount' => $lineDiscount ?? 0,
                        'line_tax' => $lineTax,
                        'net_total' => $lineNetTotal,
                    ];
                    if ($purchaseType != 'normal') {
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
                            'unit_tax_rate' => $taxTax->rate,
                            'unit_tax_id' => $taxTax->id,
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
                    $averageCost = (($item->defaultUnit->average_cost_per_gram * $actualBalance) + ($unitCost * $request->weight[$key])) / ($actualBalance + $request->weight[$key]);

                    $item->defaultUnit()->update(['initial_cost_per_gram' => $unitCost, 'average_cost_per_gram' => $averageCost, 'current_cost_per_gram' => $unitCost]);

                    $lines[] = $line;
                    if ($purchaseType != 'normal') {
                        $stockLines[] = $stockLine;
                    }
                }

                $invoice = Invoice::create([
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->supplier_id,
                    'supplier_bill_number' => $request->supplier_bill_number ?? null,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'purchase',
                    'purchase_type' => $request->purchase_type ?? 'normal',
                    'purchase_carat_type_id' => GoldCaratType::where('key', $request->carat_type)->first()->id,
                    'notes' => $request->notes ?? '',
                    'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                    'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                    'lines_total' => $linesTotal,
                    'discount_total' => $linesDiscount,
                    'lines_total_after_discount' => $linesTotalAfterDiscount,
                    'taxes_total' => $linesTax,
                    'net_total' => $linesNetTotal,
                    'user_id' => Auth::user()->id,
                ]);

                if ($purchaseType != 'normal' && count($stockLines) > 0) {
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
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                        'lines_total' => $linesTotal,
                        'discount_total' => $linesDiscount,
                        'lines_total_after_discount' => $linesTotalAfterDiscount,
                        'taxes_total' => $linesTax,
                        'net_total' => $linesNetTotal,
                        'user_id' => Auth::user()->id,
                    ]);
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
                    'message' => __('main.nodetails')
                ]);
            }
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ]);
        }
    }

    function convertCarat($weight, $fromFactor, $toFactor)
    {
        return round($weight * ($fromFactor / $toFactor), 3);
    }

    public function show($id)
    {
        $invoice = Invoice::find($id);
        if (!in_array($invoice->type, ['purchase', 'purchase_return'])) {
            return redirect()->route('purchases.index')->with('error', __('main.not_found'));
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
            $stockAccount = 'stock_account_' . $invoice->purchaseCaratType->key;
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
}
