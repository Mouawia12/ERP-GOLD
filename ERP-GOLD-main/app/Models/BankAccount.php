<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;
    use BelongsToSubscriberScope;

    protected $guarded = ['id'];

    protected $casts = [
        'supports_credit_card' => 'boolean',
        'supports_bank_transfer' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $bankAccount) {
            if (filled($bankAccount->subscriber_id) || blank($bankAccount->branch_id)) {
                return;
            }

            $bankAccount->subscriber_id = Branch::query()->withoutGlobalScopes()->find($bankAccount->branch_id)?->subscriber_id;
        });
    }

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
