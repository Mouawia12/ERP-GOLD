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
        foreach ($accounts as $account) {
            $account->branch_name = Branch::find($account->branch_id)->name;
            $account->safe_account_name = Account::find($account->safe_account)->name;
            $account->sales_account_name = Account::find($account->sales_account)->name;
            $account->return_sales_account_name = Account::find($account->return_sales_account)->name;
            $account->sales_tax_account_name = Account::find($account->sales_tax_account)->name;
            $account->purchase_tax_account_name = Account::find($account->purchase_tax_account)->name;
            $account->profit_account_name = Account::find($account->profit_account)->name;
            $account->reverse_profit_account_name = Account::find($account->reverse_profit_account)->name;
            $account->bank_account_name = Account::find($account->bank_account)->name;
            $account->made_account_name = Account::find($account->made_account)->name;
            $account->clients_account_name = Account::find($account->clients_account)->name;
            $account->suppliers_account_name = Account::find($account->suppliers_account)->name;
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
