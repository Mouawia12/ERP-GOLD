<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BranchZatcaSetting extends Model
{
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        static::creating(function (BranchZatcaSetting $setting) {
            $setting->egs_serial_number = '1-' . Str::random(4) . '|2-' . Str::random(4) . '|3-' . Str::random(4);
        });
        static::updating(function (BranchZatcaSetting $setting) {
            $setting->egs_serial_number = '1-' . Str::random(4) . '|2-' . Str::random(4) . '|3-' . Str::random(4);
        });
    }
}
