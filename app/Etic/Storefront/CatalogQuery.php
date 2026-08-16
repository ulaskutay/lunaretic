<?php

namespace App\Etic\Storefront;

use App\Etic\Support\StoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
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
        $ordersTable = (new Order)->getTable();
        $orderLinesTable = (new OrderLine)->getTable();

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

        $inStockOnly = $filters->inStock && ! $filters->outOfStock;
        $outOfStockOnly = $filters->outOfStock && ! $filters->inStock;
        $hasVariantConstraints = $filters->color
            || $filters->size
            || $filters->minPrice !== null
            || $filters->maxPrice !== null
            || $inStockOnly;

        if ($hasVariantConstraints) {
            $query->whereHas('variants', function ($variants) use ($filters, $inStockOnly) {
                $this->constrainVariants($variants, $filters, $inStockOnly);
            });
        }

        if ($outOfStockOnly) {
            $query->whereDoesntHave('variants', function ($variants) use ($filters) {
                $this->constrainVariants($variants, $filters, true);
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

        $salesCount = OrderLine::query()
            ->selectRaw("COALESCE(SUM({$orderLinesTable}.quantity), 0)")
            ->join($ordersTable, "{$ordersTable}.id", '=', "{$orderLinesTable}.order_id")
            ->join($variantsTable, function ($join) use ($orderLinesTable, $variantsTable) {
                $join->on("{$variantsTable}.id", '=', "{$orderLinesTable}.purchasable_id")
                    ->where("{$orderLinesTable}.purchasable_type", ProductVariant::morphName());
            })
            ->whereColumn("{$variantsTable}.product_id", "{$productsTable}.id")
            ->where("{$ordersTable}.channel_id", $this->store->channel()->id)
            ->whereNotNull("{$ordersTable}.placed_at")
            ->where("{$ordersTable}.status", '!=', 'cancelled');

        $outOfStockLast = ProductVariant::query()
            ->selectRaw('case when count(*) > 0 then 0 else 1 end')
            ->whereColumn("{$variantsTable}.product_id", "{$productsTable}.id")
            ->where("{$variantsTable}.stock", '>', 0)
            ->where("{$variantsTable}.purchasable", 'in_stock');

        $query->orderBy($outOfStockLast);

        match ($filters->sort) {
            'name' => $query->orderBy('id'),
            'price_asc' => $query->orderBy($lowestPrice, 'asc'),
            'price_desc' => $query->orderBy($lowestPrice, 'desc'),
            'best_selling' => $query->orderBy($salesCount, 'desc')->latest("{$productsTable}.id"),
            default => $query->latest('id'),
        };

        $paginator = $query->paginate(12)->withQueryString();
        $this->hydrateColorVariants($paginator->getCollection());

        return $paginator;
    }

    /**
     * @param  list<int>  $ids
     * @return SupportCollection<int, Product>
     */
    public function selectedProducts(array $ids, int $fallbackLimit = 4): SupportCollection
    {
        $ids = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $query = Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->with(['variants.prices', 'defaultUrl', 'media', 'thumbnail', 'brand']);

        if ($ids->isEmpty()) {
            $products = $query->latest('id')->limit($fallbackLimit)->get();
            $this->hydrateColorVariants($products);

            return $products;
        }

        $products = $query->whereKey($ids)->get()->keyBy('id');
        $ordered = $ids
            ->map(fn (int $id): ?Product => $products->get($id))
            ->filter()
            ->values();
        $this->hydrateColorVariants($ordered);

        return $ordered;
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
            ->with(['variants.prices', 'variants.values.option', 'collections.defaultUrl', 'brand', 'associations', 'media', 'thumbnail'])
            ->findOrFail($url->element_id);
    }

    public function colorVariantProducts(Product $product): SupportCollection
    {
        $modelCode = trim((string) $product->model_code);

        if ($modelCode === '') {
            return collect([$product]);
        }

        $products = Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->where('model_code', $modelCode)
            ->with(['variants.values.option', 'defaultUrl', 'media', 'thumbnail'])
            ->get();

        if ($products->isEmpty()) {
            return collect([$product]);
        }

        return $products
            ->sortBy(fn (Product $item) => $item->id === $product->id ? 0 : 1)
            ->values();
    }

    /**
     * @param  iterable<int, Product>  $products
     */
    private function hydrateColorVariants(iterable $products): void
    {
        $products = collect($products)->filter();
        $codes = $products
            ->map(fn (Product $product): string => trim((string) $product->model_code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            $products->each(fn (Product $product) => $product->setRelation('colorVariantProducts', collect()));

            return;
        }

        $siblings = Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->whereIn('model_code', $codes->all())
            ->with(['variants.values.option', 'defaultUrl', 'media', 'thumbnail'])
            ->get()
            ->groupBy(fn (Product $item): string => trim((string) $item->model_code));

        foreach ($products as $product) {
            $modelCode = trim((string) $product->model_code);
            $group = $modelCode === ''
                ? collect()
                : ($siblings->get($modelCode) ?? collect())->values();

            $product->setRelation(
                'colorVariantProducts',
                $group->count() > 1 ? $group : collect()
            );
        }
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

    private function constrainVariants(Builder $variants, CatalogFilters $filters, bool $inStock): void
    {
        if ($filters->color) {
            $variants->whereHas('values', fn ($values) => $values->whereKey($filters->color));
        }

        if ($filters->size) {
            $variants->whereHas('values', fn ($values) => $values->whereKey($filters->size));
        }

        if ($inStock) {
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
    }

    private function optionValues(string $handle): SupportCollection
    {
        $option = ProductOption::query()->where('handle', $handle)->with('values')->first();

        return $option?->values ?? collect();
    }
}
