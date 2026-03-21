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
}
