<?php

namespace App\Services\Reports;

use App\Models\Subscriber;
use App\Models\User;

/**
 * من يرى في التقارير فواتير كل المستخدمين، ومن يُقصر على فواتيره هو؟
 *
 * الأصل عزل المستخدم التشغيلي على فواتيره. يُستثنى:
 *  - المستخدم بلا مشترك (حسابات النظام العامة)،
 *  - الحساب الرئيسي للمشترك (subscribers.admin_user_id)،
 *  - ومن يحمل صلاحية «تقارير كل المستخدمين» — يراها المالك ويمنحها لمن يشاء
 *    (مدير فروع مثلاً) دون ترقيته إلى الحساب الرئيسي.
 *
 * النطاق يبقى محصورًا بفروع المستخدم في كل الأحوال؛ هذه الصلاحية ترفع قيد
 * «فواتيري فقط» ولا تفتح فرعًا غير مصرّح به.
 */
class ReportUserScopeService
{
    public const ALL_USERS_PERMISSION = 'employee.all_users_reports.show';

    public function seesAllUsers(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (blank($user->subscriber_id)) {
            return true;
        }

        return $this->isSubscriberPrimaryAccount($user) || $this->hasAllUsersPermission($user);
    }

    /**
     * معرّف المستخدم الذي تُقصر عليه التقارير، أو null إذا كان يرى الجميع.
     */
    public function lockedUserId(?User $user): ?int
    {
        if ($this->seesAllUsers($user) || ! $user) {
            return null;
        }

        return (int) $user->id;
    }

    public function isSubscriberPrimaryAccount(?User $user): bool
    {
        if (! $user || blank($user->subscriber_id)) {
            return false;
        }

        $subscriber = $user->relationLoaded('subscriber')
            ? $user->subscriber
            : Subscriber::query()->select('id', 'admin_user_id')->find($user->subscriber_id);

        return (int) ($subscriber?->admin_user_id ?? 0) === (int) $user->id;
    }

    private function hasAllUsersPermission(User $user): bool
    {
        try {
            return $user->hasPermissionTo(self::ALL_USERS_PERMISSION, 'admin-web');
        } catch (\Throwable) {
            // الصلاحية غير موجودة بعد (تثبيت لم تُشغَّل عليه البذور) — العزل يبقى.
            return false;
        }
    }
}
