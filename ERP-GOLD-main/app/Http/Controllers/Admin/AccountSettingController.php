<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = AccountSetting::all();
        $branchesById = Branch::query()->get()->keyBy('id');
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
        $branchs = Branch::all();

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
        AccountSetting::create($request->all());
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
        $setting = AccountSetting::find($id);
        $branchs = Branch::all();

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
        $setting = AccountSetting::find($id);
        if ($setting) {
            $setting->update($request->all());
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
}
