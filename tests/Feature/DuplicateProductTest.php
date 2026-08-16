<?php

use App\Etic\Catalog\DuplicateProduct;
use App\Etic\Support\CommerceBootstrap;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('copies a product as a draft with unique sku and url', function () {
    $product = Product::query()->where('status', 'published')->firstOrFail();
    $product->load(['variants.prices', 'variants.values', 'collections', 'productOptions', 'urls']);

    $copy = app(DuplicateProduct::class)->handle($product);

    expect($copy->id)->not->toBe($product->id)
        ->and($copy->status)->toBe('draft')
        ->and($copy->brand_id)->toBe($product->brand_id)
        ->and($copy->model_code)->toBe($product->model_code)
        ->and($copy->product_type_id)->toBe($product->product_type_id)
        ->and($copy->translateAttribute('name'))->toContain('(kopya)')
        ->and($product->fresh()->translateAttribute('name'))->toBe('Klasik Boxer')
        ->and($copy->variants)->toHaveCount($product->variants->count())
        ->and($copy->collections->pluck('id')->sort()->values()->all())
        ->toBe($product->collections->pluck('id')->sort()->values()->all())
        ->and($copy->productOptions->pluck('id')->sort()->values()->all())
        ->toBe($product->productOptions->pluck('id')->sort()->values()->all());

    $originalSkus = $product->variants->pluck('sku');
    $copiedSkus = $copy->variants->pluck('sku');

    expect($copiedSkus->intersect($originalSkus))->toBeEmpty()
        ->and($copiedSkus->every(fn (string $sku) => str_contains($sku, '-KOPYA')))->toBeTrue();

    $original = $product->variants->first();
    $copied = $copy->variants->firstWhere(
        fn (ProductVariant $variant) => $variant->values->pluck('id')->sort()->values()->all()
            === $original->values->pluck('id')->sort()->values()->all()
    );

    expect($copied)->not->toBeNull()
        ->and($copied->stock)->toBe($original->stock)
        ->and($copied->gtin)->toBeNull()
        ->and((int) $copied->prices->first()->price->value)->toBe((int) $original->prices->first()->price->value)
        ->and((int) $copied->prices->first()->compare_price->value)->toBe((int) $original->prices->first()->compare_price->value);

    expect($copy->defaultUrl?->slug)->not->toBe($product->defaultUrl?->slug)
        ->and($copy->defaultUrl?->slug)->not->toBeNull();
});

it('makes a second copy without colliding skus', function () {
    $product = Product::query()->where('status', 'published')->firstOrFail();

    $first = app(DuplicateProduct::class)->handle($product);
    $second = app(DuplicateProduct::class)->handle($product);

    expect($first->variants->pluck('sku')->intersect($second->variants->pluck('sku')))->toBeEmpty();
});
