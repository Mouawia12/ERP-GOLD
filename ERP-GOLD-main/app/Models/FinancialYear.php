<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['description'];
    protected $guarded = ['id'];

    public function openingBalances()
    {
        return $this->hasMany(OpeningBalance::class, 'financial_year');
    }
}
