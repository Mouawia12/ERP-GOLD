<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuditLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event_key) {
            'status_changed' => 'تغيير حالة المستخدم',
            'password_changed' => 'تغيير كلمة المرور',
            'branch_changed' => 'تغيير الفرع',
            'assigned_branches_changed' => 'تغيير الفروع المسموح بها',
            'role_changed' => 'تغيير الصلاحية',
            'direct_permissions_changed' => 'تغيير الصلاحيات المباشرة',
            default => $this->event_key,
        };
    }

    public function getSummaryAttribute(): string
    {
        return match ($this->event_key) {
            'status_changed' => sprintf(
                '%s -> %s',
                $this->statusLabel($this->old_values['status'] ?? null),
                $this->statusLabel($this->new_values['status'] ?? null),
            ),
            'password_changed' => 'تم تسجيل عملية إعادة تعيين أو تحديث كلمة المرور.',
            'branch_changed' => sprintf(
                '%s -> %s',
                $this->stringValue($this->old_values['branch_name'] ?? null),
                $this->stringValue($this->new_values['branch_name'] ?? null),
            ),
            'assigned_branches_changed' => sprintf(
                '%s -> %s',
                $this->branchesSummary($this->old_values['branches'] ?? []),
                $this->branchesSummary($this->new_values['branches'] ?? []),
            ),
            'role_changed' => sprintf(
                '%s -> %s',
                $this->stringValue($this->old_values['role_name'] ?? null),
                $this->stringValue($this->new_values['role_name'] ?? null),
            ),
            'direct_permissions_changed' => sprintf(
                '%s -> %s',
                $this->permissionsSummary($this->old_values['permissions'] ?? []),
                $this->permissionsSummary($this->new_values['permissions'] ?? []),
            ),
            default => '-',
        };
    }

    private function statusLabel($value): string
    {
        if ($value === null) {
            return '-';
        }

        return (bool) $value ? 'مفعل' : 'موقوف';
    }

    private function stringValue($value): string
    {
        if (is_array($value)) {
            return $value['ar'] ?? $value['en'] ?? reset($value) ?: '-';
        }

        return $value ?: '-';
    }

    private function permissionsSummary($value): string
    {
        $permissions = collect(is_array($value) ? $value : [])
            ->filter()
            ->values();

        if ($permissions->isEmpty()) {
            return 'بدون صلاحيات مباشرة';
        }

        $preview = $permissions->take(3)->implode('، ');
        $suffix = $permissions->count() > 3 ? ' ...' : '';

        return sprintf('%d صلاحية: %s%s', $permissions->count(), $preview, $suffix);
    }

    private function branchesSummary($value): string
    {
        $branches = collect(is_array($value) ? $value : [])
            ->map(function ($branch) {
                if (! is_array($branch)) {
                    return null;
                }

                return $branch['name'] ?? null;
            })
            ->filter()
            ->values();

        if ($branches->isEmpty()) {
            return 'بدون فروع مرتبطة';
        }

        return $branches->implode('، ');
    }
}
