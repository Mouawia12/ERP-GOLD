<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];
    protected $translatable = ['name'];

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

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

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id', 'id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user', 'branch_id', 'user_id')
            ->withPivot(['is_default', 'is_active'])
            ->withTimestamps();
    }

    public function activeAssignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user', 'branch_id', 'user_id')
            ->withPivot(['is_default', 'is_active'])
            ->where('branch_user.is_active', true)
            ->withTimestamps();
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'branch_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'branch_id', 'id');
    }

    public function publishedItems()
    {
        return $this->belongsToMany(Item::class, 'branch_items', 'branch_id', 'item_id')
            ->withPivot(['is_active', 'is_visible', 'sale_price_per_gram', 'published_by_user_id'])
            ->withTimestamps();
    }

    public function getBranchNameAttribute(): ?string
    {
        return $this->name;
    }

    public function getFullAddressAttribute(): string
    {
        $segments = array_filter([
            $this->country,
            $this->region,
            $this->city,
            $this->district,
            $this->street_name,
            $this->building_number,
            $this->postal_code,
        ]);

        return implode(' - ', $segments);
    }
}
