<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldPriceHistory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'synced_by_user_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'remote' => 'بورصة/خدمة خارجية',
            'manual' => 'تحديث يدوي',
            default => 'غير محدد',
        };
    }
}
