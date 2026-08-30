<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryDocument;
use App\Models\OpeningBalance;
use App\Services\Accounts\AccountDeletionGuard;
use App\Services\Accounts\AccountRenumberingService;
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
        $branches = $this->subscriberBranches();
        return view('admin.accounts.form', compact('accounts', 'branches'));
    }

    /**
     * فروع المشترك الحالي (لاختيارها عند ربط الحساب).
     */
    private function subscriberBranches()
    {
        $actor = request()->user('admin-web');

        return Branch::query()
            ->when(
                filled($actor?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $actor->subscriber_id)
            )
            ->orderBy('id')
            ->get();
    }

    /**
     * تنقية معرّفات الفروع المرسلة إلى ما يخص المشترك الحالي فقط.
     *
     * @param  mixed  $branchIds
     * @return array<int, int>
     */
    private function allowedBranchIds($branchIds): array
    {
        $ids = collect($branchIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $actor = request()->user('admin-web');

        return Branch::query()
            ->when(
                filled($actor?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $actor->subscriber_id)
            )
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function excepted_code(Request $request)
    {
        $parent = Account::where('id', $request->parent_id)->first();
        $parentId = $parent->id ?? null;

        // عند التعديل: إن بقي الحساب تحت نفس الأب فلا نقل ولا إعادة ترقيم،
        // فيُعرض كوده الحالي بدل كود «ابن جديد».
        $edited = $request->filled('account_id')
            ? Account::find($request->account_id)
            : null;

        if ($edited && (int) $edited->parent_account_id === (int) $parentId) {
            return response()->json(['code' => $edited->code]);
        }

        $countSiblingAccounts = Account::where('parent_account_id', $parentId)->count();

        $level = $parent ? intval($parent->level) + 1 : 1;

        $expectedNum = $countSiblingAccounts + 1;
        $expectedCode = (new Account())->codePrefix($expectedNum, $level);
        $code = $parent?->code . $expectedCode;
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
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        try {
            DB::beginTransaction();
            $account = Account::create([
                'name' => ['ar' => $request->name, 'en' => $request->name],
                'parent_account_id' => $request->parent_account_id ?? null,
                'account_type' => $request->accounts_type,
                'transfer_side' => $request->transfers_side,
            ]);

            $account->branches()->sync($this->allowedBranchIds($request->input('branch_ids', [])));

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
        $account = Account::with('branches')->find($id);
        $branches = $this->subscriberBranches();

        return view('admin.accounts.form', compact('accounts', 'account', 'branches'));
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
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        $account = Account::findOrFail($id);

        $previousParentId = $account->parent_account_id !== null
            ? (int) $account->parent_account_id
            : null;
        $newParentId = $request->parent_account_id !== null
            ? (int) $request->parent_account_id
            : null;

        $renumbering = app(AccountRenumberingService::class);

        if ($renumbering->wouldCreateCycle($account, $newParentId)) {
            return redirect()
                ->back()
                ->with('error', 'لا يمكن نقل الحساب تحت نفسه أو تحت أحد حساباته الفرعية.');
        }

        try {
            DB::beginTransaction();
            $account->update([
                'name' => ['ar' => $request->name, 'en' => $request->name],
                'parent_account_id' => $newParentId,
                'account_type' => $request->accounts_type,
                'transfer_side' => $request->transfers_side,
            ]);

            $account->branches()->sync($this->allowedBranchIds($request->input('branch_ids', [])));

            // نقل الحساب يغيّر موضعه في الشجرة، والكود والمستوى مشتقان من الموضع.
            if ($previousParentId !== $newParentId) {
                $renumbering->renumberAfterMove($account, $previousParentId);
            }

            DB::commit();

            $message = $previousParentId !== $newParentId
                ? 'تم تعديل الحساب وإعادة ترقيمه تلقائيًا إلى «' . $account->code . '».'
                : 'تم تعديل الحساب بنجاح.';

            return redirect()->route('accounts.index')->with('success', $message);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function destroy($id, AccountDeletionGuard $guard)
    {
        $account = Account::query()->findOrFail($id);

        $blockingReason = $guard->blockingReason($account);

        if ($blockingReason !== null) {
            return redirect()
                ->route('accounts.index')
                ->with('error', $blockingReason);
        }

        try {
            DB::transaction(function () use ($account) {
                $account->branches()->detach();
                $account->delete();
            });

            return redirect()
                ->route('accounts.index')
                ->with('success', 'تم حذف الحساب «' . $account->code . ' - ' . $account->name . '» بنجاح.');
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
