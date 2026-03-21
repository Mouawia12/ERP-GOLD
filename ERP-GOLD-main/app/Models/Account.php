<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['name'];
    protected $guarded = ['id'];
    protected $appends = ['opening_balance_object'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Account $account) {
            $expectedLevel = $account->parent ? intval($account->parent->level) + 1 : 1;
            $account->level = $expectedLevel;
            if (is_null($account->code)) {
                if (is_null($account->parent_account_id)) {
                    $countParentAccounts = Account::where('parent_account_id', NULL)->count();
                    $expectedNum = $countParentAccounts + 1;
                    $account->code = (new Account())->codePrefix($expectedNum, $expectedLevel);
                } else {
                    $countSiblingAccounts = Account::where('parent_account_id', $account->parent->id)->count();
                    $expectedNum = $countSiblingAccounts + 1;
                    $expectedCode = (new Account())->codePrefix($expectedNum, $expectedLevel);
                    $account->code = $account->parent->code . $expectedCode;
                }
            }
        });
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

    /*
     * Get account childs ids
     * notice this array contain account id and its childs ids
     *
     * @return Attribute
     */

    protected function childrensIds(): Attribute
    {
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
            ', [$this->id]))->pluck('id')->toArray();

        $ids = $childAccounts;

        return Attribute::make(
            get: fn() => $ids,
        );
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
