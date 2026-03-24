<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['id'];
    protected string $guard_name = 'admin-web';

    protected static function booted(): void
    {
        static::saved(function (self $user) {
            if (blank($user->branch_id)) {
                return;
            }

            $branchId = (int) $user->branch_id;

            $user->branches()->syncWithoutDetaching([
                $branchId => [
                    'is_default' => true,
                    'is_active' => true,
                ],
            ]);

            $user->branches()
                ->newPivotStatement()
                ->where('user_id', $user->id)
                ->where('branch_id', '!=', $branchId)
                ->update([
                    'is_default' => false,
                ]);

            $user->branches()->updateExistingPivot($branchId, [
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    protected $hidden = [
        'password', 'remember_token'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id')
            ->withPivot(['is_default', 'is_active'])
            ->withTimestamps();
    }

    public function auditLogs()
    {
        return $this->hasMany(UserAuditLog::class, 'target_user_id');
    }

    public function performedAuditLogs()
    {
        return $this->hasMany(UserAuditLog::class, 'actor_user_id');
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function isOwner(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }

    public function isOperationalUser(): bool
    {
        return ! $this->isOwner();
    }

    public function belongsToSubscriber(): bool
    {
        return filled($this->subscriber_id);
    }

    protected function getDefaultGuardName(): string
    {
        return 'admin-web';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
