<?php

namespace App\Etic\SEO;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Model;

class CanonicalUrl
{
    public function __construct(private StoreContext $store) {}

    public function forPath(string $path): string
    {
        return $this->store->primaryUrl().'/'.ltrim($path, '/');
    }

    public function forModel(Model $model, string $path): string
    {
        $custom = $model->seo?->canonical_url;

        if (filled($custom)) {
            return $custom;
        }

        return $this->forPath($path);
    }
}
