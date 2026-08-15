<?php

namespace App\Etic\Support\Concerns;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToChannel
{
    public function scopeForStore(Builder $query, ?StoreContext $store = null): Builder
    {
        $channelId = ($store ?? app(StoreContext::class))->channelId();

        return $query->where('channel_id', $channelId);
    }

    public static function bootBelongsToChannel(): void
    {
        static::creating(function ($model): void {
            if (blank($model->channel_id)) {
                $model->channel_id = app(StoreContext::class)->channelId();
            }
        });
    }
}
