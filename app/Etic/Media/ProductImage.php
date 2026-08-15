<?php

namespace App\Etic\Media;

use Illuminate\Support\Collection;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImage
{
    public static function url(?object $model, string $conversion = 'large'): ?string
    {
        $media = self::media($model);

        if (! $media) {
            return null;
        }

        foreach ([$conversion, 'medium', 'small'] as $name) {
            if ($media->hasGeneratedConversion($name)) {
                return $media->getUrl($name);
            }
        }

        return $media->getUrl();
    }

    public static function gallery(?object $model): Collection
    {
        if (! $model instanceof HasMedia) {
            return collect();
        }

        $collection = (string) config('lunar.media.collection', 'images');
        $items = $model->getMedia($collection);

        if ($items->isEmpty()) {
            $items = $model->getMedia('default');
        }

        if ($items->isEmpty()) {
            $items = $model->getMedia();
        }

        return $items;
    }

    public static function media(?object $model): ?Media
    {
        if ($model instanceof ProductVariantContract) {
            return $model->getThumbnail()
                ?? self::media($model->product ?? null);
        }

        if (! $model instanceof HasMedia && ! $model instanceof ProductContract) {
            return null;
        }

        $thumbnail = $model->thumbnail ?? null;

        if ($thumbnail instanceof Media) {
            return $thumbnail;
        }

        return self::gallery($model)->first(
            fn (Media $media) => (bool) $media->getCustomProperty('primary')
        ) ?? self::gallery($model)->first();
    }
}
