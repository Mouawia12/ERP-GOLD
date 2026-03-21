<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['title', 'description'];
    protected $guarded = ['id'];
}
