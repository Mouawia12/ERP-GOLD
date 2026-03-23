<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function carat()
    {
        return $this->belongsTo(GoldCarat::class, 'gold_carat_id', 'id');
    }

    public function goldCaratType()
    {
        return $this->belongsTo(GoldCaratType::class, 'gold_carat_type_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(ItemUnit::class, 'unit_id', 'id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'unit_tax_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function getRoundNetTotalAttribute()
    {
        $taxesTotal = round($this->line_tax, 2);
        $linesTotalAfterDiscount = round($this->line_total, 2);
        return $linesTotalAfterDiscount + $taxesTotal;
    }

    public function getCaratDisplayLabelAttribute(): string
    {
        if ($this->carat?->title) {
            return $this->carat->title;
        }

        return $this->item?->inventory_classification_label ?? 'غير محدد';
    }

    public function getSettlementActualWeightValueAttribute(): float
    {
        return round((float) ($this->stock_actual_weight ?? 0), 3);
    }

    public function getSettlementCountedWeightValueAttribute(): float
    {
        if ($this->stock_counted_weight !== null) {
            return round((float) $this->stock_counted_weight, 3);
        }

        $actualWeight = round((float) ($this->stock_actual_weight ?? 0), 3);
        $diffWeight = $this->stock_diff_weight !== null
            ? round((float) $this->stock_diff_weight, 3)
            : round((float) ($this->in_weight - $this->out_weight), 3);

        return round($actualWeight + $diffWeight, 3);
    }

    public function getSettlementDiffWeightValueAttribute(): float
    {
        if ($this->stock_diff_weight !== null) {
            return round((float) $this->stock_diff_weight, 3);
        }

        return round((float) ($this->in_weight - $this->out_weight), 3);
    }

    public function getSettlementDiffDirectionLabelAttribute(): string
    {
        $diffWeight = $this->stock_diff_weight !== null
            ? round((float) $this->stock_diff_weight, 3)
            : round((float) ($this->in_weight - $this->out_weight), 3);

        if ($diffWeight > 0) {
            return 'زيادة';
        }

        if ($diffWeight < 0) {
            return 'عجز';
        }

        return 'مطابق';
    }
}
