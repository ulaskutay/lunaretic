<?php

namespace App\Etic\Storefront\Http\Api;

use App\Etic\CMS\CmsPageLayout;
use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\MenuItem;
use App\Etic\CMS\Models\Page;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Integrations\Marketing\TrackingSettings;
use App\Etic\Integrations\Shipping\ShippingRates;
use App\Etic\Media\ProductImage;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CatalogQuery;
use App\Etic\Support\StoreContext;
use App\Etic\Theme\ActiveTheme;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Models\Brand;
use Lunar\Models\Cart;
use Lunar\Models\Collection;
use Lunar\Models\Order;
use Lunar\Models\Product;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;

class StorefrontPresenter
{
    public function __construct(
        private StoreContext $store,
        private CartManager $carts,
        private CatalogQuery $catalog,
        private TrackingDispatcher $tracking,
        private TrackingSettings $trackingSettings,
        private CmsPageLayout $pages,
    ) {}

    /** @return array<string, mixed> */
    public function bootstrap(): array
    {
        $ids = $this->trackingSettings->resolved();
        $theme = app(ActiveTheme::class)->toArray();
        $hotspots = collect($theme['shop_look']['hotspots'] ?? []);
        $selectedIds = $hotspots->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->values()->all();
        $hasSelectedProducts = $selectedIds !== [];
        $products = $this->catalog->selectedProducts($selectedIds);
        $productsById = $products->keyBy('id');
        $theme['shop_look']['hotspots'] = $hotspots
            ->map(function (array $hotspot, int $index) use ($hasSelectedProducts, $products, $productsById): array {
                $product = $hotspot['product_id']
                    ? $productsById->get($hotspot['product_id'])
                    : (! $hasSelectedProducts ? $products->get($index) : null);

                return [
                    ...$hotspot,
                    'product' => $product ? $this->productCard($product) : null,
                ];
            })
            ->filter(fn (array $hotspot): bool => $hotspot['product'] !== null)
            ->values()
            ->all();

        return [
            'store' => [
                'name' => $this->store->name(),
                'handle' => $this->store->handle(),
                'locale' => $this->store->locale(),
                'currency' => $this->store->currency()->code,
            ],
            'menus' => [
                'header' => $this->menu('header'),
                'footer' => $this->menu('footer'),
            ],
            'tracking' => [
                'ga4_measurement_id' => $ids['ga4_measurement_id'] ?? null,
                'gtm_container_id' => $ids['gtm_container_id'] ?? null,
                'meta_pixel_id' => $ids['meta_pixel_id'] ?? null,
                'search_console_verification' => $ids['search_console_verification'] ?? null,
            ],
            'theme' => $theme,
        ];
    }

    /** @return array<string, mixed> */
    public function productCard(Product $product): array
    {
        $variant = $product->variants->first();
        $price = $variant?->prices->first();

        return [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'slug' => $product->defaultUrl?->slug,
            'status' => $product->status,
            'image' => $this->absolute(ProductImage::url($product, 'medium')),
            'price' => $this->money($price?->priceIncTax()),
            'compare_price' => $this->comparePrice($price),
            'brand' => $product->brand?->name,
            'in_stock' => $product->variants->contains(
                fn (ProductVariant $item) => $item->canBeFulfilledAtQuantity(1)
            ),
            'color_variants' => $this->cardColorVariants($product),
        ];
    }

    /** @return array<string, mixed> */
    public function productDetail(Product $product, ?SupportCollection $colorVariants = null): array
    {
        $galleryItems = ProductImage::galleryItems($product)
            ->map(fn (array $item) => [
                'src' => $this->absolute($item['src']),
                'thumb' => $this->absolute($item['thumb']),
                'zoom' => $this->absolute($item['zoom']),
            ])
            ->all();

        return [
            ...$this->productCard($product),
            'description' => $product->translateAttribute('description'),
            'image' => $this->absolute(ProductImage::url($product, 'large')),
            'gallery' => array_values(array_filter(array_column($galleryItems, 'src'))),
            'gallery_items' => $galleryItems,
            'collections' => $product->collections
                ->map(fn (Collection $collection) => $this->collectionCard($collection))
                ->filter(fn (array $item) => filled($item['slug']))
                ->values()
                ->all(),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => $this->variant($variant))->values()->all(),
            'color_variants' => ($colorVariants ?? collect())->map(
                fn (Product $item) => $this->colorVariant($item, $product->id)
            )->filter(fn (array $item) => filled($item['slug']))->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function collectionCard(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->translateAttribute('name'),
            'slug' => $collection->defaultUrl?->slug,
        ];
    }

    /** @return array<string, mixed> */
    public function brand(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
        ];
    }

    /** @return array<string, mixed> */
    public function optionValue(ProductOptionValue $value): array
    {
        return [
            'id' => $value->id,
            'name' => $value->translate('name'),
        ];
    }

    /** @return array<string, mixed> */
    public function cart(Cart $cart): array
    {
        $cart->loadMissing([
            'lines.purchasable.product.media',
            'lines.purchasable.product.thumbnail',
            'lines.purchasable.product.defaultUrl',
            'lines.purchasable.values.option',
            'currency',
        ]);

        $subtotal = (int) ($cart->subTotal?->value ?? 0);
        $freeShipping = $this->freeShippingProgress($subtotal);

        return [
            'id' => $cart->id,
            'token' => $this->carts->token($cart),
            'coupon_code' => $cart->coupon_code,
            'lines' => $cart->lines->map(fn ($line) => [
                'id' => $line->id,
                'sku' => $line->purchasable?->sku,
                'quantity' => $line->quantity,
                'name' => $line->purchasable?->product
                    ? $line->purchasable->product->translateAttribute('name')
                    : $line->purchasable?->sku,
                'slug' => $line->purchasable?->product?->defaultUrl?->slug,
                'image' => $this->absolute(ProductImage::url($line->purchasable, 'medium')),
                'unit_price' => $this->money($line->unitPriceInclTax ?? $line->unitPrice),
                'total' => $this->money($line->total),
                'values' => $line->purchasable?->values
                    ? $line->purchasable->values->map(fn (ProductOptionValue $value) => [
                        'id' => $value->id,
                        'name' => $value->translate('name'),
                        'option' => $value->option?->handle,
                    ])->values()->all()
                    : [],
            ])->values()->all(),
            'subtotal' => $this->money($cart->subTotal),
            'discount_total' => $this->money($cart->discountTotal),
            'shipping_total' => $this->money($cart->shippingTotal),
            'tax_total' => $this->money($cart->taxTotal),
            'total' => $this->money($cart->total),
            'currency' => $cart->currency?->code ?? 'TRY',
            'free_shipping' => $freeShipping,
        ];
    }

    /** @return array<string, mixed> */
    public function shippingOption(ShippingOption $option): array
    {
        return [
            'identifier' => $option->getIdentifier(),
            'name' => $option->getName(),
            'description' => $option->getDescription(),
            'price' => $this->money($option->getPrice()),
        ];
    }

    /** @return array<string, mixed> */
    public function order(Order $order): array
    {
        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status,
            'status_label' => OrderStatusScenario::label((string) $order->status),
            'total' => $this->money($order->total),
            'currency' => $order->currency_code ?: 'TRY',
        ];
    }

    /** @return array<string, mixed> */
    public function page(Page $page): array
    {
        $presented = $this->pages->present($page);
        $presented['image'] = $this->absolute($presented['image'] ?? null);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'seo' => $this->seo($page->seo),
            ...$presented,
        ];
    }

    /** @return array<string, mixed> */
    public function blogPost(BlogPost $post, bool $full = false): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'author' => $post->author,
            'published_at' => $post->published_at?->toIso8601String(),
            'featured_image' => $this->absolute($post->featuredImageUrl()),
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'seo' => $this->seo($post->seo),
        ];

        if ($full) {
            $data['content'] = $post->content;
            $data['tags'] = $post->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()->all();
        }

        return $data;
    }

    public function paginated(LengthAwarePaginator $paginator, callable $map): array
    {
        return [
            'data' => $paginator->getCollection()->map($map)->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function trackingEvents(): array
    {
        return $this->tracking->dataLayer();
    }

    private function variant(ProductVariant $variant): array
    {
        $price = $variant->prices->first();

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'stock' => (int) $variant->stock,
            'purchasable' => $variant->canBeFulfilledAtQuantity(1),
            'price' => $this->money($price?->priceIncTax()),
            'compare_price' => $this->comparePrice($price),
            'values' => $variant->values->map(fn (ProductOptionValue $value) => [
                'id' => $value->id,
                'name' => $value->translate('name'),
                'option' => $value->option?->handle,
            ])->values()->all(),
        ];
    }

    private function cardColorVariants(Product $product): array
    {
        if (! $product->relationLoaded('colorVariantProducts') || $product->colorVariantProducts->count() < 2) {
            return [];
        }

        return $product->colorVariantProducts
            ->map(fn (Product $item) => $this->colorVariant($item, $product->id))
            ->filter(fn (array $item) => filled($item['slug']))
            ->values()
            ->all();
    }

    private function colorVariant(Product $product, int $activeProductId): array
    {
        $matched = $product->variants->first();
        $color = $matched?->values?->first(
            fn (ProductOptionValue $value) => $value->option?->handle === 'color'
        );

        return [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'slug' => $product->defaultUrl?->slug,
            'image' => $this->absolute(ProductImage::url($product, 'small')),
            'color' => $color?->translate('name'),
            'active' => $product->id === $activeProductId,
        ];
    }

    private function menu(string $handle): array
    {
        $menu = Menu::query()->forStore()->where('handle', $handle)->with('items.children.children')->first();

        return $menu ? $menu->items->map(fn (MenuItem $item) => $this->menuItem($item))->values()->all() : [];
    }

    private function menuItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'children' => $item->children->map(fn (MenuItem $child) => $this->menuItem($child))->values()->all(),
        ];
    }

    private function seo(mixed $seo): ?array
    {
        if (! $seo) {
            return null;
        }

        return [
            'title' => $seo->title,
            'description' => $seo->description,
            'robots' => $seo->robots,
            'og_title' => $seo->og_title,
            'og_description' => $seo->og_description,
            'og_image' => $this->absolute($seo->og_image),
        ];
    }

    private function comparePrice(mixed $price): ?array
    {
        if (! is_object($price) || (int) ($price->compare_price?->value ?? 0) <= 0) {
            return null;
        }

        return $this->money($price->comparePriceIncTax());
    }

    /** @return array{threshold: int|null, remaining: int, unlocked: bool} */
    private function freeShippingProgress(int $subtotal): array
    {
        $rates = app(ShippingRates::class)->all();
        $threshold = null;

        foreach ($rates as $index => $rate) {
            if ((int) ($rate['price'] ?? 0) !== 0) {
                continue;
            }

            $previous = $rates[$index - 1] ?? null;
            $threshold = is_array($previous) ? ($previous['max_subtotal'] ?? null) : 0;
            break;
        }

        $target = is_int($threshold) ? $threshold : null;

        return [
            'threshold' => $target,
            'remaining' => $target === null ? 0 : max(0, ($target + 1) - $subtotal),
            'unlocked' => $target === null || $subtotal > $target,
        ];
    }

    private function money(mixed $price): ?array
    {
        if (! $price instanceof Price && ! (is_object($price) && method_exists($price, 'formatted'))) {
            return null;
        }

        return [
            'value' => (int) $price->value,
            'formatted' => $price->formatted(),
        ];
    }

    private function absolute(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
