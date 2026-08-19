<?php

namespace App\Etic\Media;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Lunar\Models\Channel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class StoreMediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->prefix($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->prefix($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->prefix($media).'/responsive/';
    }

    public function storeHandle(Media $media): string
    {
        return $this->handleFromOwner($media)
            ?: $this->stampedHandle($media)
            ?: (app(StoreContext::class)->handle() ?: 'platform');
    }

    private function prefix(Media $media): string
    {
        return 'stores/'.$this->storeHandle($media).'/'.$media->getKey();
    }

    private function stampedHandle(Media $media): ?string
    {
        $handle = $media->getCustomProperty('store_handle');

        return is_string($handle) && $handle !== '' ? $handle : null;
    }

    private function handleFromOwner(Media $media): ?string
    {
        return app(StoreContext::class)->withoutIsolation(function () use ($media): ?string {
            $owner = $media->model()->withoutGlobalScopes()->first();

            if (! $owner) {
                return null;
            }

            if (isset($owner->store_id) && $owner->store_id) {
                $handle = Store::query()->whereKey($owner->store_id)->value('handle');

                if (is_string($handle) && $handle !== '') {
                    return $handle;
                }
            }

            if (method_exists($owner, 'channels')) {
                $handle = $owner->channels()->wherePivot('enabled', true)->value('handle')
                    ?? $owner->channels()->value('handle');

                if (is_string($handle) && $handle !== '') {
                    return $handle;
                }
            }

            if (isset($owner->channel_id) && $owner->channel_id) {
                $handle = Channel::query()->whereKey($owner->channel_id)->value('handle');

                if (is_string($handle) && $handle !== '') {
                    return $handle;
                }
            }

            return null;
        });
    }
}
