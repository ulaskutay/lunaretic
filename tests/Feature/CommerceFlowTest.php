<?php

use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\SEO\CanonicalUrl;
use App\Etic\Support\CommerceBootstrap;
use App\Models\User;
use Lunar\Facades\CartSession;
use Lunar\Models\Order;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

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

it('applies a percentage coupon', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('cart.coupon'), ['code' => 'BOXER10'])->assertRedirect();

    \Lunar\Facades\Discounts::resetDiscounts();
    $cart = CartSession::current()?->recalculate();
    expect($cart?->coupon_code)->toBe('BOXER10')
        ->and((int) $cart?->discountTotal?->value)->toBeGreaterThan(0);
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
    $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml');
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
    $this->get('/sayfa/gizlilik')->assertOk();

    $canonical = app(CanonicalUrl::class)->forPath('sayfa/gizlilik');
    expect($canonical)->toEndWith('/sayfa/gizlilik');
});

it('records tracking events from a central dispatcher', function () {
    $dispatcher = app(TrackingDispatcher::class);
    $dispatcher->record('view_item', ['item_id' => 'SKU']);

    expect($dispatcher->dataLayer())->toHaveCount(1)
        ->and($dispatcher->dataLayer()[0]['event'])->toBe('view_item');
});

it('requires authentication for the account page', function () {
    $this->get(route('account'))->assertRedirect();

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('account'))->assertOk();
});

it('renders the storefront catalog', function () {
    $this->get('/')->assertOk();
    $this->get('/koleksiyon')->assertOk();
    $this->get('/p/klasik-boxer')->assertOk();
    $this->get('/api/v1/products')->assertOk()->assertJsonStructure(['data']);
});

it('uploads a png product image and generates a thumbnail', function () {
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
        ->and(\App\Etic\Media\ProductImage::url($product->fresh()))->toContain('/storage/');

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

    app(\App\Etic\Media\MediaLibraryUploader::class)->addMany($product, $paths, $collection, true);

    $media = $product->fresh()->getMedia($collection);
    expect($media)->toHaveCount(2)
        ->and((bool) $media[0]->getCustomProperty('primary'))->toBeTrue()
        ->and((bool) $media[1]->getCustomProperty('primary'))->toBeFalse();

    foreach ($paths as $path) {
        @unlink($path);
    }
});

it('applies admin-saved shipping table rates', function () {
    $rates = app(\App\Etic\Integrations\Shipping\ShippingRates::class);
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
    $options = app(\App\Etic\Integrations\Shipping\TableRateShippingProvider::class)->optionsFor($cart);

    expect($options)->toHaveCount(1)
        ->and($options[0]->getName())->toBe('Ekspres Kargo')
        ->and($options[0]->getIdentifier())->toBe('express')
        ->and($options[0]->price->value)->toBe(24900);
});
