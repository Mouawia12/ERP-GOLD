<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvoiceTermsSetting extends Model
{
    protected $fillable = [
        'user_id',
        'templates',
        'default_template_keys',
    ];

    protected $casts = [
        'templates' => 'array',
        'default_template_keys' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
