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
            $model->addMediaConversion('small')->fit(Fit::Max, 800, 1000),
            $media
        );
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => [2400, 3000],
            'large' => [2000, 2500],
            'medium' => [1400, 1750],
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
        $quality = $conversion->getName() === 'zoom' ? 85 : 80;

        $conversion->quality($quality)->deferred();

        $mime = strtolower((string) ($media?->mime_type ?? ''));

        if (str_contains($mime, 'gif')) {
            $conversion->keepOriginalImageFormat();

            return;
        }

        $conversion->format('webp');
    }
}
