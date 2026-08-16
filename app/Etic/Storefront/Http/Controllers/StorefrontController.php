<?php

namespace App\Etic\Storefront\Http\Controllers;

use App\Etic\CMS\CmsPageLayout;
use App\Etic\CMS\Models\BlogCategory;
use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\Page;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Media\ProductImage;
use App\Etic\SEO\CanonicalUrl;
use App\Etic\SEO\SchemaBuilder;
use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CatalogFilters;
use App\Etic\Storefront\CatalogQuery;
use App\Etic\Storefront\CheckoutPayload;
use App\Etic\Storefront\CheckoutService;
use App\Etic\Storefront\StorefrontPaths;
use App\Etic\Theme\ActiveTheme;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Product;
use Lunar\Models\ProductOptionValue;
use RuntimeException;

class StorefrontController
{
    public function home(CatalogQuery $catalog, TrackingDispatcher $tracking, SchemaBuilder $schema, CanonicalUrl $canonical, ActiveTheme $theme): View
    {
        $products = $catalog->publishedProducts();
        $bestSellerProducts = $catalog->publishedProducts(new CatalogFilters(sort: 'best_selling'))->take(8);
        $hotspots = collect($theme->shopLookHotspots());
        $selectedIds = $hotspots->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->values()->all();
        $hasSelectedProducts = $selectedIds !== [];
        $shopLookProducts = $catalog->selectedProducts($selectedIds);
        $shopLookProductsById = $shopLookProducts->keyBy('id');
        $shopLookItems = $hotspots
            ->map(function (array $hotspot, int $index) use ($hasSelectedProducts, $shopLookProducts, $shopLookProductsById): array {
                $product = $hotspot['product_id']
                    ? $shopLookProductsById->get($hotspot['product_id'])
                    : (! $hasSelectedProducts ? $shopLookProducts->get($index) : null);

                return [...$hotspot, 'product' => $product];
            })
            ->filter(fn (array $item): bool => $item['product'] instanceof Product)
            ->values();
        $tracking->record('view_item_list', ['item_list_id' => 'home']);

        return view('theme::pages.home', [
            'products' => $products,
            'bestSellerProducts' => $bestSellerProducts,
            'shopLookItems' => $shopLookItems,
            'canonical' => $canonical->forPath('/'),
            'schemaJson' => $schema->encode($schema->organization(), $schema->website()),
            'headerMenu' => Menu::query()->forStore()->where('handle', 'header')->with('items.children.children')->first(),
            'footerMenu' => Menu::query()->forStore()->where('handle', 'footer')->with('items.children.children')->first(),
        ]);
    }

    public function catalog(Request $request, CatalogQuery $catalog, TrackingDispatcher $tracking): View
    {
        $filters = CatalogFilters::fromRequest($request);
        $products = $catalog->publishedProducts($filters);
        $tracking->record('view_category', ['item_list_id' => 'catalog']);

        return view('theme::pages.catalog', [
            'products' => $products,
            'collections' => $catalog->collections(),
            'filters' => $filters,
            'facets' => $catalog->facets(),
        ]);
    }

    public function collection(Request $request, string $slug, CatalogQuery $catalog, TrackingDispatcher $tracking): View
    {
        $collection = $catalog->collectionBySlug($slug);
        $filters = CatalogFilters::fromRequest($request);
        $tracking->record('view_category', ['item_list_id' => $slug]);

        return view('theme::pages.catalog', [
            'products' => $catalog->publishedProducts($filters, $collection),
            'collections' => $catalog->collections(),
            'currentCollection' => $collection,
            'filters' => $filters,
            'facets' => $catalog->facets(),
        ]);
    }

    public function product(string $slug, CatalogQuery $catalog, TrackingDispatcher $tracking, SchemaBuilder $schema, CanonicalUrl $canonical): View
    {
        $product = $catalog->productBySlug($slug);
        $variant = $product->variants->first();
        $price = $variant?->prices->first();
        $colorVariants = $catalog->colorVariantProducts($product)
            ->map(fn (Product $item) => [
                'id' => $item->id,
                'name' => $item->translateAttribute('name'),
                'slug' => $item->defaultUrl?->slug,
                'image' => ProductImage::url($item, 'small'),
                'color' => $this->resolveColor($item),
                'active' => $item->id === $product->id,
            ])
            ->filter(fn (array $item) => filled($item['slug']))
            ->values();
        $tracking->record('view_item', [
            'item_id' => $variant?->sku,
            'item_name' => $product->translateAttribute('name'),
            'value' => TrackingDispatcher::fromMinor((int) ($price?->price?->value ?? 0)),
            'currency' => $price?->currency?->code ?? 'TRY',
        ]);

        $url = $canonical->forPath(StorefrontPaths::product($slug));

        $variantsPayload = $product->variants->map(function ($item) {
            $price = $item->prices->first();
            $compareValue = (int) ($price?->compare_price?->value ?? 0);

            return [
                'id' => $item->id,
                'purchasable' => $item->canBeFulfilledAtQuantity(1),
                'price' => $price?->priceIncTax()?->formatted(),
                'compare_price' => $compareValue > 0 ? $price->comparePriceIncTax()->formatted() : null,
                'values' => $item->values->map(fn (ProductOptionValue $value) => [
                    'id' => $value->id,
                    'name' => $value->translate('name'),
                    'option' => $value->option?->handle,
                ])->values()->all(),
            ];
        })->values();

        $optionHandles = $variantsPayload
            ->flatMap(fn (array $item) => collect($item['values'])->pluck('option'))
            ->filter()
            ->unique()
            ->values();
        $selectableHandles = $colorVariants->count() > 1
            ? $optionHandles->reject(fn ($handle) => $handle === 'color')->values()
            : $optionHandles;
        $selectableHandles = $selectableHandles
            ->sortBy(fn (string $handle) => $handle === 'color' ? 0 : ($handle === 'size' ? 1 : 2))
            ->values();
        $options = $selectableHandles->mapWithKeys(function (string $handle) use ($product) {
            return [
                $handle => $product->variants
                    ->flatMap(fn ($item) => $item->values)
                    ->filter(fn (ProductOptionValue $value) => $value->option?->handle === $handle)
                    ->unique('id')
                    ->values()
                    ->map(fn (ProductOptionValue $value) => [
                        'id' => $value->id,
                        'name' => $value->translate('name'),
                    ]),
            ];
        });
        $selectedVariant = $product->variants->first(
            fn ($item) => $item->canBeFulfilledAtQuantity(1)
        ) ?? $variant;

        return view('theme::pages.product', [
            'product' => $product,
            'variant' => $selectedVariant,
            'colorVariants' => $colorVariants,
            'variantsPayload' => $variantsPayload,
            'options' => $options,
            'canonical' => $url,
            'shippingPage' => Page::query()->forStore()->where('slug', 'kargo')->where('is_published', true)->first(),
            'schemaJson' => $schema->encode($schema->product(
                $product->translateAttribute('name') ?? '',
                $url,
                (int) ($price?->price?->value ?? 0),
                $price?->currency?->code ?? 'TRY',
                (bool) $variant?->canBeFulfilledAtQuantity(1),
                ProductImage::url($product),
            )),
        ]);
    }

    private function resolveColor(Product $product): ?string
    {
        $matched = $product->variants->first();

        if (! $matched) {
            return null;
        }

        $color = $matched->values->first(
            fn (ProductOptionValue $value) => $value->option?->handle === 'color'
        );

        return $color?->translate('name');
    }

    public function search(Request $request, CatalogQuery $catalog, TrackingDispatcher $tracking): View
    {
        $filters = CatalogFilters::fromRequest($request);
        $tracking->record('search', ['search_term' => $filters->search]);

        return view('theme::pages.catalog', [
            'products' => $catalog->publishedProducts($filters),
            'collections' => $catalog->collections(),
            'search' => $filters->search,
            'filters' => $filters,
            'facets' => $catalog->facets(),
        ]);
    }

    public function page(string $slug, CanonicalUrl $canonical, CmsPageLayout $layout): View
    {
        $page = Page::query()->forStore()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $cms = $layout->present($page);
        $canonicalUrl = $canonical->forModel($page, 'sayfa/'.$slug);

        return view('theme::pages.cms', [
            'page' => $page,
            'cms' => $cms,
            'canonical' => $canonicalUrl,
            'schemaJson' => $layout->schemaJson($page, $canonicalUrl, $cms),
        ]);
    }

    public function blogIndex(Request $request): View
    {
        $categorySlug = $request->string('kategori')->toString() ?: null;
        $posts = BlogPost::query()
            ->forStore()
            ->published()
            ->with('category')
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $categorySlug)))
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('theme::pages.blog-index', [
            'posts' => $posts,
            'categories' => BlogCategory::query()->forStore()->orderBy('name')->get(),
            'currentCategory' => $categorySlug,
        ]);
    }

    public function blogShow(string $slug, CanonicalUrl $canonical, SchemaBuilder $schema): View
    {
        $post = BlogPost::query()->forStore()->published()->with(['category', 'tags'])->where('slug', $slug)->firstOrFail();

        return view('theme::pages.blog-show', [
            'post' => $post,
            'related' => BlogPost::query()
                ->forStore()
                ->published()
                ->whereKeyNot($post->id)
                ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'canonical' => $canonical->forModel($post, 'blog/'.$slug),
            'schemaJson' => $schema->encode($schema->article($post->title, $canonical->forPath('blog/'.$slug), $post->published_at?->toIso8601String())),
        ]);
    }

    public function cart(CartManager $carts): View
    {
        $cart = $carts->current()->calculate();
        $cart->loadMissing([
            'lines.purchasable.product.defaultUrl',
            'lines.purchasable.values.option',
        ]);

        return view('theme::pages.cart', [
            'cart' => $cart,
        ]);
    }

    public function addToCart(Request $request, CartManager $carts): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $carts->add((int) $data['variant_id'], (int) $data['quantity']);
            app(TrackingDispatcher::class)->flashLast();
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            $cart = $carts->current()->calculate();

            return response()->json([
                'ok' => true,
                'message' => 'Sepete eklendi.',
                'count' => (int) $cart->lines->sum('quantity'),
            ]);
        }

        $route = $request->input('intent') === 'buy' ? 'checkout.show' : 'cart.show';

        return redirect()->route($route)->with('status', 'Sepete eklendi.');
    }

    public function updateCart(Request $request, CartManager $carts): RedirectResponse
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $carts->updateLine((int) $data['line_id'], (int) $data['quantity']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        return back();
    }

    public function removeCart(Request $request, CartManager $carts): RedirectResponse
    {
        $carts->removeLine((int) $request->validate(['line_id' => ['required', 'integer']])['line_id']);

        return back();
    }

    public function coupon(Request $request, CartManager $carts): RedirectResponse
    {
        $code = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => __('etic.storefront.coupon.required'),
        ])['code'];

        try {
            $carts->applyCoupon($code);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', __('etic.storefront.coupon.applied'));
    }

    public function removeCoupon(CartManager $carts): RedirectResponse
    {
        $carts->removeCoupon();

        return back()->with('status', __('etic.storefront.coupon.removed'));
    }

    public function checkout(CartManager $carts, TrackingDispatcher $tracking): View
    {
        $cart = $carts->current()->calculate();
        $cart->loadMissing([
            'lines.purchasable.product.defaultUrl',
            'lines.purchasable.values.option',
        ]);
        $tracking->record('begin_checkout', [
            'value' => TrackingDispatcher::fromMinor((int) ($cart->total?->value ?? 0)),
            'currency' => $cart->currency?->code ?? 'TRY',
        ]);

        return view('theme::pages.checkout', [
            'cart' => $cart,
            'shippingOptions' => ShippingManifest::getOptions($cart),
        ]);
    }

    public function placeOrder(Request $request, CartManager $carts, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate(CheckoutPayload::rules());

        try {
            $order = $checkout->place($carts->current(), $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        $order->loadMissing([
            'productLines.purchasable.product',
            'shippingAddress',
            'billingAddress',
        ]);

        return view('theme::pages.success', ['order' => $order]);
    }

    public function registerForm(): View
    {
        return view('theme::pages.auth', ['mode' => 'register']);
    }

    public function loginForm(): View
    {
        return view('theme::pages.auth', ['mode' => 'login']);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $parts = explode(' ', $data['name'], 2);
        $customer = Customer::query()->create([
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? $parts[0],
        ]);
        $customer->users()->attach($user);

        Auth::login($user);

        return redirect()->route('account');
    }

    public function login(Request $request): RedirectResponse
    {
        if (! Auth::attempt($request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]), true)) {
            return back()->withErrors(['email' => 'Giriş bilgileri hatalı.']);
        }

        $request->session()->regenerate();

        return redirect()->route('account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function account(): View
    {
        $user = Auth::user();

        return view('theme::pages.account', [
            'orders' => $user?->orders()->latest()->get() ?? collect(),
        ]);
    }

    public function accountOrder(Order $order): View
    {
        $user = Auth::user();

        abort_unless($user && $user->orders()->whereKey($order->getKey())->exists(), 403);

        $order->loadMissing([
            'productLines.purchasable.product',
            'shippingAddress',
            'billingAddress',
        ]);

        return view('theme::pages.account-order', ['order' => $order]);
    }
}
