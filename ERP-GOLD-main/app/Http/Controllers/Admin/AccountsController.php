<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\OpeningBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DataTables;

class AccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::where('level', '>', 0)->orderBy('code')->get();
        $roots = Account::where('parent_account_id', null)->orderBy('id')->get();

        return view('admin.accounts.index', compact('accounts', 'roots'));
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
