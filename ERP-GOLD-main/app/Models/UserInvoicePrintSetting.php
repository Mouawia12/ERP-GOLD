<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvoicePrintSetting extends Model
{
    protected $fillable = [
        'user_id',
        'format',
        'show_header',
        'show_footer',
        'template',
        'orientation',
    ];

    protected $casts = [
        'show_header' => 'boolean',
        'show_footer' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
