<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSubscriberScope;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;
    use HasTranslations;
    use BelongsToSubscriberScope;

    public $translatable = ['name'];
    protected $guarded = ['id'];
    protected $appends = ['opening_balance_object'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Account $account) {
            $account->level = $account->parent ? intval($account->parent->level) + 1 : 1;
            if (is_null($account->code)) {
                $account->code = self::nextCodeFor($account->parent);
            }
        });
    }

    /**
     * Compute the next available code for a new account under the given parent.
     *
     * Sequence is derived from the largest existing sibling suffix (not the sibling
     * count) so that deleting an account never causes a later account to reuse an
     * existing code. A uniqueness guard is kept as a final safety net.
     */
    public static function nextCodeFor(?self $parent): string
    {
        $level = $parent ? intval($parent->level) + 1 : 1;
        $prefix = $parent?->code ?? '';
        $prefixLength = strlen($prefix);

        $siblingCodes = self::query()
            ->where('parent_account_id', $parent?->id)
            ->pluck('code')
            ->filter()
            ->all();

        $maxSeq = 0;
        foreach ($siblingCodes as $siblingCode) {
            $suffix = $prefixLength > 0 ? substr($siblingCode, $prefixLength) : $siblingCode;
            if ($suffix !== '' && ctype_digit($suffix)) {
                $maxSeq = max($maxSeq, (int) $suffix);
            }
        }

        $nextSeq = max($maxSeq, count($siblingCodes)) + 1;
        $helper = new self();

        do {
            $code = $prefix . $helper->codePrefix($nextSeq, $level);
            $exists = self::query()->where('code', $code)->exists();
            $nextSeq++;
        } while ($exists);

        return $code;
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'account_branch');
    }

    /**
     * An account is visible for the given branches when it is general (not linked
     * to any branch) or linked to at least one of the selected branches.
     *
     * @param  array<int>  $branchIds
     */
    public function scopeVisibleForBranches($query, array $branchIds)
    {
        if ($branchIds === []) {
            return $query;
        }

        return $query->where(function ($builder) use ($branchIds) {
            $builder->whereDoesntHave('branches')
                ->orWhereHas('branches', fn ($b) => $b->whereIn('branches.id', $branchIds));
        });
    }

    /**
     * Map of account_id => [branch_ids] for every account that is linked to one or
     * more branches. Accounts absent from this map are general (all branches).
     *
     * @return array<int, array<int>>
     */
    public static function branchAssignmentMap(): array
    {
        return DB::table('account_branch')
            ->select('account_id', 'branch_id')
            ->get()
            ->groupBy('account_id')
            ->map(fn ($rows) => $rows->pluck('branch_id')->map(fn ($id) => (int) $id)->values()->all())
            ->all();
    }

    public function getNameAttribute($value): string
    {
        $locale = app()->getLocale();
        if (is_array($value)) {
            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? '';
        }
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? $value;
            }
        }
        return (string) ($value ?? '');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function codePrefix($number, $level)
    {
        return str_pad($number, $level - 1, '0', STR_PAD_LEFT);
    }

    public function childrens()
    {
        return $this->hasMany($this, 'parent_account_id');
    }

    public function childrensRecursive()
    {
        return $this->childrens()
            ->orderBy('code')
            ->with('childrensRecursive');
    }

    /*
     * Get account childs ids
     * notice this array contain account id and its childs ids
     *
     * @return Attribute
     */

    protected function childrensIds(): Attribute
    {
        $accountId = $this->attributes[$this->getKeyName()] ?? null;

        if (blank($accountId)) {
            return Attribute::make(
                get: fn() => [],
            )->shouldCache();
        }

        $childAccounts = collect(DB::connection($this->connection)->select('
             WITH RECURSIVE AccountTree AS (
                 SELECT 
                     id,
                     name,
                     parent_account_id
                 FROM 
                     accounts
                 WHERE 
                     id = ?
 
                 UNION ALL
 
                 SELECT 
                     a.id,
                     a.name,
                     a.parent_account_id
                 FROM 
                     accounts a
                 INNER JOIN 
                     AccountTree at ON a.parent_account_id = at.id
                 )
                 SELECT 
                     id
                 FROM 
                     AccountTree;
            ', [$accountId]))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $ids = $childAccounts;

        return Attribute::make(
            get: fn() => $ids,
        )->shouldCache();
    }

    public function documents()
    {
        return $this->hasMany(JournalEntryDocument::class);
    }

    public function openingBalance($periodFrom, $periodTo, $type = null)
    {
        $openingBalance = OpeningBalance::whereIn('account_id', $this->childrensIds)->selectRaw('SUM(debit) as debit, SUM(credit) as credit')->first();
        $total = 0;
        if ($openingBalance) {
            if ($type == 'debit') {
                $total = $openingBalance->debit;
            } elseif ($type == 'credit') {
                $total = $openingBalance->credit;
            } else {
                $total = $openingBalance->debit - $openingBalance->credit;
            }
        }

        if ($periodFrom) {
            $query = JournalEntryDocument::whereIn('account_id', $this->childrensIds)->where('document_date', '<', $periodFrom);
            if ($type == 'debit') {
                $total += $query->sum('debit');
            } elseif ($type == 'credit') {
                $total += $query->sum('credit');
            } else {
                $total += $query->sum(DB::raw('debit - credit'));
            }
        }
        return $total;
    }

    public function currentTransaction($periodFrom, $periodTo, $type = null)
    {
        $query = JournalEntryDocument::whereIn('account_id', $this->childrensIds)->whereBetween('document_date', [$periodFrom, $periodTo]);
        $total = 0;
        if ($type == 'debit') {
            $total += $query->sum('debit');
        } elseif ($type == 'credit') {
            $total += $query->sum('credit');
        } else {
            $total += $query->sum(DB::raw('debit - credit'));
        }

        return $total;
    }

    public function closingBalance($periodFrom, $periodTo, $type = null)
    {
        return $this->openingBalance($periodFrom, $periodTo, $type) + $this->currentTransaction($periodFrom, $periodTo, $type);
    }

    public function openingBalanceRelation()
    {
        return $this->hasOne(OpeningBalance::class);
    }

    public function getOpeningBalanceObjectAttribute()
    {
        return [
            'debit' => $this->openingBalanceRelation?->debit ?? 0,
            'credit' => $this->openingBalanceRelation?->credit ?? 0,
        ];
    }
}
