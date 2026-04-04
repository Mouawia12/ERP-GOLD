<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            $branchSetting = AccountSetting::query()
                ->when(
                    filled(auth('admin-web')->user()?->branch_id),
                    fn ($query) => $query->where('branch_id', auth('admin-web')->user()?->branch_id)
                )
                ->orderBy('branch_id')
                ->first();

            if (! $branchSetting) {
                return;
            }

            if ($customer->type == 'customer') {
                $parentAccount = Account::find($branchSetting->clients_account);
            } else {
                $parentAccount = Account::find($branchSetting->suppliers_account);
            }

            if (! $parentAccount) {
                return;
            }

            $customerAccount = $parentAccount->childrens()->create([
                'subscriber_id' => $parentAccount->subscriber_id,
                'name' => ['en' => $customer->name, 'ar' => $customer->name],
                'account_type' => $parentAccount->account_type,
                'transfer_side' => $parentAccount->transfer_side,
            ]);
            $customer->account_id = $customerAccount->id;
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (! $user || blank($user->subscriber_id)) {
            return $query;
        }

        $subscriberId = (int) $user->subscriber_id;

        return $query->where(function (Builder $customerQuery) use ($subscriberId) {
            $customerQuery
                ->whereHas('account', function (Builder $accountQuery) use ($subscriberId) {
                    $accountQuery->withoutGlobalScopes()->where('subscriber_id', $subscriberId);
                })
                ->orWhereHas('invoices.branch', function (Builder $branchQuery) use ($subscriberId) {
                    $branchQuery->where('subscriber_id', $subscriberId);
                });
        });
    }
}
