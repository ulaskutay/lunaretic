<?php

use App\Etic\Catalog\Models\Product as EticProduct;
use App\Etic\CMS\Models\Page;
use App\Etic\Integrations\Payments\PaymentCredentials;
use App\Etic\SEO\Models\Redirect;
use App\Etic\Store\Models\Store;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\StoreContext;
use Lunar\Models\Product;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
});

it('uses app url for ip-only storefront links after a server move', function () {
    config(['app.url' => 'http://95.217.160.252', 'etic.store.primary_url' => 'http://95.217.160.252']);

    $store = Store::query()->where('handle', 'omnipanel')->firstOrFail();
    $store->forceFill(['primary_domain' => '91.98.120.228'])->saveQuietly();

    expect($store->fresh()->primaryUrl())->toBe('http://95.217.160.252');
});

it('resolves a store from the request host including www aliases', function () {
    $store = secondStore();

    expect(Store::resolveByHost('second.test')?->id)->toBe($store->id)
        ->and(Store::resolveByHost('www.second.test')?->id)->toBe($store->id)
        ->and(Store::resolveByHost('localhost')?->handle)->toBe('omnipanel')
        ->and(Store::resolveByHost('127.0.0.1')?->handle)->toBe('omnipanel');
});

it('isolates cms pages between stores', function () {
    $second = secondStore();

    Page::query()->create([
        'title' => 'Second only',
        'slug' => 'second-only',
        'content' => '<p>second secret</p>',
        'is_published' => true,
        'channel_id' => $second->channel()->id,
    ]);

    $this->get('http://second.test/sayfa/second-only')
        ->assertOk()
        ->assertSee('second secret')
        ->assertSee('Second Store');

    $this->get('http://localhost/sayfa/second-only')->assertNotFound();
    $this->get('http://second.test/sayfa/gizlilik')->assertNotFound();
    $this->get('http://localhost/sayfa/gizlilik')->assertOk();
});

it('isolates redirects between stores', function () {
    $second = secondStore();

    Redirect::query()->create([
        'from_path' => 'eski-sayfa',
        'to_url' => '/sayfa/gizlilik',
        'status_code' => 301,
        'is_active' => true,
        'channel_id' => $second->channel()->id,
    ]);

    $this->get('http://second.test/eski-sayfa')->assertRedirect('/sayfa/gizlilik');
    $this->get('http://localhost/eski-sayfa')->assertNotFound();
});

it('filters catalog products with an indexed channelables exists query', function () {
    app(StoreContext::class)->bindByHandle('omnipanel');

    $sql = EticProduct::query()->toSql();

    expect($sql)->toContain('exists')
        ->and($sql)->toContain('channelables')
        ->and($sql)->not->toContain('lunar_channels');
});

it('hides products that are not enabled on the current channel', function () {
    $second = secondStore();
    $product = Product::query()->where('status', 'published')->firstOrFail();
    $slug = $product->defaultUrl->slug;

    $product->channels()->sync([
        $second->channel()->id => [
            'enabled' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ],
    ]);

    $this->get('http://second.test/urun/'.$slug)->assertOk();
    $this->get('http://localhost/urun/'.$slug)->assertNotFound();
    $this->get('http://localhost/koleksiyon')->assertOk()->assertDontSee('Klasik Boxer');
});

it('keeps payment credentials isolated per store', function () {
    $second = secondStore();
    $context = app(StoreContext::class);

    $context->bind($second);
    app(PaymentCredentials::class)->saveIyzico([
        'api_key' => 'second-key',
        'secret_key' => 'second-secret',
        'base_url' => 'https://sandbox-api.iyzipay.com',
    ]);
    app(PaymentCredentials::class)->savePaytr([
        'merchant_id' => 'second-merchant',
        'merchant_key' => 'second-paytr-key',
        'merchant_salt' => 'second-paytr-salt',
    ]);

    $context->bindByHandle('omnipanel');
    expect(app(PaymentCredentials::class)->iyzico()['api_key'])->not->toBe('second-key')
        ->and(app(PaymentCredentials::class)->paytr()['merchant_id'])->not->toBe('second-merchant');

    $context->bind($second);
    expect(app(PaymentCredentials::class)->iyzico()['api_key'])->toBe('second-key')
        ->and(app(PaymentCredentials::class)->paytr()['merchant_id'])->toBe('second-merchant');
});
