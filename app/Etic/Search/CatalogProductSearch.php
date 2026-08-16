<?php

namespace App\Etic\Search;

use App\Etic\Catalog\Models\Product;
use App\Etic\Storefront\StorefrontPaths;
use App\Etic\Support\StoreContext;
use Lunar\Search\Facades\Search;

class CatalogProductSearch
{
    public function __construct(private StoreContext $store) {}

    /**
     * @return list<int>|null Null when the search engine is disabled.
     */
    public function matchingProductIds(string $term, ?int $limit = null): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $perPage = $limit ?? (int) config('etic.search.max_results', 1000);

        $results = Search::model(Product::class)
            ->query($term)
            ->addFilter('status', 'published')
            ->perPage($perPage)
            ->get();

        return collect($results->hits)
            ->pluck('document.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, url: string, image: ?string, price: ?string}>
     */
    public function suggestions(string $term, int $limit = 6): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $ids = $this->matchingProductIds($term, $limit) ?? $this->fallbackProductIds($term, $limit);

        if ($ids === []) {
            return [];
        }

        $products = Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->whereKey($ids)
            ->with(['defaultUrl', 'thumbnail', 'variants.prices'])
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?array => $this->toSuggestion($products->get($id)))
            ->filter()
            ->values()
            ->all();
    }

    public function isEnabled(): bool
    {
        return config('scout.driver') === 'meilisearch';
    }

    /**
     * @return list<int>
     */
    private function fallbackProductIds(string $term, int $limit): array
    {
        $needle = mb_strtolower($term);

        return Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->with(['defaultUrl', 'variants'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->filter(function (Product $product) use ($needle): bool {
                $name = mb_strtolower((string) $product->translateAttribute('name'));

                if ($name !== '' && str_contains($name, $needle)) {
                    return true;
                }

                return $product->variants->contains(
                    fn ($variant): bool => str_contains(mb_strtolower((string) $variant->sku), $needle)
                );
            })
            ->take($limit)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function toSuggestion(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        $slug = $product->defaultUrl?->slug;

        if (! filled($slug)) {
            return null;
        }

        $variant = $product->variants->first();
        $price = $variant?->prices->first();

        return [
            'id' => $product->id,
            'name' => (string) $product->translateAttribute('name'),
            'url' => StorefrontPaths::product($slug),
            'image' => $product->thumbnail?->getUrl('small'),
            'price' => $price?->priceIncTax()->formatted(),
        ];
    }
}
