<?php

namespace App\Services\Accounts;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يفحص الحساب قبل الحذف ويمنع حذف أي حساب عليه حركة أو مرتبط ببيانات أخرى.
 *
 * المفاتيح الأجنبية لجداول الحسابات معرفة بـ `cascade` في قاعدة البيانات، أي أن
 * حذف حساب عليه حركة يحذف معه الفواتير والعملاء والقيود المرتبطة به. لذلك يجب أن
 * يمر كل حذف على هذا الفحص قبل تنفيذه.
 */
class AccountDeletionGuard
{
    /**
     * الجداول التي تمثل حركة فعلية على الحساب.
     *
     * @var array<int, array{table: string, columns: array<int, string>, label: string}>
     */
    private const MOVEMENT_SOURCES = [
        ['table' => 'journal_entry_documents', 'columns' => ['account_id'], 'label' => 'قيود يومية'],
        ['table' => 'opening_balances', 'columns' => ['account_id'], 'label' => 'رصيد افتتاحي'],
        ['table' => 'invoices', 'columns' => ['account_id'], 'label' => 'فواتير'],
        ['table' => 'financial_vouchers', 'columns' => ['from_account_id', 'to_account_id'], 'label' => 'سندات مالية'],
        ['table' => 'branch_karat_transfers', 'columns' => ['account_id'], 'label' => 'تحويلات عيارات بين الفروع'],
    ];

    /**
     * الجداول التي تربط الحساب ببيانات تعريفية بدون أن تكون حركة.
     *
     * @var array<int, array{table: string, columns: array<int, string>, label: string}>
     */
    private const LINK_SOURCES = [
        ['table' => 'customers', 'columns' => ['account_id'], 'label' => 'عملاء أو موردين'],
        ['table' => 'bank_accounts', 'columns' => ['ledger_account_id'], 'label' => 'حسابات بنكية'],
    ];

    /**
     * سبب منع الحذف، أو `null` إذا كان الحساب فارغًا ويمكن حذفه.
     */
    public function blockingReason(Account $account): ?string
    {
        $label = $this->accountLabel($account);

        if ($this->hasChildren($account)) {
            return 'لا يمكن حذف الحساب ' . $label . ' لأنه يحتوي على حسابات فرعية.';
        }

        $movements = $this->matchingLabels($account, self::MOVEMENT_SOURCES);

        if ($movements !== []) {
            return 'الحساب ' . $label . ' عليه حركة (' . implode('، ', $movements) . ') ولا يمكن حذفه.';
        }

        $links = $this->matchingLabels($account, self::LINK_SOURCES);

        if ($this->isUsedInAccountSettings($account)) {
            $links[] = 'إعدادات الحسابات';
        }

        if ($links !== []) {
            return 'الحساب ' . $label . ' مرتبط بـ (' . implode('، ', $links) . ') ولا يمكن حذفه.';
        }

        return null;
    }

    public function isDeletable(Account $account): bool
    {
        return $this->blockingReason($account) === null;
    }

    private function hasChildren(Account $account): bool
    {
        return DB::table('accounts')
            ->where('parent_account_id', $account->id)
            ->exists();
    }

    /**
     * @param  array<int, array{table: string, columns: array<int, string>, label: string}>  $sources
     * @return array<int, string>
     */
    private function matchingLabels(Account $account, array $sources): array
    {
        $labels = [];

        foreach ($sources as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            $columns = array_values(array_filter(
                $source['columns'],
                fn (string $column) => Schema::hasColumn($source['table'], $column)
            ));

            if ($columns === []) {
                continue;
            }

            $exists = DB::table($source['table'])
                ->where(function ($query) use ($columns, $account) {
                    foreach ($columns as $column) {
                        $query->orWhere($column, $account->id);
                    }
                })
                ->exists();

            if ($exists) {
                $labels[] = $source['label'];
            }
        }

        return $labels;
    }

    /**
     * `account_settings` يحمل عمودًا لكل حساب افتراضي، والأعمدة تتوسع مع الوقت،
     * لذلك تُقرأ من المخطط بدل تثبيتها في قائمة.
     */
    private function isUsedInAccountSettings(Account $account): bool
    {
        if (! Schema::hasTable('account_settings')) {
            return false;
        }

        $columns = array_values(array_filter(
            Schema::getColumnListing('account_settings'),
            fn (string $column) => str_contains($column, 'account')
        ));

        if ($columns === []) {
            return false;
        }

        return DB::table('account_settings')
            ->where(function ($query) use ($columns, $account) {
                foreach ($columns as $column) {
                    $query->orWhere($column, $account->id);
                }
            })
            ->exists();
    }

    private function accountLabel(Account $account): string
    {
        $name = trim((string) $account->name);
        $code = trim((string) $account->code);

        if ($code !== '' && $name !== '') {
            return '«' . $code . ' - ' . $name . '»';
        }

        return '«' . ($name !== '' ? $name : $code) . '»';
    }
}
