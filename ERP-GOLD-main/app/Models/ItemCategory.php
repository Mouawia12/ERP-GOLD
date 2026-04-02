<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSubscriberScope;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    use BelongsToSubscriberScope;

    protected $translatable = ['title', 'description'];
    protected $guarded = ['id'];

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }
}
