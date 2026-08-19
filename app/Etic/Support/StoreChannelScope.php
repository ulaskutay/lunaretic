<?php

namespace App\Etic\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Lunar\Models\Order;

class StoreChannelScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(StoreContext::class);

        if ($context->isolationBypassed()) {
            return;
        }

        $channelId = $context->channelId();

        if (! $channelId) {
            return;
        }

        if ($model instanceof Order || $builder->getModel() instanceof Order) {
            $builder->where($model->getTable().'.channel_id', $channelId);

            return;
        }

        if (method_exists($model, 'channels')) {
            $prefix = (string) config('lunar.database.table_prefix', 'lunar_');
            $channelables = $prefix.'channelables';

            $builder->whereExists(function ($query) use ($model, $channelId, $channelables): void {
                $query->selectRaw('1')
                    ->from($channelables)
                    ->whereColumn($channelables.'.channelable_id', $model->getQualifiedKeyName())
                    ->where($channelables.'.channelable_type', $model->getMorphClass())
                    ->where($channelables.'.channel_id', $channelId)
                    ->where($channelables.'.enabled', true);
            });
        }
    }
}
