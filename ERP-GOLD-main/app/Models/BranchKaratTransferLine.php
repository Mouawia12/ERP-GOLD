<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchKaratTransferLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'from_weight' => 'float',
        'to_weight' => 'float',
    ];

    public function transfer()
    {
        return $this->belongsTo(BranchKaratTransfer::class, 'transfer_id');
    }

    public function fromCarat()
    {
        return $this->belongsTo(GoldCarat::class, 'from_carat_id');
    }

    public function toCarat()
    {
        return $this->belongsTo(GoldCarat::class, 'to_carat_id');
    }
}
