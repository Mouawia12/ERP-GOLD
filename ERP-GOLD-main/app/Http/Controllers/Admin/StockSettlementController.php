<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DataTables;

class StockSettlementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.stock_settlements.show,admin-web')->only(['index', 'show']);
        $this->middleware('permission:employee.stock_settlements.add,admin-web')->only([
            'create',
            'create_by_default',
            'get_carat_type_stock',
            'search',
            'show_uncounted_items',
            'store',
            'store_by_default',
        ]);
    }

    public function index(Request $request)
    {
        $type = $request->type;
        $data = Invoice::where('type', 'stock_settlements')
            ->with(['branch', 'user', 'details'])
            ->orderBy('id', 'DESC')
            ->get();

        $branches = Branch::all();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('stock_settlements.show', $row->id) . '" class="btn btn-sm btn-success"><i class="fa fa-eye"></i></a>';
                })
                ->addColumn('bill_number', function ($row) {
                    return $row->bill_number;
                })
                ->addColumn('branch_name', function ($row) {
                    return $row->branch?->name ?? '-';
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user?->name ?? '-';
                })
                ->addColumn('total_quantity', function ($row) {
                    return round($row->total_quantity, 3);
                })
                ->addColumn('difference_weight', function ($row) {
                    return round($row->details->sum(fn ($detail) => $detail->settlementDiffWeightValue), 3);
                })
                ->addColumn('net_total', function ($row) {
                    return round($row->net_total, 3);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.stock_settlements.index', compact('data', 'type'));
    }

    public function create()
    {
        $branches = Branch::all();
        $accounts = Account::whereDoesntHave('childrens')->get();

        return view('admin.stock_settlements.create', compact('branches', 'accounts'));
    }

    public function create_by_default()
    {
        $branches = Branch::all();
        $accounts = Account::whereDoesntHave('childrens')->get();
        $caratTypes = GoldCaratType::all();
        $defaultCaratStock = GoldCaratType::where('key', 'crafted')->first()->getStock();
        $defaultCarat = GoldCarat::where('transform_factor', 1)->first();
        $row = [
            'carat_id' => $defaultCarat->id,
            'title' => $defaultCarat->title,
            'actual_balance' => $defaultCaratStock,
            'weight' => 0,
            'diff_weight' => 0,
        ];
        return view('admin.stock_settlements.create_by_default', compact('branches', 'accounts', 'caratTypes', 'row'));
    }

    public function get_carat_type_stock(Request $request)
    {
        $carat_type = $request->carat_type;
        $caratType = GoldCaratType::where('key', $carat_type)->first();
        $stock = $caratType->getStock();
        $defaultCarat = GoldCarat::where('transform_factor', 1)->first();
        $row = [
            'carat_id' => $defaultCarat->id,
            'title' => $defaultCarat->title,
            'actual_balance' => $stock,
            'weight' => 0,
            'diff_weight' => 0,
        ];
        return response()->json([
            'status' => true,
            'row' => $row
        ]);
    }

    public function search(Request $request)
    {
        $code = trim((string) $request->code);
        if (empty($code)) {
            return response()->json([
                'status' => false,
                'message' => __('main.required'),
                'data' => [],
            ]);
        }
        $branch_id = (int) $request->branch_id;
        $units = ItemUnit::query()
            ->with(['item.goldCarat', 'item.goldCaratType'])
            ->where('is_sold', 0)
            ->where('weight', '>', 0)
            ->whereHas('item', function ($itemQuery) use ($branch_id, $code) {
                $itemQuery
                    ->where('branch_id', $branch_id)
                    ->where(function ($searchQuery) use ($code) {
                        $searchQuery
                            ->where('title', 'like', '%' . $code . '%')
                            ->orWhere('code', 'like', '%' . $code . '%');
                    });
            })
            ->orWhere(function ($unitQuery) use ($branch_id, $code) {
                $unitQuery
                    ->where('is_sold', 0)
                    ->where('weight', '>', 0)
                    ->whereNotNull('barcode')
                    ->where('barcode', '!=', '')
                    ->where('barcode', 'like', '%' . $code . '%')
                    ->whereHas('item', function ($itemQuery) use ($branch_id) {
                        $itemQuery->where('branch_id', $branch_id);
                    });
            })
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 WHEN barcode LIKE ? THEN 1 ELSE 2 END', [$code, $code . '%'])
            ->orderByRaw('CASE WHEN is_default = 0 THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->limit(25)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $this->formatSearch($units),
        ]);
    }

    public function show_uncounted_items(Request $request)
    {
        $branch_id = $request->branch_id;
        $countedUnits = collect($request->units)->pluck('unit_id')->toArray();

        $items = Item::query()->where('branch_id', $branch_id)->whereHas('units', function ($query) use ($countedUnits) {
            $query->where('is_default', false)->where('is_sold', false)->whereNotIn(
                'id',
                $countedUnits
            );
        })->with(['units' => function ($query) use ($countedUnits) {
            $query->where('is_default', false)->where('is_sold', false)->whereNotIn(
                'id',
                $countedUnits
            );
        }])->get();
        $view = view('admin.stock_settlements.uncounted_body', compact('items'))->render();
        return response()->json([
            'status' => true,
            'data' => $view,
        ]);
    }

    private function formatSearch($units)
    {
        return $units->map(function ($unit) {
            $item = $unit->item;
            $itemName = $item?->getTranslation('title', 'ar') ?? $item?->title ?? '-';
            $goldCaratTitle = $item?->goldCarat?->getTranslation('title', 'ar') ?? $item?->goldCarat?->title;
            $goldCaratTypeTitle = $item?->goldCaratType?->title;
            $caratLabel = trim(implode(' <br> ', array_filter([$goldCaratTitle, $goldCaratTypeTitle])));

            return [
                'item_id' => $item->id,
                'actual_balance' => (float) $item->actual_balance,
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => (float) $unit->weight,
                'item_name' => $itemName . ' <br> ' . $unit->barcode,
                'item_name_without_break' => trim($itemName . ' ' . $unit->barcode),
                'carat' => $caratLabel !== '' ? $caratLabel : ($item->inventory_classification_label ?? '-'),
                'carat_id' => $item->gold_carat_id,
                'diff_weight' => 0,
            ];
        });
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_date' => 'required',
            'branch_id' => 'required',
            'account_id' => 'required|exists:accounts,id',
            'item_id' => 'required|array'
        ], [
            'bill_date.required' => __('validations.bill_date_required'),
            'branch_id.required' => __('validations.branch_id_required'),
            'account_id.required' => __('validations.stock_settlements_account_id_required'),
            'account_id.exists' => __('validations.stock_settlements_account_id_exists'),
            'item_id.required' => __('validations.item_id_required'),
            'item_id.array' => __('validations.item_id_array'),
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
            if (count($request->item_id)) {
                // store header
                $branch = Branch::find($request->branch_id);
                $warehouse = $branch->warehouses->first();

                $craftedTotal = 0;
                $scrapTotal = 0;
                $pureTotal = 0;
                $linesNetTotal = 0;

                foreach ($request->item_id as $key => $item_id) {
                    $diffWeight = floatval($request->diff_weight[$key]);
                    if ($diffWeight == 0) {
                        continue;
                    }

                    $item = Item::find($item_id);

                    $goldCaratType = $item->goldCaratType;

                    $unitCost = $item->defaultUnit->average_cost_per_gram;

                    $lineTotalWithoutAbs = $diffWeight * $unitCost;
                    $lineTotal = abs($lineTotalWithoutAbs);

                    $caratTypeTotalVariable = $goldCaratType->key . 'Total';
                    ${$caratTypeTotalVariable} += $lineTotalWithoutAbs;

                    $linesNetTotal += $lineTotal;

                    $line = [
                        'warehouse_id' => $warehouse->id ?? null,
                        'item_id' => $item->id,
                        'gold_carat_id' => $item->gold_carat_id,
                        'gold_carat_type_id' => $item->gold_carat_type_id,
                        'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                        'in_quantity' => 0,
                        'out_quantity' => 1,
                        'in_weight' => ($diffWeight > 0) ? abs($diffWeight) : 0,
                        'out_weight' => ($diffWeight < 0) ? abs($diffWeight) : 0,
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
                        'stock_actual_weight' => floatval($request->actual_balance[$key] ?? 0),
                        'stock_counted_weight' => floatval($request->weight[$key] ?? 0),
                        'stock_diff_weight' => $diffWeight,
                    ];
                    $lines[] = $line;
                }
                if ($linesNetTotal == 0) {
                    return response()->json([
                        'status' => false,
                        'errors' => [__('main.nodetails')]
                    ], 422);
                }
                $invoice = Invoice::create([
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->customer_id,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'stock_settlements',
                    'account_id' => $request->account_id,
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

                JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->settlements_prepare_journal_entry_details($invoice, $craftedTotal, $scrapTotal, $pureTotal));
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
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function store_by_default(Request $request)
    {
        $defaultCaratPrice = GoldPrice::latestSnapshot()?->ounce_21_price;

        if ($defaultCaratPrice === null) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تحديث أسعار الذهب داخل النظام قبل تنفيذ الجرد الافتراضي.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'bill_date' => 'required',
            'branch_id' => 'required',
            'account_id' => 'required|exists:accounts,id',
            'carat_id' => 'required|exists:gold_carats,id',
            'carat_type' => 'required|in:' . collect(GoldCaratType::all())->pluck('key')->implode(',')
        ], [
            'bill_date.required' => __('validations.bill_date_required'),
            'branch_id.required' => __('validations.branch_id_required'),
            'account_id.required' => __('validations.stock_settlements_account_id_required'),
            'account_id.exists' => __('validations.stock_settlements_account_id_exists'),
            'carat_type.required' => __('validations.carat_type_required'),
            'carat_type.in' => __('validations.carat_type_in'),
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
            $defaultCarat = GoldCarat::find($request->carat_id);
            if ($defaultCarat) {
                // store header
                $branch = Branch::find($request->branch_id);
                $accountSetting = $branch->accountSetting;
                $warehouse = $branch->warehouses->first();
                $financialYear = FinancialYear::where('is_active', 1)->first();

                $diffWeight = floatval($request->diff_weight);
                if ($diffWeight == 0) {
                    return response()->json([
                        'status' => false,
                        'errors' => [__('main.nodetails')]
                    ], 422);
                }
                $craftedTotal = 0;
                $scrapTotal = 0;
                $pureTotal = 0;
                $linesNetTotal = 0;

                $goldCaratType = GoldCaratType::where('key', $request->carat_type)->first();

                $unitCost = $defaultCaratPrice;

                $lineTotalWithoutAbs = $diffWeight * $unitCost;
                $lineTotal = abs($lineTotalWithoutAbs);

                $caratTypeTotalVariable = $goldCaratType->key . 'Total';
                ${$caratTypeTotalVariable} += $lineTotalWithoutAbs;

                $linesNetTotal += $lineTotal;

                $line = [
                    'warehouse_id' => $warehouse->id ?? null,
                    'item_id' => null,
                    'gold_carat_id' => $defaultCarat->id,
                    'gold_carat_type_id' => $goldCaratType->id,
                    'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                    'in_quantity' => 0,
                    'out_quantity' => 1,
                    'in_weight' => ($diffWeight > 0) ? abs($diffWeight) : 0,
                    'out_weight' => ($diffWeight < 0) ? abs($diffWeight) : 0,
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
                    'stock_actual_weight' => floatval($request->actual_balance ?? 0),
                    'stock_counted_weight' => floatval($request->weight ?? 0),
                    'stock_diff_weight' => $diffWeight,
                ];
                $lines[] = $line;
                $invoice = Invoice::create([
                    'branch_id' => $request->branch_id,
                    'warehouse_id' => $warehouse->id ?? null,
                    'customer_id' => $request->customer_id,
                    'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                    'type' => 'stock_settlements',
                    'account_id' => $request->account_id,
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

                JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->settlements_prepare_journal_entry_details($invoice, $craftedTotal, $scrapTotal, $pureTotal));
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
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::query()
            ->with(['branch', 'user', 'account', 'details.item', 'details.carat', 'details.goldCaratType'])
            ->findOrFail($id);

        if ($invoice->type !== 'stock_settlements') {
            return redirect()->route('stock_settlements.index')->with('error', __('main.not_found'));
        }

        $summary = [
            'lines_count' => $invoice->details->count(),
            'total_actual_weight' => round($invoice->details->sum(fn ($detail) => $detail->settlementActualWeightValue), 3),
            'total_counted_weight' => round($invoice->details->sum(fn ($detail) => $detail->settlementCountedWeightValue), 3),
            'net_diff_weight' => round($invoice->details->sum(fn ($detail) => $detail->settlementDiffWeightValue), 3),
            'absolute_diff_weight' => round($invoice->details->sum(fn ($detail) => abs($detail->settlementDiffWeightValue)), 3),
        ];

        return view('admin.stock_settlements.show', compact('invoice', 'summary'));
    }

    public function settlements_prepare_journal_entry_details($invoice, $craftedTotal, $scrapTotal, $pureTotal)
    {
        $branch = $invoice->branch;
        $accountSetting = $branch->accountSetting;
        $documentDate = $invoice->date;
        $lines = [];

        if ($craftedTotal != 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_crafted,
                'debit' => ($craftedTotal > 0) ? abs($craftedTotal) : 0,
                'credit' => ($craftedTotal < 0) ? abs($craftedTotal) : 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $invoice->account_id,
                'debit' => ($craftedTotal < 0) ? abs($craftedTotal) : 0,
                'credit' => ($craftedTotal > 0) ? abs($craftedTotal) : 0,
                'document_date' => $documentDate,
            ];
        }

        if ($scrapTotal != 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_scrap,
                'debit' => ($scrapTotal > 0) ? abs($scrapTotal) : 0,
                'credit' => ($scrapTotal < 0) ? abs($scrapTotal) : 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $invoice->account_id,
                'debit' => ($scrapTotal < 0) ? abs($scrapTotal) : 0,
                'credit' => ($scrapTotal > 0) ? abs($scrapTotal) : 0,
                'document_date' => $documentDate,
            ];
        }

        if ($pureTotal != 0) {
            $lines[] = [
                'account_id' => $accountSetting->stock_account_pure,
                'debit' => ($pureTotal > 0) ? abs($pureTotal) : 0,
                'credit' => ($pureTotal < 0) ? abs($pureTotal) : 0,
                'document_date' => $documentDate,
            ];

            $lines[] = [
                'account_id' => $invoice->account_id,
                'debit' => ($pureTotal < 0) ? abs($pureTotal) : 0,
                'credit' => ($pureTotal > 0) ? abs($pureTotal) : 0,
                'document_date' => $documentDate,
            ];
        }

        return $lines;
    }
}
