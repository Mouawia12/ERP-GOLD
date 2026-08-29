<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryDocument;
use App\Models\OpeningBalance;
use App\Services\Accounts\AccountCodeService;
use App\Services\Accounts\AccountReferenceInspector;
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
        $accounts = Account::query()->orderBy('code')->get();
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

    /**
     * الكود المتوقّع لحساب تحت أب معيّن — يُستدعى من الشاشة عند اختيار الأب.
     * عند التعديل يُستبعد الحساب نفسه من عدّ الإخوة حتى لا يقفز الرقم بلا داعٍ.
     */
    public function excepted_code(Request $request, AccountCodeService $codes)
    {
        $editedId = $request->filled('account_id') ? (int) $request->account_id : null;
        $edited = $editedId ? Account::find($editedId) : null;

        $parent = $request->filled('parent_id') ? Account::find($request->parent_id) : null;

        $subscriberId = $edited?->subscriber_id
            ?? $parent?->subscriber_id
            ?? request()->user('admin-web')?->subscriber_id;

        return response()->json([
            'code' => $codes->nextCode($parent, $subscriberId, $editedId),
        ]);
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
        $account = Account::with('branches')->findOrFail($id);

        // الحساب نفسه وفروعه مستبعدون من قائمة الآباء حتى لا تُبنى دورة في الشجرة.
        $accounts = Account::query()
            ->whereNotIn('id', $account->childrensIds)
            ->orderBy('code')
            ->get();
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
    public function update(Request $request, $id, AccountCodeService $codes)
    {
        $validated = $request->validate([
            'name' => 'required',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'accounts_type' => 'required|in:' . implode(',', config('settings.accounts_types')),
            'transfers_side' => 'required|in:' . implode(',', config('settings.transfers_sides')),
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        $account = Account::findOrFail($id);
        $currentParentId = $account->parent_account_id === null ? null : (int) $account->parent_account_id;

        // الحقل قد يصل غير مُرسل (قائمة معطّلة في الشاشة) — عندها يبقى الأب كما هو
        // بدل أن يتحوّل الحساب إلى جذر يتيم.
        $newParentId = $request->has('parent_account_id')
            ? ($request->filled('parent_account_id') ? (int) $request->parent_account_id : null)
            : $currentParentId;

        if ($newParentId !== null && in_array($newParentId, $account->childrensIds, true)) {
            return redirect()
                ->back()
                ->with('error', 'لا يمكن نقل الحساب ليصبح تابعًا لنفسه أو لأحد حساباته الفرعية.');
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

            // تغيير الأب يعني تغيير موضع الحساب في الشجرة، فيُصرف له كود جديد تحت
            // الأب الجديد وتُعاد ترقيم كل حساباته الفرعية تبعًا له.
            $message = __('main.updated');
            $moved = $newParentId !== $currentParentId;

            // يُعاد الترقيم عند النقل، وكذلك عند الحفظ العادي إذا كان الكود لا
            // يطابق موضع الحساب — حساب نُقل قبل هذا الإصلاح ظلّ حاملًا كود
            // عيلته القديمة، فأول حفظ يصحّحه.
            if ($moved || ! $codes->isCodeConsistent($account)) {
                $oldCode = $account->code;
                $recoded = $codes->recodeSubtree($account);
                $message = ($moved ? 'تم تعديل الحساب ونقله: الكود ' : 'تم تعديل الحساب وتصحيح كوده: ')
                    . $oldCode . ' ← ' . $account->code
                    . ($recoded > 1 ? ' (وأُعيد ترقيم ' . ($recoded - 1) . ' حسابًا فرعيًا)' : '');
            }

            DB::commit();
            return redirect()->route('accounts.index')->with('success', $message);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function destroy($id, AccountReferenceInspector $references)
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

        // ارتباطات أخرى (الربط المحاسبي للفروع، عميل، سند، حساب بنكي…) كانت
        // تصطدم بقيد قاعدة البيانات وتظهر رسالة SQL غير مفهومة.
        $blocking = $references->blockingMessage((int) $account->id);

        if ($blocking !== null) {
            return redirect()
                ->route('accounts.index')
                ->with('error', $blocking);
        }

        try {
            DB::transaction(function () use ($account) {
                $account->branches()->detach();
                $account->delete();
            });

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
