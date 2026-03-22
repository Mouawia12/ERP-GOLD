<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_update' => 'datetime',
        'meta' => 'array',
    ];

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'remote' => 'بورصة/خدمة خارجية',
            'manual' => 'تحديث يدوي',
            default => 'غير محدد',
        };
    }

    public static function latestSnapshot(): ?self
    {
        return static::query()->orderByDesc('last_update')->orderByDesc('id')->first();
    }
}
