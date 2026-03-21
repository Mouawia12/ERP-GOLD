<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GoldCarat extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title'];

    public function lines()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function getStock($from, $to, $caratTypeId, $qtyType = null)
    {
        if ($qtyType == null) {
            return $this->lines()->whereBetween('date', [$from, $to])->where('gold_carat_type_id', $caratTypeId)->sum(DB::raw('in_weight - out_weight'));
        }
        return $this->lines()->whereBetween('date', [$from, $to])->where('gold_carat_type_id', $caratTypeId)->sum($qtyType . '_weight');
    }

    public function getStockDependent($from, $to, $caratTypeId, $qtyType = null)
    {
        $total = 0;
        if ($qtyType == null) {
            $total = InvoiceDetail::join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->whereBetween('date', [$from, $to])->where('gold_carat_type_id', $caratTypeId)->sum(DB::raw('(invoice_details.in_weight - invoice_details.out_weight) * gold_carats.transform_factor'));
        } else {
            $total = InvoiceDetail::join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->whereBetween('date', [$from, $to])->where('gold_carat_type_id', $caratTypeId)->sum(DB::raw('invoice_details.' . $qtyType . '_weight * gold_carats.transform_factor'));
        }
        return $total;
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}
