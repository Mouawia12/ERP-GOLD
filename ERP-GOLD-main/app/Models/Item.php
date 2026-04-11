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

    public const CLASSIFICATION_GOLD = 'gold';
    public const CLASSIFICATION_COLLECTIBLE = 'collectible';
    public const CLASSIFICATION_SILVER = 'silver';
    public const SALE_MODE_SINGLE = 'single';
    public const SALE_MODE_REPEATABLE = 'repeatable';

    public $translatable = ['title', 'description'];

    protected $guarded = ['id'];

    public static function inventoryClassificationOptions(): array
    {
        return [
            self::CLASSIFICATION_GOLD => 'ذهب',
            self::CLASSIFICATION_COLLECTIBLE => 'مقتنيات',
            self::CLASSIFICATION_SILVER => 'فضة',
        ];
    }

    public static function saleModeOptions(): array
    {
        return [
            self::SALE_MODE_SINGLE => 'يباع مرة واحدة',
            self::SALE_MODE_REPEATABLE => 'يباع أكثر من مرة',
        ];
    }

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

    public function branchPublications()
    {
        return $this->hasMany(BranchItem::class, 'item_id');
    }

    public function publishedBranches()
    {
        return $this->belongsToMany(Branch::class, 'branch_items', 'item_id', 'branch_id')
            ->withPivot(['is_active', 'is_visible', 'sale_price_per_gram', 'published_by_user_id'])
            ->withTimestamps();
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function getInventoryClassificationLabelAttribute(): string
    {
        return static::inventoryClassificationOptions()[$this->inventory_classification] ?? 'غير محدد';
    }

    public function getSaleModeLabelAttribute(): string
    {
        return static::saleModeOptions()[$this->sale_mode] ?? 'غير محدد';
    }

    public function scopePublishedToBranch($query, int $branchId, bool $visibleOnly = true)
    {
        return $query->whereHas('branchPublications', function ($publicationQuery) use ($branchId, $visibleOnly) {
            $publicationQuery->where('branch_id', $branchId)->where('is_active', true);

            if ($visibleOnly) {
                $publicationQuery->where('is_visible', true);
            }
        });
    }

    public function publicationForBranch(?int $branchId): ?BranchItem
    {
        if (empty($branchId)) {
            return null;
        }

        if ($this->relationLoaded('branchPublications')) {
            return $this->branchPublications->firstWhere('branch_id', $branchId);
        }

        return $this->branchPublications()->where('branch_id', $branchId)->first();
    }

    public function getActualBalanceAttribute()
    {
        return $this->details()->selectRaw(DB::raw('SUM(in_weight - out_weight) as actual_balance'))->first()->actual_balance ?? 0;
    }
}
