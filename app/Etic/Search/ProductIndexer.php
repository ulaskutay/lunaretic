<?php

namespace App\Etic\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Search\ProductIndexer as LunarProductIndexer;

class ProductIndexer extends LunarProductIndexer
{
    public function getFilterableFields(): array
    {
        return [
            ...parent::getFilterableFields(),
            'channel_ids',
            'brand_id',
        ];
    }

    public function shouldBeSearchable(Model $model): bool
    {
        return $model->status === 'published';
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return parent::makeAllSearchableUsing($query)->with('channels');
    }

    public function toSearchableArray(Model $model): array
    {
        $data = parent::toSearchableArray($model);

        $data['channel_ids'] = $model->channels
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        if ($model->brand_id) {
            $data['brand_id'] = (string) $model->brand_id;
        }

        return $data;
    }
}
