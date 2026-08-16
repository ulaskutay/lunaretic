<?php

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

function secondStore(): Store
{
    return Store::query()->create([
        'handle' => 'second',
        'name' => 'Second Store',
        'primary_domain' => 'second.test',
        'extra_domains' => ['www.second.test'],
        'theme' => 'default',
        'locale' => 'tr',
        'currency' => 'TRY',
        'is_active' => true,
        'is_default' => false,
    ]);
}

it('resolves a store from the request host including www aliases', function () {
    $store = secondStore();

    expect(Store::resolveByHost('second.test')?->id)->toBe($store->id)
        ->and(Store::resolveByHost('www.second.test')?->id)->toBe($store->id)
        ->and(Store::resolveByHost('localhost')?->handle)->toBe('boxers');
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

    $context->bindByHandle('boxers');
    expect(app(PaymentCredentials::class)->iyzico()['api_key'])->not->toBe('second-key');

    $context->bind($second);
    expect(app(PaymentCredentials::class)->iyzico()['api_key'])->toBe('second-key');
});
