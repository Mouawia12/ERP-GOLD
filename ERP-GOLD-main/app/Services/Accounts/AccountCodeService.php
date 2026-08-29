<?php

namespace App\Services\Accounts;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;

/**
 * توليد أكواد شجرة الحسابات وإعادة ترقيمها.
 *
 * النمط المعياري (نفس ما تولّده Account::codePrefix وقالب SubscriberChartProvisioner):
 *   المستوى 1 = خانة واحدة (1) | 2 = خانتان (11) | 3 = أربع (1101) | 4 = سبع (1101001)
 * أي أن كل مستوى يضيف (level - 1) خانة إلى كود الأب.
 *
 * الأكواد للعرض والترتيب فقط: كل الروابط المحاسبية (قيود، أرصدة افتتاحية، إعدادات)
 * بالـ id، ولا شيء في النظام يستنتج الشجرة من نص الكود — البنية من
 * parent_account_id / level. لذلك إعادة الترقيم لا تمسّ أي رصيد.
 *
 * كل الاستعلامات هنا تتجاوز الـ global scope وتُقيّد يدويًا بمشترك الحساب نفسه،
 * حتى لا تتسرّب عملية إلى شجرة مشترك آخر ولا تُحسب الإخوة ناقصة.
 */
class AccountCodeService
{
    /** ترتيب الجذور المعياري: الأصول 1، الخصوم 2، حقوق الملكية 3، الإيرادات 4، المصروفات 5. */
    public const ROOT_ORDER = [
        'assets' => 1,
        'liabilities' => 2,
        'equity' => 3,
        'revenues' => 4,
        'expenses' => 5,
    ];

    /** الجزء الذي يضيفه المستوى إلى كود الأب. */
    public function codeSegment(int $sequence, int $level): string
    {
        return str_pad((string) $sequence, max($level - 1, 0), '0', STR_PAD_LEFT);
    }

    /** استعلام مقصور على شجرة مشترك واحد (أو دلو subscriber_id = NULL). */
    public function treeQuery($subscriberId): Builder
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->when(
                is_null($subscriberId),
                fn (Builder $query) => $query->whereNull('subscriber_id'),
                fn (Builder $query) => $query->where('subscriber_id', $subscriberId)
            );
    }

    /** مستوى الحساب تبعًا لأبيه. */
    public function levelFor(?Account $parent): int
    {
        return $parent ? ((int) $parent->level) + 1 : 1;
    }

    /**
     * أول كود شاغر تحت أب معيّن — يبدأ من (عدد الإخوة + 1) ويتخطّى أي كود مستعمل
     * في شجرة المشترك كلها، فلا يحدث تصادم مع حساب آخر.
     */
    public function nextCode(?Account $parent, $subscriberId, ?int $ignoreId = null): string
    {
        $level = $this->levelFor($parent);
        $prefix = $parent?->code ?? '';

        $siblings = $this->treeQuery($subscriberId)
            ->when(
                $parent,
                fn (Builder $query) => $query->where('parent_account_id', $parent->id),
                fn (Builder $query) => $query->whereNull('parent_account_id')
            )
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->count();

        $sequence = $siblings + 1;

        while ($this->codeTaken($subscriberId, $prefix . $this->codeSegment($sequence, $level), $ignoreId)) {
            $sequence++;
        }

        return $prefix . $this->codeSegment($sequence, $level);
    }

    public function codeTaken($subscriberId, string $code, ?int $ignoreId = null): bool
    {
        return $this->treeQuery($subscriberId)
            ->where('code', $code)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * هل كود الحساب متّسق مع موضعه في الشجرة؟
     *
     * الكود لازم يكون كود الأب + رقم تسلسلي، ومستواه = مستوى الأب + 1. الحساب
     * المنقول قديمًا (قبل أن يُعاد الترقيم عند تغيير الأب) يحتفظ بكود عيلته
     * السابقة — وهذا ما يكشفه هذا الفحص حتى يُصحَّح عند أول حفظ.
     */
    public function isCodeConsistent(Account $account): bool
    {
        $parent = $account->parent_account_id
            ? $this->treeQuery($account->subscriber_id)->find($account->parent_account_id)
            : null;

        $level = $this->levelFor($parent);
        $code = (string) $account->code;

        if ($code === '' || (int) $account->level !== $level) {
            return false;
        }

        $prefix = (string) ($parent?->code ?? '');

        if ($prefix !== '' && ! str_starts_with($code, $prefix)) {
            return false;
        }

        $suffix = substr($code, strlen($prefix));

        // الجزء الخاص بالحساب لا يقل عن عرض المستوى (قد يزيد إذا تجاوز الإخوة
        // سعة الخانات) ولا بد أن يكون رقمًا.
        return $suffix !== ''
            && ctype_digit($suffix)
            && strlen($suffix) >= max($level - 1, 1);
    }

    /**
     * يعيد ترقيم الحساب حسب موضعه الحالي في الشجرة ثم كل فروعه بالتتابع.
     *
     * @return int عدد الحسابات التي تغيّر كودها (الحساب + الفروع)
     */
    public function recodeSubtree(Account $account): int
    {
        $parent = $account->parent_account_id
            ? $this->treeQuery($account->subscriber_id)->find($account->parent_account_id)
            : null;

        $level = $this->levelFor($parent);
        $code = $this->nextCode($parent, $account->subscriber_id, (int) $account->id);

        $this->applyCode((int) $account->id, $code, $level, $account->subscriber_id);

        $account->code = $code;
        $account->level = (string) $level;

        return 1 + $this->recodeChildren((int) $account->id, $code, $level + 1, $account->subscriber_id);
    }

    /** إعادة ترقيم أبناء حساب (وأبنائهم) بالتتابع فوق كود الأب الجديد. */
    public function recodeChildren($parentId, string $parentCode, int $level, $subscriberId): int
    {
        $children = $this->treeQuery($subscriberId)
            ->where('parent_account_id', $parentId)
            ->orderBy('code')
            ->orderBy('id')
            ->get(['id']);

        $touched = 0;
        $sequence = 1;

        foreach ($children as $child) {
            $code = $parentCode . $this->codeSegment($sequence, $level);

            $this->applyCode((int) $child->id, $code, $level, $subscriberId);
            $touched++;
            $touched += $this->recodeChildren((int) $child->id, $code, $level + 1, $subscriberId);
            $sequence++;
        }

        return $touched;
    }

    /**
     * إعادة ترقيم شجرة مشترك كاملة: الجذور بالترتيب المعياري ثم كل فرع بالتتابع.
     * حتمية وقابلة لإعادة التشغيل — تشغيلها مرتين ينتج نفس الأكواد.
     *
     * @return int عدد الحسابات التي مرّت عليها العملية
     */
    public function renumberSubscriber($subscriberId): int
    {
        $roots = $this->treeQuery($subscriberId)
            ->whereNull('parent_account_id')
            ->get(['id', 'code', 'account_type'])
            // مفتاح مركّب: الرتبة المعيارية للنوع، ثم الكود الحالي (الترتيب الذي يراه
            // المستخدم اليوم) للجذور غير المعروفة النوع، ثم المعرّف — فالنتيجة حتمية.
            ->sortBy(fn ($account) => sprintf(
                '%02d|%020s|%010d',
                self::ROOT_ORDER[$account->account_type] ?? 99,
                (string) $account->code,
                (int) $account->id
            ))
            ->values();

        $touched = 0;
        $sequence = 1;

        foreach ($roots as $root) {
            $code = $this->codeSegment($sequence, 1);

            $this->applyCode((int) $root->id, $code, 1, $subscriberId);
            $touched++;
            $touched += $this->recodeChildren((int) $root->id, $code, 2, $subscriberId);
            $sequence++;
        }

        return $touched;
    }

    private function applyCode(int $accountId, string $code, int $level, $subscriberId): void
    {
        $this->treeQuery($subscriberId)
            ->where('id', $accountId)
            ->update(['code' => $code, 'level' => (string) $level]);
    }
}
