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
                    'safe_account' => $accountsByCode['110101']->id,
                    'bank_account' => $accountsByCode['110201']->id,
                    'sales_account' => $accountsByCode['410101']->id,
                    'return_sales_account' => $accountsByCode['410201']->id,
                    'stock_account_crafted' => $accountsByCode['11080101']->id,
                    'stock_account_scrap' => $accountsByCode['11080102']->id,
                    'stock_account_pure' => $accountsByCode['11080103']->id,
                    'made_account' => $accountsByCode['530501']->id,
                    'cost_account_crafted' => $accountsByCode['520101']->id,
                    'cost_account_scrap' => $accountsByCode['520102']->id,
                    'cost_account_pure' => $accountsByCode['520103']->id,
                    'reverse_profit_account' => $accountsByCode['330102']->id,
                    'profit_account' => $accountsByCode['3301']->id,
                    'sales_tax_account' => $accountsByCode['111203']->id,
                    'purchase_tax_account' => $accountsByCode['210303']->id,
                    'supplier_default_account' => $accountsByCode['210101']->id,
                    'clients_account' => $accountsByCode['1107']->id,
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
            ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'level' => 1, 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'level' => 2, 'parent_code' => '1000', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1101', 'name_ar' => 'نقدية بالصناديق', 'name_en' => 'Cash in Safes', 'level' => 3, 'parent_code' => '1100', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '110101', 'name_ar' => 'الصندوق الرئيسي', 'name_en' => 'Main Safe', 'level' => 4, 'parent_code' => '1101', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1102', 'name_ar' => 'نقدية بالبنوك', 'name_en' => 'Cash at Banks', 'level' => 3, 'parent_code' => '1100', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '110201', 'name_ar' => 'البنك الرئيسي', 'name_en' => 'Main Bank', 'level' => 4, 'parent_code' => '1102', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1107', 'name_ar' => 'العملاء', 'name_en' => 'Customers', 'level' => 3, 'parent_code' => '1100', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '110701', 'name_ar' => 'عميل نقدي افتراضي', 'name_en' => 'Default Cash Customer', 'level' => 4, 'parent_code' => '1107', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1108', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'level' => 3, 'parent_code' => '1100', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '110801', 'name_ar' => 'المخزون الرئيسي', 'name_en' => 'Main Inventory', 'level' => 4, 'parent_code' => '1108', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11080101', 'name_ar' => 'مخزون ذهب مشغول', 'name_en' => 'Crafted Gold Stock', 'level' => 5, 'parent_code' => '110801', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11080102', 'name_ar' => 'مخزون ذهب كسر', 'name_en' => 'Scrap Gold Stock', 'level' => 5, 'parent_code' => '110801', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '11080103', 'name_ar' => 'مخزون ذهب صافي', 'name_en' => 'Pure Gold Stock', 'level' => 5, 'parent_code' => '110801', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '1112', 'name_ar' => 'أرصدة مدينة أخرى', 'name_en' => 'Other Debit Balances', 'level' => 3, 'parent_code' => '1100', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '111203', 'name_ar' => 'ضريبة المبيعات', 'name_en' => 'Sales Tax', 'level' => 4, 'parent_code' => '1112', 'account_type' => 'assets', 'transfer_side' => 'budget'],
            ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'level' => 1, 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'level' => 2, 'parent_code' => '2000', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2101', 'name_ar' => 'الموردين', 'name_en' => 'Suppliers', 'level' => 3, 'parent_code' => '2100', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '210101', 'name_ar' => 'مورد نقدي افتراضي', 'name_en' => 'Default Cash Supplier', 'level' => 4, 'parent_code' => '2101', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '2103', 'name_ar' => 'أرصدة دائنة أخرى', 'name_en' => 'Other Credit Balances', 'level' => 3, 'parent_code' => '2100', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '210303', 'name_ar' => 'ضريبة المشتريات', 'name_en' => 'Purchase Tax', 'level' => 4, 'parent_code' => '2103', 'account_type' => 'liabilities', 'transfer_side' => 'budget'],
            ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'level' => 1, 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '3300', 'name_ar' => 'حساب الربح أو الخسارة', 'name_en' => 'Profit and Loss Account', 'level' => 2, 'parent_code' => '3000', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '3301', 'name_ar' => 'صافي الربح', 'name_en' => 'Net Profit', 'level' => 3, 'parent_code' => '3300', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '330102', 'name_ar' => 'معادلة الربح في المبيعات', 'name_en' => 'Sales Profit Offset', 'level' => 4, 'parent_code' => '3301', 'account_type' => 'equity', 'transfer_side' => 'budget'],
            ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenues', 'level' => 1, 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4100', 'name_ar' => 'صافي المبيعات', 'name_en' => 'Net Sales', 'level' => 2, 'parent_code' => '4000', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4101', 'name_ar' => 'إجمالي المبيعات', 'name_en' => 'Gross Sales', 'level' => 3, 'parent_code' => '4100', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '410101', 'name_ar' => 'إجمالي المبيعات - افتراضي', 'name_en' => 'Default Gross Sales', 'level' => 4, 'parent_code' => '4101', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '4102', 'name_ar' => 'مردودات المبيعات', 'name_en' => 'Sales Returns', 'level' => 3, 'parent_code' => '4100', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '410201', 'name_ar' => 'مردودات المبيعات - افتراضي', 'name_en' => 'Default Sales Returns', 'level' => 4, 'parent_code' => '4102', 'account_type' => 'revenues', 'transfer_side' => 'income_statement'],
            ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'level' => 1, 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5200', 'name_ar' => 'تكلفة المبيعات', 'name_en' => 'Cost of Sales', 'level' => 2, 'parent_code' => '5000', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '520101', 'name_ar' => 'تكلفة مشغولات', 'name_en' => 'Crafted Cost', 'level' => 3, 'parent_code' => '5200', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '520102', 'name_ar' => 'تكلفة سكراب', 'name_en' => 'Scrap Cost', 'level' => 3, 'parent_code' => '5200', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '520103', 'name_ar' => 'تكلفة ذهب صافي', 'name_en' => 'Pure Gold Cost', 'level' => 3, 'parent_code' => '5200', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5300', 'name_ar' => 'مصروفات عمومية وإدارية', 'name_en' => 'General and Administrative Expenses', 'level' => 2, 'parent_code' => '5000', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '5305', 'name_ar' => 'أجور تصنيع', 'name_en' => 'Manufacturing Wages', 'level' => 3, 'parent_code' => '5300', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
            ['code' => '530501', 'name_ar' => 'أجور تصنيع ذهب مشغول', 'name_en' => 'Crafted Manufacturing Wages', 'level' => 4, 'parent_code' => '5305', 'account_type' => 'expenses', 'transfer_side' => 'income_statement'],
        ];
    }
}
