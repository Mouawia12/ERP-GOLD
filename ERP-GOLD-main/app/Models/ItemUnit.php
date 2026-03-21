<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemUnit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($itemUnit) {
            $result = str_replace('.', '', $itemUnit->weight);
            $weightInt = str_pad((string) ($result), 4, '0', STR_PAD_LEFT);
            $barcode = $itemUnit->item->code . $weightInt;
            $itemUnit->barcode = $barcode;
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getGramPriceAttribute()
    {
        $laborCost = $this->item->labor_cost_per_gram ?? 0;
        $profitMargin = $this->item->profit_margin_per_gram ?? 0;
        $averageCostPerGram = $this->item->defaultUnit->average_cost_per_gram ?? 0;
        return $averageCostPerGram + $laborCost + $profitMargin;
    }
}
