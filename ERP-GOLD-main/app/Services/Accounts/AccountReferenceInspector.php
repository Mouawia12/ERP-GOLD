<?php

namespace App\Services\Accounts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ما الذي يمنع حذف حساب؟
 *
 * الحذف مقصور على الحسابات الفارغة بلا حركة، لكن الارتباطات لا تقتصر على القيود
 * والأرصدة: الحساب قد يكون مربوطًا في إعدادات الفروع المحاسبية أو بعميل أو
 * بسند. بدون هذا الفحص يصطدم الحذف بقيد قاعدة البيانات وتظهر رسالة SQL
 * إنجليزية غير مفهومة، فيبدو كأن البرنامج يرفض بلا سبب.
 */
class AccountReferenceInspector
{
    /** @var array<string, string> */
    private const LABELS = [
        'accounts' => 'حسابات فرعية',
        'journal_entry_documents' => 'قيود يومية',
        'opening_balances' => 'أرصدة افتتاحية',
        'account_settings' => 'الربط المحاسبي للفروع (إعدادات الحسابات)',
        'customers' => 'عملاء أو موردين',
        'invoices' => 'فواتير',
        'financial_vouchers' => 'سندات قبض أو صرف',
        'bank_accounts' => 'حسابات بنكية',
        'branch_karat_transfers' => 'تحويلات بين الفروع',
    ];

    /** الجداول التي لا تمنع الحذف — روابط تُفَك تلقائيًا. */
    private const DETACHABLE = ['account_branch'];

    /**
     * أسباب المنع مع عدد السجلات لكل سبب.
     *
     * @return array<string, int>
     */
    public function blockingReferences(int $accountId): array
    {
        $blocking = [];

        foreach ($this->references() as [$table, $column]) {
            if (in_array($table, self::DETACHABLE, true)) {
                continue;
            }

            $count = DB::table($table)->where($column, $accountId)->count();

            if ($count === 0) {
                continue;
            }

            $label = self::LABELS[$table] ?? $table;
            $blocking[$label] = ($blocking[$label] ?? 0) + $count;
        }

        return $blocking;
    }

    public function blockingMessage(int $accountId): ?string
    {
        $blocking = $this->blockingReferences($accountId);

        if ($blocking === []) {
            return null;
        }

        $parts = [];
        foreach ($blocking as $label => $count) {
            $parts[] = $label . ' (' . $count . ')';
        }

        return 'لا يمكن حذف الحساب لأنه مستخدم في: ' . implode('، ', $parts)
            . '. الحذف متاح فقط للحسابات الفارغة بلا حركة وبلا ارتباطات.';
    }

    /**
     * كل عمود يشير إلى accounts.id — يُكتشف من قاعدة البيانات على MySQL،
     * مع قائمة أساسية تعمل على أي محرك (sqlite في الاختبارات).
     *
     * @return array<int, array{0:string,1:string}>
     */
    private function references(): array
    {
        $pairs = [
            ['accounts', 'parent_account_id'],
            ['journal_entry_documents', 'account_id'],
            ['opening_balances', 'account_id'],
            ['customers', 'account_id'],
            ['invoices', 'account_id'],
            ['bank_accounts', 'ledger_account_id'],
            ['branch_karat_transfers', 'account_id'],
            ['financial_vouchers', 'from_account_id'],
            ['financial_vouchers', 'to_account_id'],
        ];

        if (DB::connection()->getDriverName() === 'mysql') {
            $rows = DB::select(
                'SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.KEY_COLUMN_USAGE '
                . 'WHERE REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()',
                ['accounts', 'id']
            );

            foreach ($rows as $row) {
                $pairs[] = [$row->t, $row->c];
            }
        }

        $seen = [];
        $valid = [];

        foreach ($pairs as [$table, $column]) {
            $key = $table . '.' . $column;

            if (isset($seen[$key]) || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $seen[$key] = true;
            $valid[] = [$table, $column];
        }

        return $valid;
    }
}
