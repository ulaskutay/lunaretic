<?php

namespace App\Etic\Support\Concerns;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToChannel
{
    public function scopeForStore(Builder $query, ?StoreContext $store = null): Builder
    {
        $channelId = ($store ?? app(StoreContext::class))->channelId();

        return $query->where($this->getTable().'.channel_id', $channelId);
    }

    public static function bootBelongsToChannel(): void
    {
        static::addGlobalScope('etic_store', function (Builder $query): void {
            $context = app(StoreContext::class);

            if ($context->isolationBypassed()) {
                return;
            }

            $channelId = $context->channelId();

            if ($channelId) {
                $query->where($query->getModel()->getTable().'.channel_id', $channelId);
            }
        });

        static::creating(function ($model): void {
            if (blank($model->channel_id)) {
                $model->channel_id = app(StoreContext::class)->channelId();
            }
        });
    }
}
