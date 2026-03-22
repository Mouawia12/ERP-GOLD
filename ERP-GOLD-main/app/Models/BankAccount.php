<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'supports_credit_card' => 'boolean',
        'supports_bank_transfer' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function paymentLines()
    {
        return $this->hasMany(InvoicePaymentLine::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $segments = array_filter([
            $this->account_name,
            $this->bank_name,
            $this->terminal_name,
        ]);

        return implode(' - ', $segments);
    }
}
