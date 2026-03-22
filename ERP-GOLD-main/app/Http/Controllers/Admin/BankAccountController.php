<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.system_settings.show', ['only' => ['index', 'create', 'edit']]);
        $this->middleware('permission:employee.system_settings.edit', ['only' => ['store', 'update']]);
    }

    public function index(): View
    {
        return view('admin.settings.bank_accounts.index', [
            'bankAccounts' => BankAccount::query()
                ->with(['branch', 'ledgerAccount'])
                ->orderBy('branch_id')
                ->orderByDesc('is_default')
                ->latest('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.bank_accounts.create', [
            'bankAccount' => new BankAccount(),
            'branches' => Branch::query()->orderBy('id')->get(),
            'accounts' => Account::query()->whereDoesntHave('childrens')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $bankAccount = BankAccount::create($this->validatedData($request));
        $this->synchronizeBranchDefaultBankAccount($bankAccount);

        return redirect()
            ->route('admin.system-settings.bank-accounts.index')
            ->with('success', 'تم إضافة الحساب البنكي بنجاح.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('admin.settings.bank_accounts.edit', [
            'bankAccount' => $bankAccount,
            'branches' => Branch::query()->orderBy('id')->get(),
            'accounts' => Account::query()->whereDoesntHave('childrens')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($this->validatedData($request));
        $this->synchronizeBranchDefaultBankAccount($bankAccount);

        return redirect()
            ->route('admin.system-settings.bank-accounts.index')
            ->with('success', 'تم تحديث الحساب البنكي بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ledger_account_id' => 'required|exists:accounts,id',
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'iban' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'terminal_name' => 'nullable|string|max:255',
            'device_code' => 'nullable|string|max:100',
            'supports_credit_card' => 'nullable|boolean',
            'supports_bank_transfer' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]) + [
            'supports_credit_card' => $request->boolean('supports_credit_card'),
            'supports_bank_transfer' => $request->boolean('supports_bank_transfer'),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function synchronizeBranchDefaultBankAccount(BankAccount $bankAccount): void
    {
        if ($bankAccount->is_default) {
            BankAccount::query()
                ->where('branch_id', $bankAccount->branch_id)
                ->where('id', '!=', $bankAccount->id)
                ->update(['is_default' => false]);
        }

        if (! $bankAccount->branch?->accountSetting) {
            return;
        }

        $branchDefault = BankAccount::query()
            ->where('branch_id', $bankAccount->branch_id)
            ->where('is_default', true)
            ->first();

        if ($branchDefault) {
            $bankAccount->branch->accountSetting->update([
                'bank_account' => $branchDefault->ledger_account_id,
            ]);
        }
    }
}
