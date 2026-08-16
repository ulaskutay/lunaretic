<?php

use App\Etic\Catalog\Filament\ListProductsExtension;
use App\Etic\Support\CommerceBootstrap;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('creates new panel products as published', function () {
    $product = app(ListProductsExtension::class)->createPublished([
        'name' => ['tr' => 'Yeni Boxer'],
        'product_type_id' => ProductType::query()->value('id'),
        'sku' => 'BX-YENI-001',
        'base_price' => '149.90',
    ], Product::class);

    expect($product->status)->toBe('published')
        ->and($product->translateAttribute('name'))->toBe('Yeni Boxer');

    $variant = ProductVariant::query()->where('sku', 'BX-YENI-001')->first();

    expect($variant)->not->toBeNull()
        ->and($variant->product_id)->toBe($product->id);
});
