<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'description'];

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->code = $item->generateCode();
        });
    }

    public function generateCode()
    {
        $lastItem = Item::orderBy('id', 'desc')->first();

        if ($lastItem) {
            $id = $lastItem->id;
        } else {
            $id = 0;
        }
        return str_pad($id + 1, 6, '0', STR_PAD_LEFT);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function goldCarat()
    {
        return $this->belongsTo(GoldCarat::class);
    }

    public function goldCaratType()
    {
        return $this->belongsTo(GoldCaratType::class);
    }

    public function defaultUnit()
    {
        return $this->hasOne(ItemUnit::class, 'item_id')->where('is_default', true);
    }

    public function units()
    {
        return $this->hasMany(ItemUnit::class, 'item_id')->where('is_default', false);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function getActualBalanceAttribute()
    {
        return $this->details()->selectRaw(DB::raw('SUM(in_weight - out_weight) as actual_balance'))->first()->actual_balance ?? 0;
    }
}
