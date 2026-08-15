<?php

namespace App\Etic\Storefront;

use App\Etic\Support\StoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductVariant;
use Lunar\Models\Url;

class CatalogQuery
{
    public function __construct(private StoreContext $store) {}

    public function publishedProducts(?CatalogFilters $filters = null, ?Collection $collection = null): LengthAwarePaginator
    {
        $filters ??= new CatalogFilters;
        $productsTable = (new Product)->getTable();
        $variantsTable = (new ProductVariant)->getTable();
        $pricesTable = (new Price)->getTable();

        $query = Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->with(['variants.prices', 'defaultUrl', 'media', 'thumbnail', 'brand']);

        if ($collection) {
            $query->whereHas('collections', fn ($collections) => $collections->whereKey($collection->id));
        }

        if (filled($filters->search)) {
            $search = $filters->search;
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('urls', fn ($urls) => $urls->where('slug', 'like', '%'.$search.'%'));
            });
        }

        if ($filters->brand) {
            $query->where('brand_id', $filters->brand);
        }

        if ($filters->color || $filters->size || $filters->minPrice !== null || $filters->maxPrice !== null || $filters->inStock) {
            $query->whereHas('variants', function ($variants) use ($filters) {
                if ($filters->color) {
                    $variants->whereHas('values', fn ($values) => $values->whereKey($filters->color));
                }

                if ($filters->size) {
                    $variants->whereHas('values', fn ($values) => $values->whereKey($filters->size));
                }

                if ($filters->inStock) {
                    $variants->where('stock', '>', 0)->where('purchasable', 'in_stock');
                }

                if ($filters->minPrice !== null || $filters->maxPrice !== null) {
                    $variants->whereHas('prices', function ($prices) use ($filters) {
                        if ($filters->minPrice !== null) {
                            $prices->where('price', '>=', $filters->minPrice);
                        }

                        if ($filters->maxPrice !== null) {
                            $prices->where('price', '<=', $filters->maxPrice);
                        }
                    });
                }
            });
        }

        $lowestPrice = Price::query()
            ->select("{$pricesTable}.price")
            ->join($variantsTable, function ($join) use ($pricesTable, $variantsTable) {
                $join->on("{$pricesTable}.priceable_id", '=', "{$variantsTable}.id")
                    ->where("{$pricesTable}.priceable_type", ProductVariant::morphName());
            })
            ->whereColumn("{$variantsTable}.product_id", "{$productsTable}.id")
            ->orderBy("{$pricesTable}.price")
            ->limit(1);

        match ($filters->sort) {
            'name' => $query->orderBy('id'),
            'price_asc' => $query->orderBy($lowestPrice, 'asc'),
            'price_desc' => $query->orderBy($lowestPrice, 'desc'),
            default => $query->latest('id'),
        };

        return $query->paginate(12)->withQueryString();
    }

    public function facets(): array
    {
        return [
            'colors' => $this->optionValues('color'),
            'sizes' => $this->optionValues('size'),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function collections(): SupportCollection
    {
        return Collection::query()
            ->channel($this->store->channel())
            ->with('defaultUrl')
            ->get();
    }

    public function productBySlug(string $slug): Product
    {
        $url = Url::query()
            ->where('slug', $slug)
            ->where('element_type', Product::morphName())
            ->firstOrFail();

        return Product::query()
            ->channel($this->store->channel())
            ->with(['variants.prices', 'variants.values', 'collections', 'brand', 'associations', 'media', 'thumbnail'])
            ->findOrFail($url->element_id);
    }

    public function collectionBySlug(string $slug): Collection
    {
        $url = Url::query()
            ->where('slug', $slug)
            ->where('element_type', Collection::morphName())
            ->firstOrFail();

        return Collection::query()
            ->channel($this->store->channel())
            ->with('defaultUrl')
            ->findOrFail($url->element_id);
    }

    private function optionValues(string $handle): SupportCollection
    {
        $option = ProductOption::query()->where('handle', $handle)->with('values')->first();

        return $option?->values ?? collect();
    }
}
