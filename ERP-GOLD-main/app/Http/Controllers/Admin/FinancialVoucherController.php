<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\FinancialYear;
use App\Services\JournalEntriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinancialVoucherController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:employee.branches.show', ['only' => ['index']]);
        // $this->middleware('permission:employee.branches.add', ['only' => ['create', 'store']]);
        // $this->middleware('permission:employee.branches.edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:employee.branches.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request, $type)
    {
        $vouchers = FinancialVoucher::where('type', $type)->orderBy('id', 'desc')->get();
        $branches = Branch::all();
        $accounts = Account::all();
        return view('admin.financial_vouchers.index', compact('vouchers', 'type', 'branches', 'accounts'));
    }

    public function store(Request $request, $type)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'branch_id' => 'required',
            'from_account_id' => 'required',
            'to_account_id' => 'required',
            'total_amount' => 'required',
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
        try {
            DB::beginTransaction();
            $voucher = FinancialVoucher::create([
                'type' => $type,
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'date' => $request->date,
                'branch_id' => $request->branch_id,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'total_amount' => $request->total_amount,
                'description' => $request->description,
            ]);
            JournalEntriesService::invoiceGenerateJournalEntries($voucher, $this->financial_voucher_prepare_journal_entry_details($voucher));
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم اضافة حركة مالية بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
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
