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
            $branchSetting = AccountSetting::first();
            if ($customer->type == 'customer') {
                $parentAccount = Account::find($branchSetting->clients_account);
            } else {
                $parentAccount = Account::find($branchSetting->suppliers_account);
            }

            $customerAccount = $parentAccount->childrens()->create([
                'name' => ['en' => $customer->name, 'ar' => $customer->name],
            ]);
            $customer->account_id = $customerAccount->id;
        });
    }
}
