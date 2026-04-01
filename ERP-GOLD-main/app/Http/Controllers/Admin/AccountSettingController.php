<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use Illuminate\Http\Request;

class AccountSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = AccountSetting::query()->get();
        $branchesById = $this->branchesQuery()->get()->keyBy('id');
        $accountIds = $accounts->flatMap(function ($accountSetting) {
            return [
                $accountSetting->safe_account,
                $accountSetting->sales_account,
                $accountSetting->return_sales_account,
                $accountSetting->sales_discount_account,
                $accountSetting->sales_tax_account,
                $accountSetting->purchase_tax_account,
                $accountSetting->cost_account,
                $accountSetting->profit_account,
                $accountSetting->reverse_profit_account,
                $accountSetting->bank_account,
                $accountSetting->made_account,
                $accountSetting->clients_account,
                $accountSetting->suppliers_account,
            ];
        })->filter()->unique()->values();
        $accountsById = Account::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        foreach ($accounts as $account) {
            $account->branch_name = $branchesById->get($account->branch_id)?->name ?? 'غير محدد';
            $account->safe_account_name = $accountsById->get($account->safe_account)?->name ?? 'غير محدد';
            $account->sales_account_name = $accountsById->get($account->sales_account)?->name ?? 'غير محدد';
            $account->return_sales_account_name = $accountsById->get($account->return_sales_account)?->name ?? 'غير محدد';
            $account->sales_discount_account_name = $accountsById->get($account->sales_discount_account)?->name ?? 'غير محدد';
            $account->sales_tax_account_name = $accountsById->get($account->sales_tax_account)?->name ?? 'غير محدد';
            $account->purchase_tax_account_name = $accountsById->get($account->purchase_tax_account)?->name ?? 'غير محدد';
            $account->cost_account_name = $accountsById->get($account->cost_account)?->name ?? 'غير محدد';
            $account->profit_account_name = $accountsById->get($account->profit_account)?->name ?? 'غير محدد';
            $account->reverse_profit_account_name = $accountsById->get($account->reverse_profit_account)?->name ?? 'غير محدد';
            $account->bank_account_name = $accountsById->get($account->bank_account)?->name ?? 'غير محدد';
            $account->made_account_name = $accountsById->get($account->made_account)?->name ?? 'غير محدد';
            $account->clients_account_name = $accountsById->get($account->clients_account)?->name ?? 'غير محدد';
            $account->suppliers_account_name = $accountsById->get($account->suppliers_account)?->name ?? 'غير محدد';
        }

        return view('admin.accounts.settings', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accounts = Account::query()->get();
        $branchs = $this->branchesQuery()->get();

        return view('admin.accounts.create_settings', compact('accounts', 'branchs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAccountSettingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        AccountSetting::create($this->validatedPayload($request));
        return redirect()->route('accounts.settings.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AccountSetting  $accountSetting
     * @return \Illuminate\Http\Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AccountSetting  $accountSetting
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $accounts = Account::query()->whereDoesntHave('childrens')->get();
        $setting = AccountSetting::query()->findOrFail($id);
        $branchs = $this->branchesQuery()->get();

        return view('admin.accounts.update_settings', compact('accounts', 'branchs', 'setting'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAccountSettingRequest  $request
     * @param  \App\Models\AccountSetting  $accountSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $setting = AccountSetting::query()->findOrFail($id);
        if ($setting) {
            $setting->update($this->validatedPayload($request));
            return redirect()->route('accounts.settings.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AccountSetting  $accountSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(AccountSetting $accountSetting)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $payload = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'safe_account' => 'nullable|integer',
            'bank_account' => 'nullable|integer',
            'sales_account' => 'nullable|integer',
            'return_sales_account' => 'nullable|integer',
            'stock_account_crafted' => 'nullable|integer',
            'stock_account_scrap' => 'nullable|integer',
            'stock_account_pure' => 'nullable|integer',
            'made_account' => 'nullable|integer',
            'cost_account_crafted' => 'nullable|integer',
            'cost_account_scrap' => 'nullable|integer',
            'cost_account_pure' => 'nullable|integer',
            'reverse_profit_account' => 'nullable|integer',
            'profit_account' => 'nullable|integer',
            'sales_tax_account' => 'nullable|integer',
            'purchase_tax_account' => 'nullable|integer',
            'sales_tax_excise_account' => 'nullable|integer',
            'supplier_default_account' => 'nullable|integer',
            'clients_account' => 'nullable|integer',
            'suppliers_account' => 'nullable|integer',
        ]);

        if (filled($payload['branch_id'] ?? null)) {
            abort_unless(
                $this->branchesQuery()->whereKey($payload['branch_id'])->exists(),
                403,
                'لا يمكنك اختيار فرع يخص مشتركًا آخر.'
            );
        }

        foreach ([
            'safe_account',
            'bank_account',
            'sales_account',
            'return_sales_account',
            'stock_account_crafted',
            'stock_account_scrap',
            'stock_account_pure',
            'made_account',
            'cost_account_crafted',
            'cost_account_scrap',
            'cost_account_pure',
            'reverse_profit_account',
            'profit_account',
            'sales_tax_account',
            'purchase_tax_account',
            'sales_tax_excise_account',
            'supplier_default_account',
            'clients_account',
            'suppliers_account',
        ] as $field) {
            if (filled($payload[$field] ?? null)) {
                abort_unless(
                    Account::query()->whereKey($payload[$field])->exists(),
                    403,
                    'لا يمكنك ربط حساب يخص مشتركًا آخر.'
                );
            }
        }

        return $payload;
    }

    private function branchesQuery()
    {
        $user = request()->user('admin-web');

        return Branch::query()->when(
            filled($user?->subscriber_id),
            fn ($query) => $query->where('subscriber_id', $user->subscriber_id)
        );
    }
}
