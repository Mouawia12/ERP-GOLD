<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingLossSettlementLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function parentDetail()
    {
        return $this->belongsTo(InvoiceDetail::class, 'parent_detail_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function carat()
    {
        return $this->belongsTo(GoldCarat::class, 'gold_carat_id');
    }

    public function goldCaratType()
    {
        return $this->belongsTo(GoldCaratType::class, 'gold_carat_type_id');
    }

    public function getSettlementTypeLabelAttribute(): string
    {
        return match ($this->settlement_type) {
            'natural_loss' => 'فاقد طبيعي',
            'final_damage' => 'هالك نهائي',
            'review_difference' => 'فرق قيد يحتاج اعتماد',
            default => $this->settlement_type ?? '-',
        };
    }
}
