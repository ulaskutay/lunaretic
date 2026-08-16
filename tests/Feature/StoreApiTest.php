<?php

use App\Etic\Catalog\DuplicateProduct;
use App\Etic\Support\CommerceBootstrap;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
});

it('exposes storefront bootstrap for the next.js client', function () {
    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.store.handle', 'boxers')
        ->assertJsonStructure(['data' => ['store', 'menus', 'tracking', 'theme']]);
});

it('filters catalog products by availability', function () {
    $productId = ProductVariant::query()->value('product_id');
    ProductVariant::query()->where('product_id', $productId)->update(['stock' => 0, 'purchasable' => 'in_stock']);

    $outOfStock = $this->getJson('/api/v1/products?stok=yok')->assertOk()->json('data');
    $inStock = $this->getJson('/api/v1/products?stok=1')->assertOk()->json('data');

    expect(collect($outOfStock)->pluck('id'))->toContain($productId);
    expect(collect($inStock)->pluck('id'))->not->toContain($productId);
    expect(collect($outOfStock)->firstWhere('id', $productId)['in_stock'])->toBeFalse();
});

it('lists out of stock products after in stock products', function () {
    $product = Product::query()->where('status', 'published')->firstOrFail();
    $copy = app(DuplicateProduct::class)->handle($product);
    $copy->update(['status' => 'published']);
    $copy->variants()->update(['stock' => 0, 'purchasable' => 'in_stock']);

    $flags = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))->pluck('in_stock');
    $firstOutOfStock = $flags->search(false);

    expect($flags)->toContain(true)
        ->and($flags)->toContain(false)
        ->and($firstOutOfStock)->not->toBeFalse()
        ->and($flags->slice($firstOutOfStock)->contains(true))->toBeFalse();
});

it('lists color variant thumbnails for products that share a model code', function () {
    $product = Product::query()->where('status', 'published')->firstOrFail();
    $copy = app(DuplicateProduct::class)->handle($product);
    $copy->update(['status' => 'published']);

    $card = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))
        ->firstWhere('id', $product->id);

    expect($card['color_variants'])->toHaveCount(2)
        ->and(collect($card['color_variants'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$product->id, $copy->id])->sort()->values()->all());
});

it('does not treat shared skus as color variants', function () {
    $product = Product::query()->where('status', 'published')->firstOrFail();
    $copy = app(DuplicateProduct::class)->handle($product);
    $copy->update([
        'status' => 'published',
        'model_code' => 'BX-DIGER',
    ]);
    $copy->variants()->first()?->update(['sku' => $product->variants()->first()?->sku]);

    $card = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))
        ->firstWhere('id', $product->id);

    expect($card['color_variants'])->toBe([]);
});

it('lists and shows products over the storefront api', function () {
    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'facets', 'collections']);

    $this->getJson('/api/v1/products?sort=best_selling')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'facets', 'collections']);

    $this->getJson('/api/v1/products/klasik-boxer')
        ->assertOk()
        ->assertJsonPath('data.slug', 'klasik-boxer')
        ->assertJsonStructure(['data' => ['variants', 'gallery', 'gallery_items', 'color_variants', 'collections'], 'schema', 'events']);
});

it('keeps a headless cart by token across requests', function () {
    $variant = ProductVariant::query()->with('product.defaultUrl')->first();

    $created = $this->postJson('/api/v1/cart', [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertOk();

    $token = $created->json('data.token');
    expect($token)->not->toBeEmpty();

    $this->withHeader('X-Cart-Token', $token)
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.lines.0.quantity', 2)
        ->assertJsonPath('data.lines.0.slug', $variant->product?->defaultUrl?->slug)
        ->assertJsonStructure(['data' => ['free_shipping' => ['threshold', 'remaining', 'unlocked'], 'lines' => [['unit_price', 'values']]]]);

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/coupon', ['code' => 'BOXER10'])
        ->assertOk()
        ->assertJsonPath('data.coupon_code', 'BOXER10');
});

it('rejects overselling through the storefront api', function () {
    $variant = ProductVariant::query()->first();
    $variant->update(['stock' => 1, 'purchasable' => 'in_stock']);

    $this->postJson('/api/v1/cart', [
        'variant_id' => $variant->id,
        'quantity' => 5,
    ])->assertStatus(422);
});

it('places an order through the storefront api', function () {
    $variant = ProductVariant::query()->first();

    $token = $this->postJson('/api/v1/cart', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->json('data.token');

    $response = $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', [
            'first_name' => 'Ali',
            'last_name' => 'Yılmaz',
            'email' => 'ali@example.com',
            'phone' => '5551112233',
            'line_one' => 'Test Cad. 1',
            'city' => 'İstanbul',
            'payment' => 'cash-in-hand',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'payment-offline')
        ->assertJsonStructure([
            'data' => [
                'id',
                'reference',
                'status_message',
                'shipping_address',
                'lines',
            ],
            'events',
        ]);

    $orderId = $response->json('data.id');

    $this->getJson("/api/v1/orders/{$orderId}")
        ->assertOk()
        ->assertJsonPath('data.id', $orderId)
        ->assertJsonPath('data.lines.0.quantity', 1)
        ->assertJsonPath('data.shipping_address.city', 'İstanbul');
});

it('registers and returns account orders over the storefront api', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ayşe Yılmaz',
        'email' => 'ayse@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()->assertJsonPath('data.user.email', 'ayse@example.com');

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'ayse@example.com',
        'password' => 'password123',
    ])->json('data.token');

    $this->withToken($token)
        ->getJson('/api/v1/account')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'ayse@example.com');
});

it('publishes cms pages and blog over the storefront api', function () {
    $this->getJson('/api/v1/pages/hakkimizda')
        ->assertOk()
        ->assertJsonPath('data.slug', 'hakkimizda')
        ->assertJsonPath('data.template', 'story')
        ->assertJsonPath('data.kicker', 'Marka')
        ->assertJsonStructure(['data' => ['lead', 'body', 'highlights', 'related', 'cta']]);

    $this->getJson('/api/v1/pages/sss')
        ->assertOk()
        ->assertJsonPath('data.template', 'faq')
        ->assertJsonPath('data.faq.0.question', 'Siparişim ne zaman kargoya verilir?');

    $this->getJson('/api/v1/pages/gizlilik')
        ->assertOk()
        ->assertJsonPath('data.template', 'legal');

    $this->getJson('/api/v1/blog')->assertOk()->assertJsonStructure(['data', 'categories']);
});
