<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
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

        $user = $this->currentUser();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($user) {
                    $buttons = '';

                    if ($user && $user->can('employee.initial_quantities.edit')) {
                        $buttons .= '<a href="' . route('initial_quantities.edit', $row->id) . '" '
                            . 'class="btn btn-labeled btn-primary" title="' . __('main.edit') . '">'
                            . '<i class="fa fa-edit"></i></a> ';
                    }

                    if ($user && $user->can('employee.initial_quantities.delete')) {
                        $buttons .= '<button type="button" class="btn btn-labeled btn-danger deleteBtn" '
                            . 'data-id="' . $row->id . '" data-bill="' . e($row->bill_number) . '" '
                            . 'title="' . __('main.delete') . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $buttons;
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
                    'financial_year' => FinancialYear::activeOrFail()->id,
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

    public function edit($id)
    {
        abort_unless($this->currentUser()?->can('employee.initial_quantities.edit'), 403);

        $invoice = Invoice::where('type', 'initial_quantities')
            ->with(['details.item', 'details.carat', 'details.unit'])
            ->findOrFail($id);

        $branches = Branch::all();
        $accounts = Account::whereDoesntHave('childrens')->get();

        // Pre-seed the line rows the create/edit JS expects (mirrors the item
        // search payload) so existing lines render on load.
        $lineSeeds = $invoice->details->map(function ($detail) {
            $carat = $detail->carat;

            return [
                'unit_id' => $detail->unit_id,
                'item_name' => optional($detail->item)->name ?? '',
                'carat' => optional($carat)->label ?? '',
                'weight' => (float) $detail->in_weight,
                'quantity_balance' => (float) (optional($detail->item)->actual_balance ?? 0),
                'item_total_cost' => (float) $detail->line_total,
                'carat_transform_factor' => (float) (optional($carat)->transform_factor ?? 0),
            ];
        })->values();

        return view('admin.initial_quantities.edit', compact('invoice', 'branches', 'accounts', 'lineSeeds'));
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->currentUser()?->can('employee.initial_quantities.edit'), 403);

        $invoice = Invoice::where('type', 'initial_quantities')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bill_date' => 'required',
            'branch_id' => 'required',
            'credit_account' => 'required|exists:accounts,id',
            'unit_id' => 'required|array',
        ], [
            'bill_date.required' => __('validations.bill_date_required'),
            'branch_id.required' => __('validations.branch_id_required'),
            'credit_account.required' => __('validations.credit_account_required'),
            'credit_account.exists' => __('validations.credit_account_exists'),
            'unit_id.required' => __('main.nodetails'),
            'unit_id.array' => __('main.nodetails'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $branch = Branch::find($request->branch_id);
            $warehouse = $branch->warehouses->first();

            // Items touched before and after — both need their cost recomputed.
            $affectedItemIds = $invoice->details()->pluck('item_id')->all();

            $built = $this->buildInitialQuantityLines($request, $warehouse);
            $affectedItemIds = array_merge($affectedItemIds, array_column($built['lines'], 'item_id'));

            // Reverse the old journal + lines, then re-apply the edited document.
            $this->reverseInvoiceJournal($invoice);
            $invoice->details()->delete();

            $invoice->update([
                'branch_id' => $request->branch_id,
                'warehouse_id' => $warehouse->id ?? null,
                'customer_id' => $request->customer_id,
                'account_id' => $request->credit_account,
                'notes' => $request->notes ?? '',
                'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                'time' => Carbon::parse($request->bill_date)->format('H:i:s'),
                'lines_total' => $built['linesNetTotal'],
                'discount_total' => 0,
                'lines_total_after_discount' => $built['linesNetTotal'],
                'taxes_total' => 0,
                'net_total' => $built['linesNetTotal'],
            ]);

            $invoice->details()->createMany($built['lines']);

            $freshInvoice = $invoice->fresh();
            JournalEntriesService::invoiceGenerateJournalEntries(
                $freshInvoice,
                $this->initial_quantities_prepare_journal_entry_details(
                    $freshInvoice,
                    $built['craftedTotal'],
                    $built['scrapTotal'],
                    $built['pureTotal']
                )
            );

            $this->recomputeItemsCost($affectedItemIds);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('main.updated')
            ]);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        abort_unless($this->currentUser()?->can('employee.initial_quantities.delete'), 403);

        $invoice = Invoice::where('type', 'initial_quantities')->findOrFail($id);

        try {
            DB::beginTransaction();

            $affectedItemIds = $invoice->details()->pluck('item_id')->all();

            $this->reverseInvoiceJournal($invoice);
            // invoice_details cascade on the invoice delete (FK onDelete cascade),
            // which also reverses stock since Item::actual_balance is a live SUM.
            $invoice->delete();

            $this->recomputeItemsCost($affectedItemIds);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('main.deleted')
            ]);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Build the invoice_details rows and carat-type totals for an initial
     * quantities document (shared shape with store(), without mutating costs).
     *
     * @return array{lines:array<int,array<string,mixed>>,craftedTotal:float,scrapTotal:float,pureTotal:float,linesNetTotal:float}
     */
    private function buildInitialQuantityLines(Request $request, $warehouse): array
    {
        $lines = [];
        $craftedTotal = 0;
        $scrapTotal = 0;
        $pureTotal = 0;
        $linesNetTotal = 0;

        foreach ($request->unit_id as $key => $unit_id) {
            $unit = ItemUnit::find($unit_id);
            if (! $unit) {
                continue;
            }

            $item = $unit->item;
            $goldCaratType = $item->goldCaratType;

            $weight = (float) ($request->weight[$key] ?? 0);
            $lineTotal = (float) ($request->item_total_cost[$key] ?? 0);
            $unitCost = $weight > 0 ? $lineTotal / $weight : 0;

            $caratTypeTotalVariable = $goldCaratType->key . 'Total';
            ${$caratTypeTotalVariable} += $lineTotal;
            $linesNetTotal += $lineTotal;

            $lines[] = [
                'warehouse_id' => $warehouse->id ?? null,
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'gold_carat_id' => $item->gold_carat_id,
                'gold_carat_type_id' => $item->gold_carat_type_id,
                'date' => Carbon::parse($request->bill_date)->format('Y-m-d'),
                'in_quantity' => 0,
                'out_quantity' => 1,
                'in_weight' => $weight,
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
        }

        return compact('lines', 'craftedTotal', 'scrapTotal', 'pureTotal', 'linesNetTotal');
    }

    /**
     * Soft-delete the invoice's journal entry and its documents (same pattern
     * as JournalEntryController::delete).
     */
    private function currentUser()
    {
        return Auth::guard('admin-web')->user() ?? Auth::user();
    }

    private function reverseInvoiceJournal(Invoice $invoice): void
    {
        $journal = $invoice->journalEntry;
        if ($journal) {
            $journal->documents()->delete();
            $journal->delete();
        }
    }

    /**
     * Recompute the weighted-average purchase cost for the given items from
     * their remaining inbound stock lines. Deterministic and self-healing:
     * after an edit/delete the cost reflects only the stock that still exists.
     *
     * @param  array<int,int|null>  $itemIds
     */
    private function recomputeItemsCost(array $itemIds): void
    {
        $itemIds = array_values(array_unique(array_filter($itemIds)));

        foreach ($itemIds as $itemId) {
            $item = Item::find($itemId);
            if (! $item || ! $item->defaultUnit) {
                continue;
            }

            $totals = InvoiceDetail::where('item_id', $itemId)
                ->where('in_weight', '>', 0)
                ->selectRaw('COALESCE(SUM(in_weight * unit_cost), 0) as cost_sum, COALESCE(SUM(in_weight), 0) as weight_sum')
                ->first();

            $weightSum = (float) ($totals->weight_sum ?? 0);
            $averageCost = $weightSum > 0 ? ((float) $totals->cost_sum) / $weightSum : 0;

            $item->defaultUnit()->update([
                'average_cost_per_gram' => $averageCost,
                'current_cost_per_gram' => $averageCost,
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
