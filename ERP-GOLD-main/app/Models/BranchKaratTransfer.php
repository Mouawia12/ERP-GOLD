<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchKaratTransfer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'bill_date' => 'date',
        'total_from_weight' => 'float',
        'total_to_weight' => 'float',
        'total_value' => 'float',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function goldCaratType()
    {
        return $this->belongsTo(GoldCaratType::class, 'gold_carat_type_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function outInvoice()
    {
        return $this->belongsTo(Invoice::class, 'out_invoice_id');
    }

    public function inInvoice()
    {
        return $this->belongsTo(Invoice::class, 'in_invoice_id');
    }

    public function lines()
    {
        return $this->hasMany(BranchKaratTransferLine::class, 'transfer_id');
    }

    public static function nextBillNumber(): string
    {
        $last = self::query()->orderByDesc('id')->value('bill_number');
        $sequence = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $sequence = ((int) $m[1]) + 1;
        }

        return 'BKT-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
