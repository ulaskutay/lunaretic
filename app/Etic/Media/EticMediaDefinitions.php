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
            ->fit(Fit::Max, 300, 300)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => [500, 500],
            'large' => [800, 800],
            'medium' => [500, 500],
        ];

        $collection->registerMediaConversions(function (Media $media) use ($model, $conversions) {
            foreach ($conversions as $key => [$width, $height]) {
                $model->addMediaConversion($key)
                    ->fit(Fit::Max, $width, $height)
                    ->keepOriginalImageFormat()
                    ->nonQueued();
            }
        });
    }
}
