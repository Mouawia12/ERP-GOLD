<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GoldCaratType extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function lines()
    {
        return $this->hasMany(InvoiceDetail::class, 'gold_carat_type_id', 'id');
    }

    public function getStock($qtyType = null)
    {
        if ($qtyType == null) {
            return $this->lines()->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->sum(DB::raw('(invoice_details.in_weight - invoice_details.out_weight) * gold_carats.transform_factor'));
        }
        return $this->lines()->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->sum(DB::raw($qtyType . '_weight * gold_carats.transform_factor'));
    }
}
