<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\BranchItem;
use App\Models\Customer;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriberTestingDataSeeder extends Seeder
{
    private const TARGET_EMAIL = 'ymouawia10@gmail.com';

    public function run(): void
    {
        DB::transaction(function () {
            $user = User::query()->where('email', self::TARGET_EMAIL)->first();

            if (! $user) {
                $this->command?->warn('Target user not found for testing seeder: ' . self::TARGET_EMAIL);
                return;
            }

            $subscriber = $user->subscriber ?: Subscriber::query()->find($user->subscriber_id);
            if (! $subscriber) {
                $this->command?->warn('Target subscriber not found for testing seeder: ' . self::TARGET_EMAIL);
                return;
            }

            $branches = $subscriber->branches()->get();

            if ($branches->isEmpty() && $user->branch) {
                $user->branch->update([
                    'subscriber_id' => $subscriber->id,
                ]);
                $branches = collect([$user->branch->fresh()]);
            }

            if ($branches->isEmpty()) {
                $this->command?->warn('No branches found for subscriber: ' . self::TARGET_EMAIL);
                return;
            }

            foreach ($branches as $branch) {
                $this->seedBranchTestingData($subscriber, $user, $branch);
            }

            $this->command?->info('Subscriber testing data prepared for: ' . self::TARGET_EMAIL);
        });
    }

    private function seedBranchTestingData(Subscriber $subscriber, User $user, Branch $branch): void
    {
        $safeAccount = $this->referenceAccount('5', 'الصندوق التجريبي');
        $bankLedgerAccount = $this->referenceAccount('6', 'الحساب البنكي التجريبي');
        $salesAccount = $this->referenceAccount('58', 'إيرادات المبيعات');
        $returnSalesAccount = $this->referenceAccount('60', 'مردودات المبيعات');
        $stockCraftedAccount = $this->referenceAccount('113', 'مخزون مشغولات');
        $stockScrapAccount = $this->referenceAccount('114', 'مخزون سكراب');
        $stockPureAccount = $this->referenceAccount('115', 'مخزون ذهب صافي');
        $madeAccount = $this->referenceAccount('117', 'المصنعية');
        $costCraftedAccount = $this->referenceAccount('192', 'تكلفة مشغولات');
        $costScrapAccount = $this->referenceAccount('193', 'تكلفة سكراب');
        $costPureAccount = $this->referenceAccount('194', 'تكلفة ذهب صافي');
        $reverseProfitAccount = $this->referenceAccount('79', 'عكس الربح');
        $profitAccount = $this->referenceAccount('51', 'الأرباح');
        $purchaseTaxAccount = $this->referenceAccount('43', 'ضريبة المشتريات');
        $salesTaxAccount = $this->referenceAccount('23', 'ضريبة المبيعات');
        $supplierDefaultAccount = $this->referenceAccount('104', 'ذمم موردين فرعية');
        $suppliersAccount = $this->referenceAccount('28', 'ذمم الموردين');
        $clientsAccount = $this->referenceAccount('13', 'ذمم العملاء');

        $accountSetting = AccountSetting::query()->updateOrCreate(
            ['branch_id' => $branch->id],
            [
                'safe_account' => $safeAccount->id,
                'bank_account' => $bankLedgerAccount->id,
                'sales_account' => $salesAccount->id,
                'return_sales_account' => $returnSalesAccount->id,
                'stock_account_crafted' => $stockCraftedAccount->id,
                'stock_account_scrap' => $stockScrapAccount->id,
                'stock_account_pure' => $stockPureAccount->id,
                'made_account' => $madeAccount->id,
                'cost_account_crafted' => $costCraftedAccount->id,
                'cost_account_scrap' => $costScrapAccount->id,
                'cost_account_pure' => $costPureAccount->id,
                'reverse_profit_account' => $reverseProfitAccount->id,
                'profit_account' => $profitAccount->id,
                'purchase_tax_account' => $purchaseTaxAccount->id,
                'sales_tax_account' => $salesTaxAccount->id,
                'supplier_default_account' => $supplierDefaultAccount->id,
                'clients_account' => $clientsAccount->id,
                'suppliers_account' => $suppliersAccount->id,
            ]
        );

        BankAccount::query()->updateOrCreate(
            [
                'branch_id' => $branch->id,
                'account_name' => 'حساب بنكي تجريبي - ' . $branch->id,
            ],
            [
                'ledger_account_id' => $bankLedgerAccount->id,
                'bank_name' => 'البنك التجريبي',
                'iban' => 'QA' . str_pad((string) $branch->id, 22, '0', STR_PAD_LEFT),
                'account_number' => 'ACC-' . str_pad((string) $branch->id, 6, '0', STR_PAD_LEFT),
                'terminal_name' => 'POS-' . $branch->id,
                'device_code' => 'DEV-' . $branch->id,
                'supports_credit_card' => true,
                'supports_bank_transfer' => true,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Warehouse::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'QA-WH-' . $branch->id],
            ['name' => 'المخزن التجريبي - ' . $branch->id]
        );

        $category = ItemCategory::query()->firstOrCreate(
            ['code' => 'QA-CAT-' . $subscriber->id],
            [
                'title' => json_encode(['ar' => 'مجموعة تجريبية للمشترك', 'en' => 'Subscriber Test Category'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode(['ar' => 'بيانات تجريبية خاصة بالمشترك للاختبار', 'en' => 'Subscriber-specific testing data'], JSON_UNESCAPED_UNICODE),
                'image_url' => null,
            ]
        );

        Customer::query()->firstOrCreate(
            ['type' => 'supplier', 'name' => 'مورد تجريبي 1'],
            [
                'phone' => '011223344',
                'email' => 'supplier-test@example.com',
            ]
        );

        Customer::query()->firstOrCreate(
            ['type' => 'customer', 'name' => 'عميل تجريبي'],
            [
                'phone' => '0551234567',
                'email' => 'customer-test@example.com',
            ]
        );

        $caratType = GoldCaratType::query()->where('key', 'crafted')->first() ?: GoldCaratType::query()->first();
        $carat = GoldCarat::query()->where('label', 'C21')->first() ?: GoldCarat::query()->orderByDesc('transform_factor')->first();

        if (! $caratType || ! $carat) {
            $this->command?->warn('Skipped item seed because gold carat data is missing.');
            return;
        }

        $item = Item::query()
            ->where('branch_id', $branch->id)
            ->where('title', 'like', '%صنف تجريبي 1%')
            ->first();

        if (! $item) {
            $item = Item::query()->create([
                'title' => ['ar' => 'صنف تجريبي 1', 'en' => 'Test Item 1'],
                'description' => ['ar' => 'subscriber_test_seed', 'en' => 'subscriber_test_seed'],
                'category_id' => $category->id,
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'gold_carat_id' => $carat->id,
                'gold_carat_type_id' => $caratType->id,
                'no_metal' => 0,
                'no_metal_type' => 'fixed',
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 55,
                'status' => true,
            ]);
        } else {
            $item->update([
                'category_id' => $category->id,
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'gold_carat_id' => $carat->id,
                'gold_carat_type_id' => $caratType->id,
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 55,
                'status' => true,
            ]);
        }

        ItemUnit::query()->updateOrCreate(
            ['item_id' => $item->id, 'is_default' => true],
            [
                'weight' => 10,
                'initial_cost_per_gram' => 100,
                'average_cost_per_gram' => 100,
                'current_cost_per_gram' => 100,
                'is_sold' => false,
            ]
        );

        BranchItem::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'item_id' => $item->id],
            [
                'is_active' => true,
                'is_visible' => true,
                'sale_price_per_gram' => 170,
                'published_by_user_id' => $user->id,
            ]
        );

        if ((int) $user->branch_id === (int) $branch->id || blank($user->branch_id)) {
            $user->update([
                'branch_id' => $branch->id,
                'subscriber_id' => $subscriber->id,
            ]);
        }

        if ((int) $branch->subscriber_id !== (int) $subscriber->id) {
            $branch->update([
                'subscriber_id' => $subscriber->id,
            ]);
        }

        $this->command?->info('Prepared testing data for branch #' . $branch->id);
    }

    private function referenceAccount(string $oldId, string $fallbackArabicName): Account
    {
        $account = Account::query()->where('old_id', $oldId)->first();

        if ($account) {
            return $account;
        }

        $account = Account::query()->where('name', 'like', '%' . $fallbackArabicName . '%')->first();

        if ($account) {
            return $account;
        }

        return Account::query()->create([
            'name' => ['ar' => $fallbackArabicName, 'en' => $fallbackArabicName],
            'old_id' => 'QA-' . $oldId,
            'account_type' => config('settings.accounts_types')[0],
            'transfer_side' => config('settings.transfers_sides')[0],
        ]);
    }
}
