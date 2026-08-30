<?php

namespace App\Services\Accounts;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

/**
 * يعيد ترقيم الحسابات بعد نقلها في الشجرة.
 *
 * المخطط المعتمد (نفس `2026_08_19_000001_renumber_accounts_to_standard_scheme`
 * و`Account::codePrefix`): كل مستوى يضيف `level - 1` خانة.
 *   L1 = 1  |  L2 = 11  |  L3 = 1101  |  L4 = 1101001
 *
 * الكود قيمة عرض وترتيب فقط — كل الروابط المحاسبية عبر `accounts.id` — لذلك
 * إعادة الترقيم لا تغيّر أي سلوك محاسبي.
 */
class AccountRenumberingService
{
    /**
     * يعيد ترقيم مجموعة الإخوة الجديدة والقديمة بعد نقل حساب.
     *
     * الحساب المنقول يوضع آخر إخوته الجدد، وتُغلق الفجوة التي تركها في موضعه
     * القديم، ثم تُحدَّث أكواد ومستويات كل الفروع التابعة للمجموعتين.
     */
    public function renumberAfterMove(Account $account, ?int $previousParentId): void
    {
        $currentParentId = $account->parent_account_id !== null
            ? (int) $account->parent_account_id
            : null;

        $subscriberId = $account->subscriber_id !== null
            ? (int) $account->subscriber_id
            : null;

        DB::transaction(function () use ($account, $currentParentId, $previousParentId, $subscriberId) {
            $this->renumberSiblings($currentParentId, $subscriberId, (int) $account->id);

            if ($previousParentId !== $currentParentId) {
                $this->renumberSiblings($previousParentId, $subscriberId, null);
            }
        });

        $account->refresh();
    }

    /**
     * هل نقل `$account` تحت `$newParentId` ينشئ دورة في الشجرة؟
     *
     * النقل تحت الحساب نفسه أو تحت أحد أبنائه يقطع الشجرة ويجعل إعادة الترقيم
     * تدور بلا نهاية، لذلك يُمنع قبل الحفظ.
     */
    public function wouldCreateCycle(Account $account, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        $accountId = (int) $account->id;

        if ($newParentId === $accountId) {
            return true;
        }

        $ancestorId = $newParentId;
        $guard = 0;

        while ($ancestorId !== null && $guard++ < 100) {
            if ($ancestorId === $accountId) {
                return true;
            }

            $parentId = DB::table('accounts')
                ->where('id', $ancestorId)
                ->value('parent_account_id');

            $ancestorId = $parentId !== null ? (int) $parentId : null;
        }

        return false;
    }

    /**
     * يعيد ترقيم كل أبناء `$parentId` بالتسلسل ثم فروعهم.
     *
     * `$forceLastId` يثبّت الحساب المنقول في آخر المجموعة بدل ترتيبه بكوده
     * القديم القادم من فرع آخر.
     */
    private function renumberSiblings(?int $parentId, ?int $subscriberId, ?int $forceLastId): void
    {
        if ($parentId === null) {
            $this->renumberChildren(null, '', 1, $subscriberId, $forceLastId);

            return;
        }

        $parent = DB::table('accounts')->where('id', $parentId)->first(['code', 'level']);

        if ($parent === null) {
            return;
        }

        $this->renumberChildren(
            $parentId,
            (string) $parent->code,
            ((int) $parent->level) + 1,
            $subscriberId,
            $forceLastId
        );
    }

    private function renumberChildren(
        ?int $parentId,
        string $parentCode,
        int $level,
        ?int $subscriberId,
        ?int $forceLastId = null
    ): void {
        $children = DB::table('accounts')
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_account_id'),
                fn ($query) => $query->where('parent_account_id', $parentId)
            )
            ->when(
                $parentId === null,
                fn ($query) => $subscriberId === null
                    ? $query->whereNull('subscriber_id')
                    : $query->where('subscriber_id', $subscriberId)
            )
            ->orderBy('code')
            ->orderBy('id')
            ->get(['id', 'code']);

        if ($forceLastId !== null) {
            $moved = $children->firstWhere('id', $forceLastId);

            if ($moved !== null) {
                $children = $children->reject(fn ($child) => (int) $child->id === $forceLastId)
                    ->values()
                    ->push($moved);
            }
        }

        $sequence = 1;

        foreach ($children as $child) {
            $newCode = $parentCode . $this->codePrefix($sequence, $level);

            if ((string) $child->code !== $newCode) {
                DB::table('accounts')
                    ->where('id', $child->id)
                    ->update(['code' => $newCode, 'level' => (string) $level]);
            } else {
                DB::table('accounts')
                    ->where('id', $child->id)
                    ->update(['level' => (string) $level]);
            }

            $this->renumberChildren((int) $child->id, $newCode, $level + 1, $subscriberId);
            $sequence++;
        }
    }

    private function codePrefix(int $number, int $level): string
    {
        return str_pad((string) $number, max($level - 1, 0), '0', STR_PAD_LEFT);
    }
}
