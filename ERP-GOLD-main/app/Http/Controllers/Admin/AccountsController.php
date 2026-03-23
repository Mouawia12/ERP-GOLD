<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryDocument;
use App\Models\OpeningBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.accounts.show,admin-web')->only(['index', 'opening']);
        $this->middleware('permission:employee.accounts.add,admin-web')->only(['create', 'store', 'excepted_code', 'opening_store']);
        $this->middleware('permission:employee.accounts.edit,admin-web')->only(['edit', 'update']);
        $this->middleware('permission:employee.accounts.delete,admin-web')->only(['destroy']);
    }

    public function index()
    {
        $activeFinancialYear = FinancialYear::query()->where('is_active', true)->first();

        $accounts = Account::query()
            ->with('parent')
            ->withCount('childrens')
            ->orderBy('code')
            ->get();

        $roots = Account::query()
            ->with('childrensRecursive')
            ->withCount('childrens')
            ->whereNull('parent_account_id')
            ->orderBy('code')
            ->get();

        $stats = [
            'total_accounts' => $accounts->count(),
            'root_accounts' => $roots->count(),
            'leaf_accounts' => $accounts->where('childrens_count', 0)->count(),
            'max_level' => (int) ($accounts->max(fn (Account $account) => (int) $account->level) ?? 0),
            'accounts_with_opening_balance' => $activeFinancialYear
                ? OpeningBalance::query()
                    ->where('financial_year', $activeFinancialYear->id)
                    ->distinct('account_id')
                    ->count('account_id')
                : 0,
            'manual_journals_count' => JournalEntry::query()->whereNull('journalable_type')->count(),
            'transaction_journals_count' => JournalEntry::query()->whereNotNull('journalable_type')->count(),
            'journal_documents_count' => JournalEntryDocument::query()->count(),
        ];

        return view('admin.accounts.index', compact('accounts', 'roots', 'activeFinancialYear', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accounts = Account::all();
        return view('admin.accounts.form', compact('accounts'));
    }

    public function excepted_code(Request $request)
    {
        $account = Account::where('id', $request->parent_id)->first();
        $countSiblingAccounts = Account::where('parent_account_id', $account->id ?? null)->count();

        $level = $account ? intval($account->level) + 1 : 1;

        $expectedNum = $countSiblingAccounts + 1;
        $expectedCode = (new Account())->codePrefix($expectedNum, $level);
        $code = $account?->code . $expectedCode;
        return response()->json(['code' => $code]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAccountsTreeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:accounts',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'accounts_type' => 'required|in:' . implode(',', config('settings.accounts_types')),
            'transfers_side' => 'required|in:' . implode(',', config('settings.transfers_sides')),
        ]);

        try {
            DB::beginTransaction();
            Account::create([
                'name' => ['ar' => $request->name, 'en' => $request->name],
                'parent_account_id' => $request->parent_account_id ?? null,
                'account_type' => $request->accounts_type,
                'transfer_side' => $request->transfers_side,
            ]);

            DB::commit();
            return redirect()->route('accounts.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AccountsTree  $accountsTree
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $accounts = Account::all();
        $account = Account::find($id);

        return view('admin.accounts.form', compact('accounts', 'account'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAccountsTreeRequest  $request
     * @param  \App\Models\AccountsTree  $accountsTree
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|unique:accounts',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'accounts_type' => 'required|in:' . implode(',', config('settings.accounts_types')),
            'transfers_side' => 'required|in:' . implode(',', config('settings.transfers_sides')),
        ]);

        try {
            DB::beginTransaction();
            $account = Account::find($id);
            $account->update([
                'name' => ['ar' => $request->name, 'en' => $request->name],
                'parent_account_id' => $request->parent_account_id ?? null,
                'account_type' => $request->accounts_type,
                'transfer_side' => $request->transfers_side,
            ]);

            DB::commit();
            return redirect()->route('accounts.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function destroy($id)
    {
        $account = Account::query()
            ->withCount('childrens')
            ->findOrFail($id);

        if ($account->childrens_count > 0) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'لا يمكن حذف حساب يحتوي على حسابات فرعية.');
        }

        if (OpeningBalance::query()->where('account_id', $account->id)->exists()) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'لا يمكن حذف حساب عليه رصيد افتتاحي.');
        }

        if (JournalEntryDocument::query()->where('account_id', $account->id)->exists()) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'لا يمكن حذف حساب مرتبط بقيود يومية.');
        }

        try {
            $account->delete();

            return redirect()
                ->route('accounts.index')
                ->with('success', __('main.deleted'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('accounts.index')
                ->with('error', $th->getMessage());
        }
    }

    public function search(Request $request)
    {
        $accounts = Account::where(function ($query) use ($request) {
            $query
                ->where('code', 'like', '%' . $request->search . '%')
                ->orWhere('name', 'like', '%' . $request->search . '%');
        })->whereDoesntHave('childrens')->get();
        return response()->json($accounts);
    }

    public function opening()
    {
        $openingBalances = OpeningBalance::where('financial_year', FinancialYear::where('is_active', true)->first()->id)->get();
        $openingBalances = collect($openingBalances)->map(function ($openingBalance) {
            return [
                'id' => $openingBalance->account_id,
                'code' => $openingBalance->account->code,
                'name' => $openingBalance->account->name,
                'debit' => $openingBalance->debit,
                'credit' => $openingBalance->credit,
            ];
        });
        return view('admin.accounts.opening', compact('openingBalances'));
    }

    public function opening_store(Request $request)
    {
        if ($request->isMethod('GET')) {
            abort(403);
        }
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|array'
        ],
            [
                'account_id.required' => __('validations.account_id_required'),
                'account_id.array' => __('validations.account_id_array'),
            ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        $debits = array_sum($request->debit ?? []);
        $credits = array_sum($request->credit ?? []);
        if (floatval($debits) != floatval($credits)) {
            return response()->json([
                'status' => false,
                'errors' => __('validations.debits_credits_not_equal')
            ], 422);
        }

        $financialYear = FinancialYear::where('is_active', true)->first();
        try {
            DB::beginTransaction();
            foreach ($request->account_id as $key => $value) {
                $financialYear->openingBalances()->updateOrCreate([
                    'account_id' => $value,
                ], [
                    'debit' => $request->debit[$key],
                    'credit' => $request->credit[$key],
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => __('main.created')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
