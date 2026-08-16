<?php

namespace App\Etic\Media;

use Lunar\Base\StandardMediaDefinitions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EticMediaDefinitions extends StandardMediaDefinitions
{
    public function registerMediaConversions(HasMedia $model, ?Media $media = null): void
    {
        $this->configureConversion(
            $model->addMediaConversion('small')->fit(Fit::Max, 480, 600),
            $media
        );
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => [1600, 2000],
            'large' => [1100, 1400],
            'medium' => [800, 1000],
        ];

        $collection->registerMediaConversions(function (Media $media) use ($model, $conversions) {
            foreach ($conversions as $key => [$width, $height]) {
                $this->configureConversion(
                    $model->addMediaConversion($key)->fit(Fit::Max, $width, $height),
                    $media
                );
            }
        });
    }

    private function configureConversion(Conversion $conversion, ?Media $media): void
    {
        $conversion->quality(78)->deferred();

        $mime = (string) ($media?->mime_type ?? '');

        if (str_contains($mime, 'png') || str_contains($mime, 'gif') || str_contains($mime, 'webp')) {
            $conversion->keepOriginalImageFormat();

            return;
        }

        $conversion->format('jpg');
    }
}
