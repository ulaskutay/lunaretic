<?php

namespace App\Etic\Storefront\Http\Controllers;

use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\Page;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\SEO\CanonicalUrl;
use App\Etic\SEO\SchemaBuilder;
use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CatalogQuery;
use App\Etic\Storefront\CheckoutService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Collection;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use RuntimeException;

class StorefrontController
{
    public function home(CatalogQuery $catalog, TrackingDispatcher $tracking, SchemaBuilder $schema, CanonicalUrl $canonical): View
    {
        $products = $catalog->publishedProducts();
        $tracking->record('view_item_list', ['item_list_id' => 'home']);

        return view('theme::pages.home', [
            'products' => $products,
            'canonical' => $canonical->forPath('/'),
            'schemaJson' => $schema->encode($schema->organization(), $schema->website()),
            'headerMenu' => Menu::query()->where('handle', 'header')->with('items.children')->first(),
            'footerMenu' => Menu::query()->where('handle', 'footer')->with('items.children')->first(),
        ]);
    }

    public function catalog(Request $request, CatalogQuery $catalog, TrackingDispatcher $tracking): View
    {
        $products = $catalog->publishedProducts($request->string('q')->toString() ?: null, $request->string('sort')->toString() ?: 'newest');
        $tracking->record('view_category', ['search' => $request->get('q')]);

        return view('theme::pages.catalog', [
            'products' => $products,
            'collections' => Collection::query()->get(),
        ]);
    }

    public function collection(string $slug, CatalogQuery $catalog): View
    {
        $collection = $catalog->collectionBySlug($slug);

        return view('theme::pages.catalog', [
            'products' => $collection->products()->where('status', 'published')->with(['media', 'thumbnail', 'defaultUrl'])->paginate(12),
            'collections' => Collection::query()->get(),
            'currentCollection' => $collection,
        ]);
    }

    public function product(string $slug, CatalogQuery $catalog, TrackingDispatcher $tracking, SchemaBuilder $schema, CanonicalUrl $canonical): View
    {
        $product = $catalog->productBySlug($slug);
        $variant = $product->variants->first();
        $price = $variant?->prices->first();
        $tracking->record('view_item', ['item_id' => $variant?->sku]);

        $url = $canonical->forPath('p/'.$slug);

        return view('theme::pages.product', [
            'product' => $product,
            'variant' => $variant,
            'canonical' => $url,
            'schemaJson' => $schema->encode($schema->product(
                $product->translateAttribute('name') ?? '',
                $url,
                (int) ($price?->price?->value ?? 0),
                $price?->currency?->code ?? 'TRY',
                (bool) $variant?->canBeFulfilledAtQuantity(1),
                \App\Etic\Media\ProductImage::url($product),
            )),
        ]);
    }

    public function search(Request $request, CatalogQuery $catalog, TrackingDispatcher $tracking): View
    {
        $q = $request->string('q')->toString();
        $tracking->record('search', ['search_term' => $q]);

        return view('theme::pages.catalog', [
            'products' => $catalog->publishedProducts($q),
            'collections' => Collection::query()->get(),
            'search' => $q,
        ]);
    }

    public function page(string $slug, CanonicalUrl $canonical): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('theme::pages.cms', [
            'page' => $page,
            'canonical' => $canonical->forModel($page, 'sayfa/'.$slug),
        ]);
    }

    public function blogIndex(): View
    {
        return view('theme::pages.blog-index', [
            'posts' => BlogPost::query()->where('is_published', true)->latest('published_at')->paginate(10),
        ]);
    }

    public function blogShow(string $slug, CanonicalUrl $canonical, SchemaBuilder $schema): View
    {
        $post = BlogPost::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('theme::pages.blog-show', [
            'post' => $post,
            'canonical' => $canonical->forModel($post, 'blog/'.$slug),
            'schemaJson' => $schema->encode($schema->article($post->title, $canonical->forPath('blog/'.$slug), $post->published_at?->toIso8601String())),
        ]);
    }

    public function cart(CartManager $carts): View
    {
        return view('theme::pages.cart', [
            'cart' => $carts->current()->calculate(),
        ]);
    }

    public function addToCart(Request $request, CartManager $carts): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $carts->add((int) $data['variant_id'], (int) $data['quantity']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('cart.show')->with('status', 'Sepete eklendi.');
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
        $carts->applyCoupon($request->validate(['code' => ['required', 'string', 'max:50']])['code']);

        return back()->with('status', 'Kupon uygulandı.');
    }

    public function checkout(CartManager $carts, TrackingDispatcher $tracking): View
    {
        $cart = $carts->current()->calculate();
        $tracking->record('begin_checkout', ['value' => $cart->total?->value]);

        return view('theme::pages.checkout', [
            'cart' => $cart,
            'shippingOptions' => ShippingManifest::getOptions($cart),
        ]);
    }

    public function placeOrder(Request $request, CartManager $carts, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:30'],
            'line_one' => ['required', 'string', 'max:191'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping' => ['nullable', 'string'],
            'payment' => ['required', 'in:cash-in-hand,iyzico'],
            'payment_token' => ['nullable', 'string'],
            'same_as_shipping' => ['nullable', 'boolean'],
        ]);

        try {
            $order = $checkout->place($carts->current(), $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
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
}
