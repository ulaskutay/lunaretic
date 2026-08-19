<?php

namespace App\Etic\Search;

use App\Etic\Catalog\Models\Product;
use App\Etic\Media\ProductImage;
use App\Etic\Storefront\StorefrontPaths;
use App\Etic\Support\StoreContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogProductSearch
{
    public const UNAVAILABLE_CACHE_KEY = 'etic:meilisearch:down';

    public function __construct(private StoreContext $store) {}

    /**
     * @return list<int>
     */
    public function matchingProductIds(string $term, ?int $limit = null): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $perPage = $limit ?? (int) config('etic.search.max_results', 250);

        if ($this->isEnabled() && ! Cache::get(self::UNAVAILABLE_CACHE_KEY)) {
            try {
                return $this->searchMeilisearch($term, $perPage);
            } catch (Throwable $exception) {
                Cache::put(self::UNAVAILABLE_CACHE_KEY, true, now()->addSeconds(30));
                Log::warning('Meilisearch unavailable, falling back to SQL search.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->fallbackProductIds($term, $perPage);
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

        $cacheKey = 'etic:search:suggest:'.$this->store->channelId().':'.mb_strtolower($term).':'.$limit;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $results = $this->suggestionHitsFromMeilisearch($term, $limit);

        if ($results === null) {
            $results = $this->buildSuggestions($term, $limit);
        }

        if ($results !== []) {
            Cache::put($cacheKey, $results, 20);
        }

        return $results;
    }

    /**
     * @return list<array{id: int, name: string, url: string, image: ?string, price: ?string}>|null
     */
    private function suggestionHitsFromMeilisearch(string $term, int $limit): ?array
    {
        if (! $this->isEnabled() || Cache::get(self::UNAVAILABLE_CACHE_KEY)) {
            return null;
        }

        try {
            $channelId = (string) $this->store->channelId();
            $filter = sprintf('status = "published" AND channel_ids = "%s"', addcslashes($channelId, '"\\'));

            $raw = Product::search($term, function ($index, string $query, array $options) use ($limit, $filter) {
                unset($options['hitsPerPage'], $options['page']);

                $options['filter'] = $filter;
                $options['limit'] = $limit;
                $options['attributesToRetrieve'] = ['id', 'name', 'url', 'thumbnail'];

                return $index->rawSearch($query, $options);
            })->raw();
        } catch (Throwable $exception) {
            Cache::put(self::UNAVAILABLE_CACHE_KEY, true, now()->addSeconds(30));
            Log::warning('Meilisearch unavailable, falling back to SQL search.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return collect($raw['hits'] ?? [])
            ->map(function (array $hit): ?array {
                $id = (int) ($hit['id'] ?? 0);
                $name = trim((string) ($hit['name'] ?? ''));
                $url = trim((string) ($hit['url'] ?? ''));

                if ($id < 1 || $name === '' || $url === '') {
                    return null;
                }

                return [
                    'id' => $id,
                    'name' => $name,
                    'url' => $url,
                    'image' => isset($hit['thumbnail']) ? (string) $hit['thumbnail'] : null,
                    'price' => null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, url: string, image: ?string, price: ?string}>
     */
    private function buildSuggestions(string $term, int $limit): array
    {
        $ids = $this->matchingProductIds($term, $limit);

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
    private function searchMeilisearch(string $term, int $limit): array
    {
        $channelId = (string) $this->store->channelId();
        $filter = sprintf('status = "published" AND channel_ids = "%s"', addcslashes($channelId, '"\\'));

        $raw = Product::search($term, function ($index, string $query, array $options) use ($limit, $filter) {
            unset($options['hitsPerPage'], $options['page']);

            $options['filter'] = $filter;
            $options['limit'] = $limit;
            $options['attributesToRetrieve'] = ['id'];

            return $index->rawSearch($query, $options);
        })->raw();

        return collect($raw['hits'] ?? [])
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function fallbackProductIds(string $term, int $limit): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->where(function ($query) use ($like, $term): void {
                $query->whereHas('urls', fn ($urls) => $urls->where('slug', 'like', $like))
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', $like));

                if (mb_strlen($term) >= 3) {
                    $query->orWhere('attribute_data', 'like', $like);
                }
            })
            ->latest('id')
            ->limit($limit)
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
            'image' => ProductImage::url($product, 'small'),
            'price' => $price?->priceIncTax()->formatted(),
        ];
    }
}
