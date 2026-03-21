<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialVoucher extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            $lastVoucher = FinancialVoucher::where('branch_id', $voucher->branch_id)->where('type', $voucher->type)->orderBy('id', 'desc')->first();
            $branch = $voucher->branch;
            $voucherType = $voucher->type;
            $prefix = '';
            if ($voucherType == 'receipt') {
                $prefix = 'R';
            } elseif ($voucherType == 'payment') {
                $prefix = 'P';
            }
            $voucherCount = ($lastVoucher?->serial ?? 0) + 1;
            $newNumer = str_pad($voucherCount, 5, '0', STR_PAD_LEFT);
            $voucher->bill_number = $prefix . '-' . $branch->id . '-' . $newNumer;
            $voucher->serial = $newNumer;
        });
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'journalable');
    }
}
