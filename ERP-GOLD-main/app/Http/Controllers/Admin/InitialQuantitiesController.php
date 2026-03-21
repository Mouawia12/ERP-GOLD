<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\ItemUnit;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;

class InitialQuantitiesController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $data = Invoice::where('type', 'initial_quantities')
            ->orderBy('id', 'DESC')
            ->get();

        $branches = Branch::all();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($type) {
                    return '';
                })
                ->addColumn('bill_number', function ($row) use ($type) {
                    return $row->bill_number;
                })
                ->addColumn('total_quantity', function ($row) use ($type) {
                    return $row->total_quantity;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.initial_quantities.index', compact('data', 'type'));
    }

    public function create()
    {
        $branches = Branch::all();
        $accounts = Account::whereDoesntHave('childrens')->get();

        return view('admin.initial_quantities.create', compact('branches', 'accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_date' => 'required',
            'branch_id' => 'required',
            'credit_account' => 'required|exists:accounts,id',
        ], [
            'bill_date.required' => __('validations.bill_date_required'),
            'branch_id.required' => __('validations.branch_id_required'),
            'credit_account.required' => __('validations.credit_account_required'),
            'credit_account.exists' => __('validations.credit_account_exists'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $lines = array();
            if (count($request->unit_id)) {
                // store header
                $branch = Branch::find($request->branch_id);
                $warehouse = $branch->warehouses->first();

                $craftedTotal = 0;
                $scrapTotal = 0;
                $pureTotal = 0;
                $linesNetTotal = 0;

                foreach ($request->unit_id as $key => $unit_id) {
                    $unit = ItemUnit::find($request->unit_id[$key]);

                    $item = $unit->item;
                    $goldCaratType = $item->goldCaratType;

                    $lineTotal = $request->item_total_cost[$key];
                    $unitCost = $request->item_total_cost[$key] / $request->weight[$key];
                    $caratTypeTotalVariable = $goldCaratType->key . 'Total';
                    ${$caratTypeTotalVariable} += $lineTotal;

                    $linesNetTotal += $lineTotal;

                    $line = [
                        'warehouse_id' => $warehouse->id ?? null,
                        'item_id' => $item->id,
                        'unit_id' => $unit->id,
                        'gold_carat_id' => $item->gold_carat_id,
                        'gold_carat_type_id' => $item->gold_carat_type_id,
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'in_quantity' => 0,
                        'out_quantity' => 1,
                        'in_weight' => $request->weight[$key],
                        'out_weight' => 0,
                        'unit_cost' => $unitCost,
                        'unit_price' => $unitCost,
                        'unit_discount' => 0,
                        'unit_tax' => 0,
                        'unit_tax_rate' => 0,
                        'unit_tax_id' => null,
                        'line_total' => $lineTotal,
                        'line_discount' => 0,
                        'line_tax' => 0,
                        'net_total' => $lineTotal,
                    ];

                    $actualBalance = $item->actual_balance;
                    if ($actualBalance < 0) {
                        $actualBalance = 0;
                    }
                    $averageCost = (($item->defaultUnit->average_cost_per_gram * $actualBalance) + ($unitCost * $request->weight[$key])) / ($actualBalance + $request->weight[$key]);

                    $item->defaultUnit()->update(['initial_cost_per_gram' => $unitCost, 'average_cost_per_gram' => $averageCost, 'current_cost_per_gram' => $unitCost]);

                    $lines[] = $line;
                }

                $invoice = Invoice::create([
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->customer_id,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'initial_quantities',
                    'account_id' => $request->credit_account,
                    'notes' => $request->notes ?? '',
                    'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                    'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                    'lines_total' => $linesNetTotal,
                    'discount_total' => 0,
                    'lines_total_after_discount' => $linesNetTotal,
                    'taxes_total' => 0,
                    'net_total' => $linesNetTotal,
                    'user_id' => Auth::user()->id,
                ]);

                JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->initial_quantities_prepare_journal_entry_details($invoice, $craftedTotal, $scrapTotal, $pureTotal));
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

    public function show($id)
    {
        $invoice = Invoice::find($id);
        if (!in_array($invoice->type, ['sale', 'sale_return'])) {
            return redirect()->route('sales.index')->with('error', __('main.not_found'));
        }
        return view('admin.sales_and_sales_return.print', compact('invoice'));
    }

    public function initial_quantities_prepare_journal_entry_details($invoice, $craftedTotal, $scrapTotal, $pureTotal)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        if ($craftedTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_crafted,
                'debit' => $craftedTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($scrapTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_scrap,
                'debit' => $scrapTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        if ($pureTotal > 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_pure,
                'debit' => $pureTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        // capital account
        $lines[] = [
            'account_id' => $invoice->account_id,
            'debit' => 0,
            'credit' => $invoice->net_total,
            'document_date' => $documentDate,
        ];

        return $lines;
    }
}
