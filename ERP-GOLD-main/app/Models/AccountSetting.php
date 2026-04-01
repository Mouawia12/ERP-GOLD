<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    use HasFactory;
    use BelongsToSubscriberScope;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $setting) {
            if (filled($setting->subscriber_id) || blank($setting->branch_id)) {
                return;
            }

            $setting->subscriber_id = Branch::query()->withoutGlobalScopes()->find($setting->branch_id)?->subscriber_id;
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
