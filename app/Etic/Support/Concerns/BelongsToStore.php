<?php

namespace App\Etic\Support\Concerns;

use App\Etic\Support\StoreContext;
use App\Etic\Support\StoreScope;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope('etic_store', new StoreScope);

        static::creating(function ($model): void {
            if (blank($model->store_id)) {
                $model->store_id = app(StoreContext::class)->store()?->id;
            }
        });
    }
}
