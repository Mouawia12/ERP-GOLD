<?php

namespace App\Services\Accounts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * أي الحسابات يصح أن يُصبّ فيها حساب آخر؟
 *
 * القيود تُرحَّل على الحسابات النهائية وحدها — البحث عن الحسابات يستبعد كل
 * حساب له أبناء. فإذا صار حسابٌ عليه حركة أبًا، خرج من متناول الترحيل ورصيده
 * ما زال عليه، فيصير رصيدًا محبوسًا لا يمكن تصحيحه إلا بنقل شجرته.
 *
 * ولا يصح اشتراط «رئيسي» فقط: كل حساب يبدأ نهائيًا، فلن يمكن إنشاء أول ابن
 * لأي حساب. الشرط الصحيح أن يكون الأب خاليًا من الحركة — سواء كان له أبناء
 * بالفعل أو كان فارغًا ينتظرهم.
 */
class ParentAccountEligibility
{
    /**
     * معرّفات الحسابات التي عليها حركة، فلا تصلح آباءً.
     *
     * @return array<int, int>
     */
    public function postedAccountIds(): array
    {
        $ids = [];

        if (Schema::hasTable('journal_entry_documents')) {
            $ids = array_merge($ids, DB::table('journal_entry_documents')
                ->distinct()
                ->pluck('account_id')
                ->all());
        }

        if (Schema::hasTable('opening_balances')) {
            $ids = array_merge($ids, DB::table('opening_balances')
                ->distinct()
                ->pluck('account_id')
                ->all());
        }

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    public function carriesMovement(?int $accountId): bool
    {
        if (blank($accountId)) {
            return false;
        }

        $hasDocuments = Schema::hasTable('journal_entry_documents')
            && DB::table('journal_entry_documents')->where('account_id', $accountId)->exists();

        if ($hasDocuments) {
            return true;
        }

        return Schema::hasTable('opening_balances')
            && DB::table('opening_balances')->where('account_id', $accountId)->exists();
    }
}
