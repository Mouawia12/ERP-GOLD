<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSubscriberScope
{
    protected static function bootBelongsToSubscriberScope(): void
    {
        static::addGlobalScope('subscriber', function (Builder $builder) {
            $subscriberId = static::currentSubscriberIdForScope();

            if (! $subscriberId) {
                return;
            }

            $builder->where($builder->qualifyColumn('subscriber_id'), $subscriberId);
        });

        static::creating(function ($model) {
            if (filled($model->subscriber_id)) {
                return;
            }

            $subscriberId = static::currentSubscriberIdForScope();

            if ($subscriberId) {
                $model->subscriber_id = $subscriberId;
            }
        });
    }

    protected static function currentSubscriberIdForScope(): ?int
    {
        $user = auth('admin-web')->user();

        if (! $user || blank($user->subscriber_id)) {
            return null;
        }

        return (int) $user->subscriber_id;
    }
}
