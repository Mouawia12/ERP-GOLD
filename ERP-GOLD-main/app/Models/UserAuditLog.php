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
            'role_changed' => 'تغيير الصلاحية',
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
            'role_changed' => sprintf(
                '%s -> %s',
                $this->stringValue($this->old_values['role_name'] ?? null),
                $this->stringValue($this->new_values['role_name'] ?? null),
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
}
