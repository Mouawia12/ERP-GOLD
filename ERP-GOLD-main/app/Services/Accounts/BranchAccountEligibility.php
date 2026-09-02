<?php

namespace App\Services\Accounts;

use Illuminate\Support\Facades\DB;

/**
 * هل يصحّ ربط هذا الحساب بالإعدادات المحاسبية لهذا الفرع؟
 *
 * شاشة الحساب تتيح تخصيصه بفروع معيّنة، وشرحها: «اترك الحقل فارغًا ليخص الحساب
 * كل الفروع». لكن شاشة الروابط المحاسبية كانت تقبل أي حساب، فيمكن أن يشير
 * `sales_account` لفرع إلى حساب مبيعات فرع آخر — فتذهب مبيعات الفرع إلى حساب
 * لا يخصّه، ويظهر ذلك في قائمة الدخل كأن فرعًا يتسرّب داخل فرع.
 */
class BranchAccountEligibility
{
    /**
     * الحساب غير المربوط بأي فرع يخص كل الفروع، فيُقبل دائمًا.
     * أما المربوط بفروع محددة فلا يُقبل إلا لأحدها.
     */
    public function isLinkableTo(?int $accountId, ?int $branchId): bool
    {
        if (blank($accountId)) {
            return true;
        }

        // إعداد بلا فرع (إعداد عام قديم) يبقى كما كان حتى لا تنكسر تهيئة قائمة.
        if (blank($branchId)) {
            return true;
        }

        $assignedBranchIds = DB::table('account_branch')
            ->where('account_id', $accountId)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id);

        if ($assignedBranchIds->isEmpty()) {
            return true;
        }

        return $assignedBranchIds->contains((int) $branchId);
    }

    /**
     * أسماء الفروع التي يخصّها الحساب — لصياغة رسالة تقول للمستخدم أين يقع.
     */
    public function branchNamesFor(int $accountId): string
    {
        return DB::table('account_branch')
            ->join('branches', 'branches.id', '=', 'account_branch.branch_id')
            ->where('account_branch.account_id', $accountId)
            ->pluck('branches.name')
            ->map(function ($name) {
                $decoded = json_decode((string) $name, true);

                return is_array($decoded)
                    ? ($decoded['ar'] ?? $decoded['en'] ?? (string) $name)
                    : (string) $name;
            })
            ->implode('، ');
    }
}
