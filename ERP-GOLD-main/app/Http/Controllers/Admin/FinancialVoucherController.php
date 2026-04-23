<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Models\Shift;
use App\Services\Branches\BranchAccessService;
use App\Services\JournalEntriesService;
use App\Services\Shifts\SalesShiftModeService;
use App\Services\Shifts\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinancialVoucherController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccessService,
        private readonly SalesShiftModeService $salesShiftModeService,
        private readonly ShiftService $shiftService,
    )
    {
        // $this->middleware('permission:employee.branches.show', ['only' => ['index']]);
        // $this->middleware('permission:employee.branches.add', ['only' => ['create', 'store']]);
        // $this->middleware('permission:employee.branches.edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:employee.branches.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request, $type)
    {
        $voucherQuery = FinancialVoucher::with(['fromAccount', 'toAccount', 'bankAccount'])
            ->where('type', $type);
        $this->branchAccessService->scopeToAccessibleBranch($voucherQuery, $request->user('admin-web'));

        $vouchers = $voucherQuery->orderBy('id', 'desc')->get();
        $branches = $this->branchAccessService->visibleBranches($request->user('admin-web'));
        $accounts = Account::all();
        $bankAccountsQuery = BankAccount::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('account_name');
        $this->branchAccessService->scopeToAccessibleBranch($bankAccountsQuery, $request->user('admin-web'));
        $bankAccounts = $bankAccountsQuery->get();
        $bankAccountOptions = $bankAccounts->map(function (BankAccount $bankAccount) {
            return [
                'id' => $bankAccount->id,
                'branch_id' => $bankAccount->branch_id,
                'display_name' => $bankAccount->display_name,
                'supports_credit_card' => (bool) $bankAccount->supports_credit_card,
                'supports_bank_transfer' => (bool) $bankAccount->supports_bank_transfer,
            ];
        })->values();

        return view('admin.financial_vouchers.index', compact('vouchers', 'type', 'branches', 'accounts', 'bankAccounts', 'bankAccountOptions'));
    }

    public function store(Request $request, $type)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'branch_id' => 'required',
            'from_account_id' => 'required',
            'to_account_id' => 'required',
            'total_amount' => 'required',
            'payment_method' => 'nullable|in:cash,credit_card,bank_transfer',
            'bank_account_id' => 'nullable|integer',
            'reference_no' => 'nullable|string|max:191',
            'description' => 'nullable',
        ], [
            'date.required' => __('validations.date_required'),
            'branch_id.required' => __('validations.branch_id_required'),
            'from_account_id.required' => __('validations.from_account_id_required'),
            'to_account_id.required' => __('validations.to_account_id_required'),
            'total_amount.required' => __('validations.total_amount_required'),
            'description.required' => __('validations.description_required'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $this->branchAccessService->enforceBranchAccess($request->user('admin-web'), (int) $request->branch_id);

        try {
            $activeShift = $this->resolveVoucherShift($request, (int) $request->branch_id);
            $paymentMethod = $request->input('payment_method', 'cash');
            $bankAccount = null;

            if ($paymentMethod !== 'cash') {
                if (! $request->filled('bank_account_id')) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['يجب اختيار حساب بنكي فعلي عند استخدام تحصيل أو سداد غير نقدي.'],
                    ]);
                }

                $bankAccount = BankAccount::query()
                    ->active()
                    ->where('branch_id', $request->branch_id)
                    ->whereKey($request->bank_account_id)
                    ->first();

                if (! $bankAccount) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['الحساب البنكي المحدد غير صالح لهذا الفرع.'],
                    ]);
                }

                if ($paymentMethod === 'credit_card' && ! $bankAccount->supports_credit_card) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['الحساب البنكي المحدد لا يدعم الشبكة / البطاقة.'],
                    ]);
                }

                if ($paymentMethod === 'bank_transfer' && ! $bankAccount->supports_bank_transfer) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['الحساب البنكي المحدد لا يدعم التحويل البنكي.'],
                    ]);
                }

                if (! in_array((int) $bankAccount->ledger_account_id, [(int) $request->from_account_id, (int) $request->to_account_id], true)) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => ['الحساب البنكي المحدد يجب أن يكون أحد طرفي السند المحاسبي.'],
                    ]);
                }
            }

            DB::beginTransaction();
            $voucher = FinancialVoucher::create([
                'type' => $type,
                'payment_method' => $paymentMethod,
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'date' => $request->date,
                'branch_id' => $request->branch_id,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'bank_account_id' => $bankAccount?->id,
                'reference_no' => $request->filled('reference_no') ? trim((string) $request->reference_no) : null,
                'total_amount' => $request->total_amount,
                'description' => $request->description,
                'shift_id' => $activeShift?->id,
            ]);
            JournalEntriesService::invoiceGenerateJournalEntries($voucher, $this->financial_voucher_prepare_journal_entry_details($voucher));
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم اضافة حركة مالية بنجاح',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => collect($e->errors())->flatten()->values()->all(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveVoucherShift(Request $request, int $branchId): ?Shift
    {
        if (! $this->salesShiftModeService->requiresShift()) {
            return null;
        }

        return $this->shiftService->requireActiveShift($request->user('admin-web'), $branchId);
    }

    private function financial_voucher_prepare_journal_entry_details($voucher)
    {
        $journal_entry_details = [];
        $journal_entry_details[] = [
            'account_id' => $voucher->from_account_id,
            'credit' => $voucher->total_amount,
            'debit' => 0,
            'document_date' => $voucher->date,
        ];
        $journal_entry_details[] = [
            'account_id' => $voucher->to_account_id,
            'debit' => $voucher->total_amount,
            'credit' => 0,
            'document_date' => $voucher->date,
        ];
        return $journal_entry_details;
    }
}
