<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'status' => 'boolean',
        'is_trial' => 'boolean',
        'max_users' => 'integer',
        'max_branches' => 'integer',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function isActiveForLogin(): bool
    {
        if (! $this->status) {
            return false;
        }

        if (blank($this->ends_at)) {
            return true;
        }

        return Carbon::parse($this->ends_at)->endOfDay()->greaterThanOrEqualTo(now());
    }

    public function activeUsersCount(): int
    {
        return $this->users()->where('status', true)->count();
    }

    public function activeBranchesCount(): int
    {
        return $this->branches()->where('status', true)->count();
    }
}
