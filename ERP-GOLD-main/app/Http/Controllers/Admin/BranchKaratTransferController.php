<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\BranchKaratTransfer;
use App\Models\BranchKaratTransferLine;
use App\Models\FinancialYear;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BranchKaratTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.branch_karat_transfers.show,admin-web')
            ->only(['index', 'show', 'report', 'reportSearch']);
        $this->middleware('permission:employee.branch_karat_transfers.add,admin-web')
            ->only(['create', 'store']);
        $this->middleware('permission:employee.branch_karat_transfers.delete,admin-web')
            ->only(['destroy']);
    }

    public function index()
    {
        $transfers = BranchKaratTransfer::query()
            ->with(['fromBranch', 'toBranch', 'user', 'goldCaratType'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.branch_karat_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $carats = GoldCarat::orderBy('id')->get();
        $caratTypes = GoldCaratType::orderBy('id')->get();
        $accounts = Account::whereDoesntHave('childrens')->orderBy('code')->get();
        $billNumber = BranchKaratTransfer::nextBillNumber();
        $defaultPrice = (float) (GoldPrice::latestSnapshot()?->ounce_21_price ?? 0);

        return view('admin.branch_karat_transfers.create', compact(
            'branches', 'carats', 'caratTypes', 'accounts', 'billNumber', 'defaultPrice'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_date' => 'required|date',
            'from_branch_id' => 'required|exists:branches,id|different:to_branch_id',
            'to_branch_id' => 'required|exists:branches,id',
            'gold_carat_type_id' => 'required|exists:gold_carat_types,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.from_carat_id' => 'required|exists:gold_carats,id',
            'lines.*.to_carat_id' => 'required|exists:gold_carats,id',
            'lines.*.from_weight' => 'required|numeric|gt:0',
            'lines.*.to_weight' => 'required|numeric|gt:0',
            'lines.*.unit_cost' => 'required|numeric|min:0',
            'lines.*.line_notes' => 'nullable|string|max:255',
        ], [
            'from_branch_id.different' => 'لا يمكن التحويل لنفس الفرع.',
            'lines.required' => 'يجب إضافة سطر واحد على الأقل.',
            'account_id.required' => 'يجب تحديد حساب الوسيط (التسوية) للقيد المحاسبي.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $fromBranch = Branch::with('accountSetting', 'warehouses')->findOrFail($request->input('from_branch_id'));
        $toBranch = Branch::with('accountSetting', 'warehouses')->findOrFail($request->input('to_branch_id'));
        $caratType = GoldCaratType::findOrFail($request->input('gold_carat_type_id'));

        $stockAccountField = 'stock_account_' . $caratType->key;
        $fromStockAccountId = $fromBranch->accountSetting?->{$stockAccountField};
        $toStockAccountId = $toBranch->accountSetting?->{$stockAccountField};

        if (! $fromStockAccountId || ! $toStockAccountId) {
            return response()->json([
                'status' => false,
                'errors' => ['لا يوجد حساب مخزون لنوع العيار المحدد في إعدادات أحد الفرعين.'],
            ], 422);
        }

        $financialYear = FinancialYear::where('is_active', true)->first();
        if (! $financialYear) {
            return response()->json([
                'status' => false,
                'errors' => ['لا توجد سنة مالية نشطة.'],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $billDate = Carbon::parse($request->input('bill_date'));
            $totalFrom = 0.0;
            $totalTo = 0.0;
            $totalValue = 0.0;

            $linePayload = collect($request->input('lines'))->map(function ($line) use (&$totalFrom, &$totalTo, &$totalValue) {
                $fromWeight = (float) $line['from_weight'];
                $toWeight = (float) $line['to_weight'];
                $unitCost = (float) $line['unit_cost'];
                $lineValue = round($fromWeight * $unitCost, 2);

                $totalFrom += $fromWeight;
                $totalTo += $toWeight;
                $totalValue += $lineValue;

                return [
                    'from_carat_id' => (int) $line['from_carat_id'],
                    'to_carat_id' => (int) $line['to_carat_id'],
                    'from_weight' => $fromWeight,
                    'to_weight' => $toWeight,
                    'unit_cost' => $unitCost,
                    'line_value' => $lineValue,
                    'line_notes' => $line['line_notes'] ?? null,
                ];
            })->all();

            $transfer = BranchKaratTransfer::create([
                'bill_number' => BranchKaratTransfer::nextBillNumber(),
                'bill_date' => $billDate->format('Y-m-d'),
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $toBranch->id,
                'gold_carat_type_id' => $caratType->id,
                'account_id' => $request->input('account_id'),
                'user_id' => Auth::guard('admin-web')->id(),
                'notes' => $request->input('notes'),
                'lines_count' => count($linePayload),
                'total_from_weight' => $totalFrom,
                'total_to_weight' => $totalTo,
                'total_value' => $totalValue,
            ]);

            $outInvoice = $this->createSideInvoice(
                branch: $fromBranch,
                type: 'branch_karat_transfer_out',
                billDate: $billDate,
                accountId: (int) $request->input('account_id'),
                notes: $request->input('notes'),
                financialYearId: $financialYear->id,
                totalValue: $totalValue,
                billNumber: $transfer->bill_number,
            );

            $inInvoice = $this->createSideInvoice(
                branch: $toBranch,
                type: 'branch_karat_transfer_in',
                billDate: $billDate,
                accountId: (int) $request->input('account_id'),
                notes: $request->input('notes'),
                financialYearId: $financialYear->id,
                totalValue: $totalValue,
                billNumber: $transfer->bill_number,
                parentInvoiceId: $outInvoice->id,
            );

            foreach ($linePayload as $line) {
                $transfer->lines()->create($line);

                $outInvoice->details()->create([
                    'warehouse_id' => $fromBranch->warehouses->first()?->id,
                    'item_id' => null,
                    'gold_carat_id' => $line['from_carat_id'],
                    'gold_carat_type_id' => $caratType->id,
                    'date' => $billDate->format('Y-m-d'),
                    'in_quantity' => 0,
                    'out_quantity' => 0,
                    'in_weight' => 0,
                    'out_weight' => $line['from_weight'],
                    'unit_cost' => $line['unit_cost'],
                    'unit_price' => $line['unit_cost'],
                    'line_total' => $line['line_value'],
                    'net_total' => $line['line_value'],
                ]);

                $inInvoice->details()->create([
                    'warehouse_id' => $toBranch->warehouses->first()?->id,
                    'item_id' => null,
                    'gold_carat_id' => $line['to_carat_id'],
                    'gold_carat_type_id' => $caratType->id,
                    'date' => $billDate->format('Y-m-d'),
                    'in_quantity' => 0,
                    'out_quantity' => 0,
                    'in_weight' => $line['to_weight'],
                    'out_weight' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'unit_price' => $line['unit_cost'],
                    'line_total' => $line['line_value'],
                    'net_total' => $line['line_value'],
                ]);
            }

            if ($totalValue > 0) {
                JournalEntriesService::invoiceGenerateJournalEntries($outInvoice, [
                    [
                        'account_id' => (int) $request->input('account_id'),
                        'debit' => $totalValue,
                        'credit' => 0,
                        'document_date' => $billDate->format('Y-m-d'),
                    ],
                    [
                        'account_id' => (int) $fromStockAccountId,
                        'debit' => 0,
                        'credit' => $totalValue,
                        'document_date' => $billDate->format('Y-m-d'),
                    ],
                ]);

                JournalEntriesService::invoiceGenerateJournalEntries($inInvoice, [
                    [
                        'account_id' => (int) $toStockAccountId,
                        'debit' => $totalValue,
                        'credit' => 0,
                        'document_date' => $billDate->format('Y-m-d'),
                    ],
                    [
                        'account_id' => (int) $request->input('account_id'),
                        'debit' => 0,
                        'credit' => $totalValue,
                        'document_date' => $billDate->format('Y-m-d'),
                    ],
                ]);
            }

            $transfer->update([
                'out_invoice_id' => $outInvoice->id,
                'in_invoice_id' => $inInvoice->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('main.saved'),
                'redirect' => route('branch_karat_transfers.show', $transfer->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $transfer = BranchKaratTransfer::with([
            'fromBranch',
            'toBranch',
            'user',
            'goldCaratType',
            'account',
            'lines.fromCarat',
            'lines.toCarat',
            'outInvoice.journalEntry.documents.account',
            'inInvoice.journalEntry.documents.account',
        ])->findOrFail($id);

        return view('admin.branch_karat_transfers.show', compact('transfer'));
    }

    public function destroy($id)
    {
        $transfer = BranchKaratTransfer::findOrFail($id);

        DB::transaction(function () use ($transfer) {
            foreach ([$transfer->outInvoice, $transfer->inInvoice] as $invoice) {
                if (! $invoice) {
                    continue;
                }
                $invoice->journalEntry?->documents()?->delete();
                $invoice->journalEntry?->delete();
                $invoice->details()->delete();
                $invoice->delete();
            }
            $transfer->delete();
        });

        return redirect()->route('branch_karat_transfers.index')->with('success', __('main.deleted'));
    }

    public function report(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $carats = GoldCarat::orderBy('id')->get();

        $filters = [
            'date_from' => $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->input('date_to', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'from_branch_id' => $request->input('from_branch_id'),
            'to_branch_id' => $request->input('to_branch_id'),
            'from_carat_id' => $request->input('from_carat_id'),
            'to_carat_id' => $request->input('to_carat_id'),
        ];

        $lines = collect();

        if ($request->isMethod('post') || $request->boolean('apply')) {
            $query = BranchKaratTransferLine::query()
                ->with(['transfer.fromBranch', 'transfer.toBranch', 'transfer.user', 'fromCarat', 'toCarat'])
                ->whereHas('transfer', function ($q) use ($filters) {
                    $q->whereBetween('bill_date', [$filters['date_from'], $filters['date_to']]);

                    if (! empty($filters['from_branch_id'])) {
                        $q->where('from_branch_id', $filters['from_branch_id']);
                    }
                    if (! empty($filters['to_branch_id'])) {
                        $q->where('to_branch_id', $filters['to_branch_id']);
                    }
                });

            if (! empty($filters['from_carat_id'])) {
                $query->where('from_carat_id', $filters['from_carat_id']);
            }
            if (! empty($filters['to_carat_id'])) {
                $query->where('to_carat_id', $filters['to_carat_id']);
            }

            $lines = $query->orderByDesc('id')->get();
        }

        return view('admin.branch_karat_transfers.report', compact('branches', 'carats', 'filters', 'lines'));
    }

    private function createSideInvoice(
        Branch $branch,
        string $type,
        Carbon $billDate,
        int $accountId,
        ?string $notes,
        int $financialYearId,
        float $totalValue,
        string $billNumber,
        ?int $parentInvoiceId = null,
    ): Invoice {
        return Invoice::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $branch->warehouses->first()?->id,
            'customer_id' => null,
            'parent_id' => $parentInvoiceId,
            'financial_year' => $financialYearId,
            'type' => $type,
            'account_id' => $accountId,
            'bill_number' => $billNumber,
            'notes' => $notes ?? '',
            'date' => $billDate->format('Y-m-d'),
            'time' => $billDate->format('H:i:s'),
            'lines_total' => $totalValue,
            'discount_total' => 0,
            'lines_total_after_discount' => $totalValue,
            'taxes_total' => 0,
            'net_total' => $totalValue,
            'user_id' => Auth::guard('admin-web')->id(),
        ]);
    }
}
