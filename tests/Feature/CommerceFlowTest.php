<?php

use App\Etic\Integrations\Marketing\MetaConversionsClient;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Integrations\Marketing\TrackingSettings;
use App\Etic\Integrations\Shipping\ShippingRates;
use App\Etic\Integrations\Shipping\TableRateShippingProvider;
use App\Etic\Media\MediaLibraryUploader;
use App\Etic\Media\ProductImage;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\SEO\CanonicalUrl;
use App\Etic\SEO\Models\Redirect;
use App\Etic\Support\CommerceBootstrap;
use App\Models\User;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Lunar\Facades\CartSession;
use Lunar\Facades\Discounts;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\Product;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
});

it('creates a product with color and size variants', function () {
    $product = Product::query()->where('status', 'published')->first();

    expect($product)->not->toBeNull()
        ->and($product->variants)->toHaveCount(12)
        ->and($product->variants->pluck('sku')->unique())->toHaveCount(12);
});

it('rejects cart quantity above stock', function () {
    $variant = ProductVariant::query()->first();
    $variant->update(['stock' => 1, 'purchasable' => 'in_stock']);

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 5,
    ])->assertSessionHasErrors('cart');
});

it('adds a variant to the cart and calculates totals', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertRedirect(route('cart.show'));

    $this->get(route('cart.show'))->assertOk();

    $cart = CartSession::current();
    expect($cart)->not->toBeNull()
        ->and($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->quantity)->toBe(2);
});

it('returns json when a product is added to the cart via ajax', function () {
    $variant = ProductVariant::query()->first();

    $this->postJson(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('message', 'Sepete eklendi.');
});

it('keeps vat inside the product price at checkout', function () {
    $variant = ProductVariant::query()->first();
    $stored = (int) $variant->prices->first()->price->value;

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    Discounts::resetDiscounts();
    $cart = CartSession::current()?->recalculate();

    expect(prices_inc_tax())->toBeTrue()
        ->and((int) $cart?->lines->first()->total->value)->toBe($stored)
        ->and((int) $cart?->total->value)->toBe($stored)
        ->and((int) $cart?->taxTotal?->value)->toBeGreaterThan(0)
        ->and((int) $cart?->total->value)->toBe((int) $cart?->subTotal->value)
        ->and((int) $cart?->total->value)->toBeLessThan((int) round($stored * 1.1));

    $this->get(route('checkout.show'))
        ->assertOk()
        ->assertSee(__('etic.storefront.totals.tax_included'));
});

it('applies a percentage coupon', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('cart.coupon'), ['code' => 'boxer10'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Discounts::resetDiscounts();
    $cart = CartSession::current()?->recalculate();
    expect($cart?->coupon_code)->toBe('BOXER10')
        ->and((int) $cart?->discountTotal?->value)->toBeGreaterThan(0);

    $this->get(route('cart.show'))
        ->assertOk()
        ->assertSee('BOXER10')
        ->assertSee(__('etic.storefront.totals.discount'));

    $this->get(route('checkout.show'))
        ->assertOk()
        ->assertSee('BOXER10');
});

it('rejects an invalid coupon and leaves the cart unchanged', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->from(route('cart.show'))
        ->post(route('cart.coupon'), ['code' => 'GECERSIZ'])
        ->assertRedirect(route('cart.show'))
        ->assertSessionHasErrors('code');

    expect(CartSession::current()?->coupon_code)->toBeNull();
});

it('removes an applied coupon', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('cart.coupon'), ['code' => 'BOXER10']);

    $this->delete(route('cart.coupon.remove'))
        ->assertRedirect()
        ->assertSessionHas('status');

    Discounts::resetDiscounts();
    $cart = CartSession::current()?->recalculate();
    expect($cart?->coupon_code)->toBeNull()
        ->and((int) $cart?->discountTotal?->value)->toBe(0);
});

it('creates an order through offline checkout', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response = $this->post(route('checkout.place'), [
        'first_name' => 'Ali',
        'last_name' => 'Yılmaz',
        'email' => 'ali@example.com',
        'phone' => '5551112233',
        'line_one' => 'Test Cad. 1',
        'city' => 'İstanbul',
        'state' => 'Kadıköy',
        'postcode' => '34710',
        'notes' => 'Kapıya bırakın',
        'payment' => 'cash-in-hand',
    ]);

    $order = Order::query()->latest('id')->first();
    expect($order)->not->toBeNull();
    $response->assertRedirect(route('checkout.success', $order));
    expect($order->status)->toBe('payment-offline')
        ->and($order->placed_at)->not->toBeNull()
        ->and(config('lunar.panel.order_count_statuses'))->toContain('payment-offline');
});

it('authorizes iyzico when a token is present', function () {
    $variant = ProductVariant::query()->first();
    $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

    $this->post(route('checkout.place'), [
        'first_name' => 'Ali',
        'last_name' => 'Yılmaz',
        'email' => 'ali@example.com',
        'phone' => '5551112233',
        'line_one' => 'Test Cad. 1',
        'city' => 'İstanbul',
        'payment' => 'iyzico',
        'payment_token' => 'tok_test',
    ])->assertRedirect();

    expect(Order::query()->latest('id')->first()?->status)->toBe('payment-received');
});

it('walks an order through the fulfilment status scenario', function () {
    $order = Order::factory()->create([
        'status' => OrderStatusScenario::PAYMENT_OFFLINE,
        'currency_code' => 'TRY',
        'compare_currency_code' => 'TRY',
    ]);

    expect($order->status)->toBe(OrderStatusScenario::PAYMENT_OFFLINE)
        ->and($order->status_label)->toBe('Kapıda / havale')
        ->and(OrderStatusScenario::canTransition($order->status, OrderStatusScenario::PROCESSING))->toBeTrue();

    foreach (OrderStatusScenario::fulfilmentPath() as $status) {
        expect(OrderStatusScenario::canTransition($order->status, $status))->toBeTrue();
        $order->update(['status' => $status]);
        $order->refresh();
        expect($order->status)->toBe($status)
            ->and($order->status_label)->toBe(OrderStatusScenario::label($status));
    }

    expect($order->status)->toBe(OrderStatusScenario::DELIVERED)
        ->and(OrderStatusScenario::canTransition($order->status, OrderStatusScenario::CANCELLED))->toBeFalse()
        ->and(config('lunar.panel.order_count_statuses'))->toBe(OrderStatusScenario::openStatuses());
});

it('allows cancelling an unpaid or offline order', function () {
    $order = Order::factory()->create([
        'status' => OrderStatusScenario::AWAITING_PAYMENT,
        'currency_code' => 'TRY',
        'compare_currency_code' => 'TRY',
    ]);

    expect(OrderStatusScenario::canTransition($order->status, OrderStatusScenario::CANCELLED))->toBeTrue();

    $order->update(['status' => OrderStatusScenario::CANCELLED]);

    expect($order->fresh()->status_label)->toBe('İptal edildi');
});

it('serves canonical-friendly seo endpoints', function () {
    $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml')->assertSee('/urun/klasik-boxer', false);
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
    $this->get('/sayfa/gizlilik')->assertOk();

    $canonical = app(CanonicalUrl::class)->forPath('sayfa/gizlilik');
    expect($canonical)->toEndWith('/sayfa/gizlilik');
});

it('renders modern layouts for static storefront pages', function () {
    $this->get('/sayfa/hakkimizda')
        ->assertOk()
        ->assertSee('etic-static--story', false)
        ->assertSee('Koleksiyonu keşfet')
        ->assertSee('Nasıl çalışıyoruz');

    $this->get('/sayfa/sss')
        ->assertOk()
        ->assertSee('etic-static--faq', false)
        ->assertSee('Siparişim ne zaman kargoya verilir?');

    $this->get('/sayfa/gizlilik')
        ->assertOk()
        ->assertSee('etic-static--legal', false)
        ->assertSee('Yardım sayfaları')
        ->assertSee('"@type":"WebPage"', false);

    $this->get('/sayfa/iletisim')
        ->assertOk()
        ->assertSee('etic-static--contact', false)
        ->assertSee('Müşteri desteği');
});

it('records tracking events from a central dispatcher', function () {
    $dispatcher = app(TrackingDispatcher::class);
    $dispatcher->record('view_item', ['item_id' => 'SKU']);

    expect($dispatcher->dataLayer())->toHaveCount(1)
        ->and($dispatcher->dataLayer()[0]['event'])->toBe('view_item')
        ->and($dispatcher->metaCommands()[0]['event'])->toBe('ViewContent');
});

it('publishes blog posts and filters by category', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Doğru boxer nasıl seçilir?')
        ->assertSee('Rehber');

    $this->get(route('blog.index', ['kategori' => 'rehber']))
        ->assertOk()
        ->assertSee('Doğru boxer nasıl seçilir?');

    $this->get(route('blog.show', 'boxer-rehberi'))
        ->assertOk()
        ->assertSee('kumaş', false);
});

it('applies an active redirect', function () {
    Redirect::query()->create([
        'from_path' => 'eski-boxer',
        'to_url' => '/urun/klasik-boxer',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $this->get('/eski-boxer')->assertRedirect('/urun/klasik-boxer');
});

it('serves a google merchant rss feed of variants', function () {
    $this->get(route('feed.google-merchant'))
        ->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=UTF-8')
        ->assertSee('g:id', false)
        ->assertSee('BX-SIYAH-S', false)
        ->assertSee('/urun/klasik-boxer', false)
        ->assertSee('249.00 TRY', false);
});

it('uses panel marketing settings on the storefront and can hide the merchant feed', function () {
    app(TrackingSettings::class)->save([
        'ga4_measurement_id' => 'G-PANELTEST',
        'meta_pixel_id' => '999111222',
        'merchant_feed_enabled' => false,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('G-PANELTEST', false)
        ->assertSee('999111222', false);

    $this->get(route('feed.google-merchant'))->assertNotFound();
});

it('filters the catalog by colour and price', function () {
    $black = ProductOptionValue::query()
        ->whereHas('option', fn ($option) => $option->where('handle', 'color'))
        ->get()
        ->first(fn ($value) => $value->translate('name') === 'Siyah');

    expect($black)->not->toBeNull();

    $this->get(route('catalog', ['renk' => $black->id]))
        ->assertOk()
        ->assertSee('Klasik Boxer');

    $this->get(route('catalog', ['min' => 400]))
        ->assertOk()
        ->assertDontSee('Klasik Boxer');
});

it('pushes purchase onto the success page data layer', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.place'), [
        'first_name' => 'Ali',
        'last_name' => 'Yılmaz',
        'email' => 'ali@example.com',
        'phone' => '5551112233',
        'line_one' => 'Test Cad. 1',
        'city' => 'İstanbul',
        'payment' => 'cash-in-hand',
    ])->assertRedirect();

    $order = Order::query()->latest('id')->first();

    $this->get(route('checkout.success', $order))
        ->assertOk()
        ->assertSee('eticTrack', false)
        ->assertSee('purchase', false)
        ->assertSee((string) $order->reference, false);
});

it('sends hashed purchase events to meta conversions api', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['events_received' => 1]),
    ]);

    app(TrackingSettings::class)->save([
        'meta_pixel_id' => '1234567890',
        'meta_capi_enabled' => true,
        'meta_capi_token' => 'EAATESTTOKEN',
        'meta_test_event_code' => 'TEST123',
        'merchant_feed_enabled' => true,
    ]);

    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.place'), [
        'first_name' => 'Ali',
        'last_name' => 'Yılmaz',
        'email' => 'ali@example.com',
        'phone' => '5551112233',
        'line_one' => 'Test Cad. 1',
        'city' => 'İstanbul',
        'payment' => 'cash-in-hand',
    ])->assertRedirect();

    $hashedEmail = MetaConversionsClient::hash('ali@example.com');
    $hashedPhone = MetaConversionsClient::hash('905551112233');

    Http::assertSent(function ($request) use ($hashedEmail, $hashedPhone) {
        $json = $request->data();
        $event = $json['data'][0] ?? [];

        return str_contains($request->url(), '/1234567890/events')
            && ($event['event_name'] ?? null) === 'Purchase'
            && ($event['event_id'] ?? null)
            && ($event['user_data']['em'][0] ?? null) === $hashedEmail
            && ($event['user_data']['ph'][0] ?? null) === $hashedPhone
            && ($json['test_event_code'] ?? null) === 'TEST123'
            && ! str_contains($request->body(), 'ali@example.com')
            && ! str_contains($request->body(), '5551112233');
    });
});

it('requires authentication for the account page', function () {
    $this->get(route('account'))->assertRedirect();

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('account'))->assertOk();
});

it('renders the storefront catalog', function () {
    $this->get('/')->assertOk();
    $this->get('/koleksiyon')->assertOk();
    $this->get('/urun/klasik-boxer')->assertOk();
    $this->get('/p/klasik-boxer')->assertRedirect('/urun/klasik-boxer');
    $this->get('/api/v1/products')->assertOk()->assertJsonStructure(['data']);
});

it('hides the variant picker when a product only has a default stock code', function () {
    $language = Language::query()->where('code', 'tr')->firstOrFail();
    $product = Product::query()->create([
        'status' => 'published',
        'product_type_id' => ProductType::query()->value('id'),
        'attribute_data' => [
            'name' => new TranslatedText(collect(['tr' => 'Tek Stoklu Ürün'])),
        ],
    ]);
    $product->urls()->firstOrCreate(
        ['slug' => 'tek-stoklu-urun', 'language_id' => $language->id],
        ['default' => true]
    );
    $variant = $product->variants()->create([
        'tax_class_id' => TaxClass::getDefault()->id,
        'sku' => '20001',
        'stock' => 5,
        'purchasable' => 'in_stock',
        'shippable' => true,
        'unit_quantity' => 1,
    ]);
    $variant->prices()->create([
        'price' => 200000,
        'currency_id' => Currency::query()->where('code', 'TRY')->value('id'),
        'min_quantity' => 1,
    ]);

    $this->get('/urun/tek-stoklu-urun')
        ->assertOk()
        ->assertSee('Tek Stoklu Ürün', false)
        ->assertDontSee(__('etic.storefront.product.variant'))
        ->assertDontSee('<select name="variant_id"', false)
        ->assertSee('name="variant_id"', false);

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertRedirect(route('cart.show'));
});

it('uploads a png product image and generates a thumbnail', function () {
    $this->withoutDefer();

    $product = Product::query()->first();
    $path = sys_get_temp_dir().'/etic-boxer-'.uniqid().'.png';

    $image = imagecreatetruecolor(80, 80);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagefilledrectangle($image, 10, 10, 70, 70, imagecolorallocate($image, 18, 18, 18));
    imagepng($image, $path);

    $media = $product->addMedia($path)
        ->usingFileName('boxer.png')
        ->withCustomProperties(['name' => 'Boxer', 'primary' => true])
        ->toMediaCollection(config('lunar.media.collection'));

    expect($media->mime_type)->toBe('image/png')
        ->and(str_ends_with(strtolower($media->file_name), '.png'))->toBeTrue()
        ->and($media->hasGeneratedConversion('small'))->toBeTrue()
        ->and(ProductImage::url($product->fresh(), 'large'))->toContain('/storage/')
        ->and(ProductImage::url($product->fresh(), 'large'))->not->toContain('/conversions/')
        ->and(ProductImage::url($product->fresh(), 'small'))->toContain('/conversions/');

    @unlink($path);
});

it('defers product image conversions so uploading does not wait for thumbnails', function () {
    $product = Product::query()->first();
    $path = sys_get_temp_dir().'/etic-boxer-'.uniqid().'.png';

    $image = imagecreatetruecolor(80, 80);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagefilledrectangle($image, 10, 10, 70, 70, imagecolorallocate($image, 18, 18, 18));
    imagepng($image, $path);

    $media = $product->addMedia($path)
        ->usingFileName('boxer.png')
        ->withCustomProperties(['name' => 'Boxer', 'primary' => true])
        ->toMediaCollection(config('lunar.media.collection'));

    expect($media->hasGeneratedConversion('small'))->toBeFalse()
        ->and(ProductImage::url($product->fresh()))->toContain('/storage/');

    app(DeferredCallbackCollection::class)->invoke();

    expect($media->fresh()->hasGeneratedConversion('small'))->toBeTrue();

    @unlink($path);
});

it('stores multiple product images and marks the first as primary', function () {
    $product = Product::query()->first();
    $collection = config('lunar.media.collection');
    $paths = [];

    foreach ([1, 2] as $i) {
        $path = sys_get_temp_dir()."/etic-boxer-{$i}-".uniqid().'.png';
        $image = imagecreatetruecolor(40, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 20 * $i, 20 * $i, 20 * $i));
        imagepng($image, $path);
        $paths[] = $path;
    }

    app(MediaLibraryUploader::class)->addMany($product, $paths, $collection, true);

    $media = $product->fresh()->getMedia($collection);
    expect($media)->toHaveCount(2)
        ->and((bool) $media[0]->getCustomProperty('primary'))->toBeTrue()
        ->and((bool) $media[1]->getCustomProperty('primary'))->toBeFalse();

    foreach ($paths as $path) {
        @unlink($path);
    }
});

it('applies admin-saved shipping table rates', function () {
    $rates = app(ShippingRates::class);
    $rates->save([
        [
            'name' => 'Ekspres Kargo',
            'identifier' => 'express',
            'description' => '1-2 iş günü',
            'price' => 24900,
            'max_subtotal' => null,
        ],
    ]);

    expect($rates->all()[0]['name'])->toBe('Ekspres Kargo')
        ->and($rates->toFormState($rates->all())[0]['price_tl'])->toBe(249.0);

    $variant = ProductVariant::query()->first();
    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $cart = CartSession::current()?->calculate();
    $options = app(TableRateShippingProvider::class)->optionsFor($cart);

    expect($options)->toHaveCount(1)
        ->and($options[0]->getName())->toBe('Ekspres Kargo')
        ->and($options[0]->getIdentifier())->toBe('express')
        ->and($options[0]->price->value)->toBe(24900);
});
