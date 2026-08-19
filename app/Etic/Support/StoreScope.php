<?php

namespace App\Etic\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(StoreContext::class);

        if ($context->isolationBypassed()) {
            return;
        }

        $storeId = $context->store()?->id;

        if (! $storeId) {
            return;
        }

        $builder->where($model->getTable().'.store_id', $storeId);
    }
}
