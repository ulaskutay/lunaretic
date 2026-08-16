<?php

namespace App\Etic\Media;

use Lunar\Base\StandardMediaDefinitions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EticMediaDefinitions extends StandardMediaDefinitions
{
    public function registerMediaConversions(HasMedia $model, ?Media $media = null): void
    {
        $model->addMediaConversion('small')
            ->fit(Fit::Max, 400, 400)
            ->keepOriginalImageFormat()
            ->deferred();
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => [2000, 2500],
            'large' => [1400, 1750],
            'medium' => [900, 1125],
        ];

        $collection->registerMediaConversions(function (Media $media) use ($model, $conversions) {
            foreach ($conversions as $key => [$width, $height]) {
                $model->addMediaConversion($key)
                    ->fit(Fit::Max, $width, $height)
                    ->keepOriginalImageFormat()
                    ->deferred();
            }
        });
    }
}
