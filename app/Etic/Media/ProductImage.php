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

        return $media ? self::mediaUrl($media, $conversion) : null;
    }

    public static function mediaUrl(Media $media, string $conversion = 'large'): string
    {
        foreach (self::conversionFallback($conversion) as $name) {
            if ($media->hasGeneratedConversion($name)) {
                return self::versionedUrl($media->getUrl($name), $media);
            }
        }

        return self::versionedUrl($media->getUrl(), $media);
    }

    public static function galleryUrls(?object $model, string $conversion = 'large'): Collection
    {
        return self::gallery($model)
            ->map(fn (Media $media) => self::mediaUrl($media, $conversion))
            ->values();
    }

    public static function galleryItems(?object $model): Collection
    {
        return self::gallery($model)
            ->map(fn (Media $media) => [
                'src' => self::mediaUrl($media, 'large'),
                'thumb' => self::mediaUrl($media, 'small'),
                'zoom' => self::mediaUrl($media, 'zoom'),
            ])
            ->values();
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

    /** @return list<string> */
    private static function conversionFallback(string $conversion): array
    {
        return match ($conversion) {
            'original' => [],
            'small' => ['small', 'medium'],
            'medium' => ['medium', 'large', 'zoom'],
            'zoom' => ['zoom', 'large'],
            default => ['large', 'zoom', 'medium'],
        };
    }

    private static function versionedUrl(string $url, Media $media): string
    {
        $version = (string) ($media->updated_at?->timestamp ?: $media->id);

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
    }
}
