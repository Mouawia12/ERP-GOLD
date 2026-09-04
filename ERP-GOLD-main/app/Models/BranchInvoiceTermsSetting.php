<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInvoiceTermsSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'templates',
        'default_template_keys',
    ];

    protected $casts = [
        'templates' => 'array',
        'default_template_keys' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
