<?php

namespace App\Etic\Storefront;

use App\Etic\Support\StoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lunar\Models\Collection;
use Lunar\Models\Product;
use Lunar\Models\Url;

class CatalogQuery
{
    public function __construct(private StoreContext $store) {}

    public function publishedProducts(?string $search = null, string $sort = 'newest'): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('status', 'published')
            ->with(['variants.prices', 'defaultUrl', 'media', 'thumbnail']);

        if (filled($search)) {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('urls', fn ($urls) => $urls->where('slug', 'like', '%'.$search.'%'));
            });
        }

        match ($sort) {
            'name' => $query->orderBy('id'),
            default => $query->latest('id'),
        };

        return $query->paginate(12);
    }

    public function productBySlug(string $slug): Product
    {
        $url = Url::query()
            ->where('slug', $slug)
            ->where('element_type', Product::morphName())
            ->firstOrFail();

        return Product::query()
            ->with(['variants.prices', 'variants.values', 'collections', 'brand', 'associations', 'media', 'thumbnail'])
            ->findOrFail($url->element_id);
    }

    public function collectionBySlug(string $slug): Collection
    {
        $url = Url::query()
            ->where('slug', $slug)
            ->where('element_type', Collection::morphName())
            ->firstOrFail();

        return Collection::query()->findOrFail($url->element_id);
    }
}
