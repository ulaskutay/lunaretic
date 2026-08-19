<?php

use App\Etic\Catalog\Models\Product;
use App\Etic\Search\CatalogProductSearch;
use App\Etic\Search\ProductIndexer;
use App\Etic\Support\CommerceBootstrap;

it('falls back to sql search when scout is disabled', function () {
    app(CommerceBootstrap::class)->catalog();

    $this->get(route('search', ['q' => 'klasik']))
        ->assertOk()
        ->assertSee('Klasik Boxer');
});

it('reports scout search as disabled without meilisearch', function () {
    expect(app(CatalogProductSearch::class)->isEnabled())->toBeFalse();
});

it('finds published products by name when scout is disabled', function () {
    app(CommerceBootstrap::class)->catalog();

    expect(app(CatalogProductSearch::class)->matchingProductIds('klasik'))->not->toBeEmpty();
});

it('only indexes published products', function () {
    app(CommerceBootstrap::class)->catalog();

    $product = Product::query()->where('status', 'published')->firstOrFail();
    $indexer = new ProductIndexer;

    expect($indexer->shouldBeSearchable($product))->toBeTrue();

    $product->status = 'draft';

    expect($indexer->shouldBeSearchable($product))->toBeFalse();
});

it('includes channel ids in the searchable payload', function () {
    app(CommerceBootstrap::class)->catalog();

    $product = Product::query()
        ->where('status', 'published')
        ->with('channels')
        ->firstOrFail();

    $payload = (new ProductIndexer)->toSearchableArray($product);

    expect($payload)->toHaveKey('channel_ids')
        ->and($payload['channel_ids'])->not->toBeEmpty()
        ->and($payload)->toHaveKey('name')
        ->and((string) $payload['name'])->not->toBeEmpty();
});
