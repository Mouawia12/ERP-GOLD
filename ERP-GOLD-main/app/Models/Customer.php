<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            ]);
            $customer->account_id = $customerAccount->id;
        });
    }
}
