<?php

namespace App\Services\Accounts;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use App\Models\Subscriber;

class SubscriberChartProvisioner
{
    /**
     * @return array<string, \App\Models\Account>
     */
    public function ensureProvisioned(Subscriber $subscriber): array
    {
        $existing = Account::query()
            ->withoutGlobalScopes()
            ->where('subscriber_id', $subscriber->id)
            ->get()
            ->keyBy('code');

        if ($existing->isNotEmpty()) {
            return $existing->all();
        }

        $created = [];

        foreach ($this->template() as $accountData) {
            $parentCode = $accountData['parent_code'] ?? null;
            $parentId = $parentCode ? ($created[$parentCode]->id ?? null) : null;

            $account = Account::query()->withoutGlobalScopes()->create([
                'subscriber_id' => $subscriber->id,
                'name' => ['ar' => $accountData['name_ar'], 'en' => $accountData['name_en']],
                'code' => $accountData['code'],
                'level' => (string) $accountData['level'],
                'parent_account_id' => $parentId,
                'account_type' => $accountData['account_type'],
                'transfer_side' => $accountData['transfer_side'],
            ]);

            $created[$accountData['code']] = $account;
        }

        return $created;
    }

    public function ensureBranchAccountSettings(Subscriber $subscriber, Branch $branch): AccountSetting
    {
        $accountsByCode = $this->ensureProvisioned($subscriber);

        return AccountSetting::query()
            ->withoutGlobalScopes()
            ->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'subscriber_id' => $subscriber->id,
                    'safe_account' => $accountsByCode['1101001']->id,
                    'bank_account' => $accountsByCode['1102001']->id,
                    'sales_account' => $accountsByCode['4101001']->id,
                    'return_sales_account' => $accountsByCode['4102001']->id,
                    'stock_account_crafted' => $accountsByCode['11040010001']->id,
                    'stock_account_scrap' => $accountsByCode['11040010002']->id,
                    'stock_account_pure' => $accountsByCode['11040010003']->id,
                    'made_account' => $accountsByCode['5201001']->id,
                    'cost_account_crafted' => $accountsByCode['5101']->id,
                    'cost_account_scrap' => $accountsByCode['5102']->id,
                    'cost_account_pure' => $accountsByCode['5103']->id,
                    'reverse_profit_account' => $accountsByCode['3101001']->id,
                    'profit_account' => $accountsByCode['3101']->id,
                    'sales_tax_account' => $accountsByCode['1105001']->id,
                    'purchase_tax_account' => $accountsByCode['2102001']->id,
                    'supplier_default_account' => $accountsByCode['2101001']->id,
                    'clients_account' => $accountsByCode['1103']->id,
                    'suppliers_account' => $accountsByCode['2101']->id,
                ]
            );
    }

    public function remapLedgerAccountForSubscriber(Subscriber $subscriber, ?int $legacyAccountId): ?int
    {
        if (! $legacyAccountId) {
            return null;
        }

        $legacyAccount = Account::query()
            ->withoutGlobalScopes()
            ->find($legacyAccountId);

        if (! $legacyAccount?->code) {
            return null;
        }

        return Account::query()
            ->withoutGlobalScopes()
            ->where('subscriber_id', $subscriber->id)
            ->where('code', $legacyAccount->code)
            ->value('id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function template(): array
    {
        return [
            ['code' => '1', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'level' => 1, 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'level' => 2, 'parent_code' => '1', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1101', 'name_ar' => 'نقدية بالصناديق', 'name_en' => 'Cash in Safes', 'level' => 3, 'parent_code' => '11', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1101001', 'name_ar' => 'الصندوق الرئيسي', 'name_en' => 'Main Safe', 'level' => 4, 'parent_code' => '1101', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1102', 'name_ar' => 'نقدية بالبنوك', 'name_en' => 'Cash at Banks', 'level' => 3, 'parent_code' => '11', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1102001', 'name_ar' => 'البنك الرئيسي', 'name_en' => 'Main Bank', 'level' => 4, 'parent_code' => '1102', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1103', 'name_ar' => 'العملاء', 'name_en' => 'Customers', 'level' => 3, 'parent_code' => '11', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1103001', 'name_ar' => 'عميل نقدي افتراضي', 'name_en' => 'Default Cash Customer', 'level' => 4, 'parent_code' => '1103', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1104', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'level' => 3, 'parent_code' => '11', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1104001', 'name_ar' => 'المخزون الرئيسي', 'name_en' => 'Main Inventory', 'level' => 4, 'parent_code' => '1104', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11040010001', 'name_ar' => 'مخزون ذهب مشغول', 'name_en' => 'Crafted Gold Stock', 'level' => 5, 'parent_code' => '1104001', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11040010002', 'name_ar' => 'مخزون ذهب كسر', 'name_en' => 'Scrap Gold Stock', 'level' => 5, 'parent_code' => '1104001', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11040010003', 'name_ar' => 'مخزون ذهب صافي', 'name_en' => 'Pure Gold Stock', 'level' => 5, 'parent_code' => '1104001', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1105', 'name_ar' => 'أرصدة مدينة أخرى', 'name_en' => 'Other Debit Balances', 'level' => 3, 'parent_code' => '11', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1105001', 'name_ar' => 'ضريبة المبيعات', 'name_en' => 'Sales Tax', 'level' => 4, 'parent_code' => '1105', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '2', 'name_ar' => 'الخصوم (الالتزامات)', 'name_en' => 'Liabilities', 'level' => 1, 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '21', 'name_ar' => 'الخصوم المتداولة (الالتزامات المتداولة)', 'name_en' => 'Current Liabilities', 'level' => 2, 'parent_code' => '2', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2101', 'name_ar' => 'الموردين', 'name_en' => 'Suppliers', 'level' => 3, 'parent_code' => '21', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2101001', 'name_ar' => 'مورد نقدي افتراضي', 'name_en' => 'Default Cash Supplier', 'level' => 4, 'parent_code' => '2101', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2102', 'name_ar' => 'أرصدة دائنة أخرى', 'name_en' => 'Other Credit Balances', 'level' => 3, 'parent_code' => '21', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2102001', 'name_ar' => 'ضريبة المشتريات', 'name_en' => 'Purchase Tax', 'level' => 4, 'parent_code' => '2102', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '3', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'level' => 1, 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '31', 'name_ar' => 'حساب الربح أو الخسارة', 'name_en' => 'Profit and Loss Account', 'level' => 2, 'parent_code' => '3', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '3101', 'name_ar' => 'صافي الربح', 'name_en' => 'Net Profit', 'level' => 3, 'parent_code' => '31', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '3101001', 'name_ar' => 'معادلة الربح في المبيعات', 'name_en' => 'Sales Profit Offset', 'level' => 4, 'parent_code' => '3101', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '4', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenues', 'level' => 1, 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '41', 'name_ar' => 'صافي المبيعات', 'name_en' => 'Net Sales', 'level' => 2, 'parent_code' => '4', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4101', 'name_ar' => 'إجمالي المبيعات', 'name_en' => 'Gross Sales', 'level' => 3, 'parent_code' => '41', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4101001', 'name_ar' => 'إجمالي المبيعات - افتراضي', 'name_en' => 'Default Gross Sales', 'level' => 4, 'parent_code' => '4101', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4102', 'name_ar' => 'مردودات المبيعات', 'name_en' => 'Sales Returns', 'level' => 3, 'parent_code' => '41', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4102001', 'name_ar' => 'مردودات المبيعات - افتراضي', 'name_en' => 'Default Sales Returns', 'level' => 4, 'parent_code' => '4102', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '5', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'level' => 1, 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '51', 'name_ar' => 'تكلفة المبيعات', 'name_en' => 'Cost of Sales', 'level' => 2, 'parent_code' => '5', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5101', 'name_ar' => 'تكلفة مشغولات', 'name_en' => 'Crafted Cost', 'level' => 3, 'parent_code' => '51', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5102', 'name_ar' => 'تكلفة سكراب', 'name_en' => 'Scrap Cost', 'level' => 3, 'parent_code' => '51', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5103', 'name_ar' => 'تكلفة ذهب صافي', 'name_en' => 'Pure Gold Cost', 'level' => 3, 'parent_code' => '51', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '52', 'name_ar' => 'مصروفات عمومية وإدارية', 'name_en' => 'General and Administrative Expenses', 'level' => 2, 'parent_code' => '5', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5201', 'name_ar' => 'أجور تصنيع', 'name_en' => 'Manufacturing Wages', 'level' => 3, 'parent_code' => '52', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5201001', 'name_ar' => 'أجور تصنيع ذهب مشغول', 'name_en' => 'Crafted Manufacturing Wages', 'level' => 4, 'parent_code' => '5201', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
        ];
    }
}
