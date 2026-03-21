<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];
    protected $translatable = ['name'];

    public function zatca_settings()
    {
        return $this->hasOne(BranchZatcaSetting::class, 'branch_id', 'id');
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'branch_id', 'id');
    }

    public function accountSetting()
    {
        return $this->hasOne(AccountSetting::class, 'branch_id', 'id');
    }

    public function getBranchNameAttribute(): ?string
    {
        return $this->name;
    }
}
