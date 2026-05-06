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
        'a4_margin_top', 'a4_margin_right', 'a4_margin_bottom', 'a4_margin_left',
        'a4_header_height', 'a4_footer_height', 'a4_content_offset_top',
        'a5_margin_top', 'a5_margin_right', 'a5_margin_bottom', 'a5_margin_left',
        'a5_header_height', 'a5_footer_height', 'a5_content_offset_top',
        'font_scale',
    ];

    protected $casts = [
        'show_header' => 'boolean',
        'show_footer' => 'boolean',
        'a4_margin_top' => 'float', 'a4_margin_right' => 'float',
        'a4_margin_bottom' => 'float', 'a4_margin_left' => 'float',
        'a4_header_height' => 'float', 'a4_footer_height' => 'float',
        'a4_content_offset_top' => 'float',
        'a5_margin_top' => 'float', 'a5_margin_right' => 'float',
        'a5_margin_bottom' => 'float', 'a5_margin_left' => 'float',
        'a5_header_height' => 'float', 'a5_footer_height' => 'float',
        'a5_content_offset_top' => 'float',
        'font_scale' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
