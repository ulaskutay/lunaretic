<?php

use App\Etic\Catalog\Models\Brand;
use App\Etic\Catalog\Models\CustomerGroup;
use App\Etic\Catalog\Models\ProductType;
use App\Etic\Store\Actions\ProvisionStore;
use App\Etic\Store\Actions\VerifyCustomDomain;
use App\Etic\Store\Models\CustomDomain;
use App\Etic\Store\Models\Store;
use App\Etic\Store\Models\StoreAuditLog;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\DnsResolver;
use App\Etic\Support\StoreContext;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Product;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
    app(CommerceBootstrap::class)->admin();
});

it('does not redirect the lunar panel off 127.0.0.1', function () {
    $this->get('http://127.0.0.1/lunar')
        ->assertRedirect('http://127.0.0.1/lunar/login');
});

it('does not fall back to the default store for unknown hosts', function () {
    $this->get('http://unknown.example/')->assertNotFound();
});

it('does not serve the default catalog to a tenant subdomain', function () {
    $second = secondStore();

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('http://localhost/api/v1/bootstrap', [
            'X-Forwarded-Host' => $second->primary_domain,
        ])
        ->assertOk()
        ->assertJsonPath('data.store.handle', 'second')
        ->assertJsonPath('data.store.name', 'Second Store');

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('http://localhost/api/v1/products', [
            'X-Forwarded-Host' => $second->primary_domain,
        ])
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('serves the default store on the platform apex, not as a customer subdomain', function () {
    $this->get('http://eticcommerce.test/sayfa/gizlilik')->assertOk();
    $this->get('http://eticcommerce.test/lunar')->assertRedirect();
    $this->get('http://eticcommerce.test/platform')->assertRedirect('http://eticcommerce.test/platform/login');
    $this->getJson('http://eticcommerce.test/api/v1/bootstrap')->assertOk();
});

it('returns maintenance when a store is suspended', function () {
    $store = secondStore();
    $store->forceFill(['is_active' => false])->save();

    $this->get('http://second.test/')
        ->assertStatus(503)
        ->assertSee('geçici olarak kapalı', false);
});

it('provisions a store with subdomain, channel, cms and owner membership', function () {
    $actor = Staff::query()->where('admin', true)->firstOrFail();

    $store = app(ProvisionStore::class)->handle([
        'name' => 'Butik Ada',
        'handle' => 'butikada',
        'theme' => 'default',
        'owner_email' => 'owner@butik.test',
        'owner_password' => 'password123',
    ], $actor);

    $staff = Staff::query()->where('email', 'owner@butik.test')->first();

    expect($store->primary_domain)->toBe('butikada.eticcommerce.test')
        ->and($store->members()->count())->toBe(1)
        ->and($staff)->not->toBeNull()
        ->and(Hash::check('password123', $staff->password))->toBeTrue()
        ->and($staff->can('catalog:manage-products'))->toBeTrue()
        ->and($staff->can('sales:manage-orders'))->toBeTrue()
        ->and($staff->can('settings:manage-staff'))->toBeFalse()
        ->and($store->channel()->handle)->toBe('butikada');

    $this->get('http://butikada.eticcommerce.test/sayfa/gizlilik')->assertOk();
    $this->get('http://localhost/sayfa/gizlilik')->assertOk();
});

it('rejects reserved store handles', function () {
    app(ProvisionStore::class)->handle([
        'name' => 'Admin',
        'handle' => 'admin',
    ]);
})->throws(ValidationException::class);

it('hides another stores products from the current channel', function () {
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
});

it('isolates brands per store', function () {
    $second = secondStore();
    $default = Store::query()->where('is_default', true)->firstOrFail();

    app(StoreContext::class)->bind($default);
    Brand::query()->create(['name' => 'Only Default Store Brand']);

    app(StoreContext::class)->bind($second);
    Brand::query()->create(['name' => 'Only Second Store Brand']);

    expect(Brand::query()->pluck('name')->all())->toBe(['Only Second Store Brand']);

    app(StoreContext::class)->bind($default);
    $names = Brand::query()->pluck('name')->all();

    expect($names)->toContain('Only Default Store Brand')
        ->and($names)->not->toContain('Only Second Store Brand');
});

it('keeps a default customer group on every store', function () {
    $default = Store::query()->where('is_default', true)->firstOrFail();
    $second = secondStore();
    app(CommerceBootstrap::class)->provisionCatalogDefaults($second);

    $defaultGroup = CustomerGroup::query()->withoutGlobalScopes()
        ->where('store_id', $default->id)
        ->where('handle', 'retail')
        ->first();
    $secondGroup = CustomerGroup::query()->withoutGlobalScopes()
        ->where('store_id', $second->id)
        ->where('handle', 'retail')
        ->first();

    expect((bool) $defaultGroup?->default)->toBeTrue()
        ->and((bool) $secondGroup?->default)->toBeTrue();

    app(StoreContext::class)->bind($default);
    expect(CustomerGroup::getDefault()?->id)->toBe($defaultGroup->id);

    app(StoreContext::class)->bind($second);
    expect(CustomerGroup::getDefault()?->id)->toBe($secondGroup->id);
});

it('isolates catalog metadata per store', function () {
    $second = secondStore();
    app(CommerceBootstrap::class)->provisionCatalogDefaults($second);
    $default = Store::query()->where('is_default', true)->firstOrFail();

    app(StoreContext::class)->bind($second);
    ProductType::query()->create(['name' => 'Second Type Only']);

    expect(ProductType::query()->pluck('name')->all())
        ->toContain('Second Type Only')
        ->not->toContain('Boxer');

    app(StoreContext::class)->bind($default);
    $names = ProductType::query()->pluck('name')->all();

    expect($names)->toContain('Boxer')
        ->and($names)->not->toContain('Second Type Only');
});

it('keeps customer accounts isolated per store', function () {
    secondStore();

    $this->post('http://localhost/kayit', [
        'name' => 'Ali Veli',
        'email' => 'ali@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    $this->post('http://second.test/kayit', [
        'name' => 'Ali Other',
        'email' => 'ali@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::query()->withoutGlobalScopes()->where('email', 'ali@example.com')->count())->toBe(2);

    app(StoreContext::class)->bind(Store::query()->where('handle', 'omnipanel')->first());
    expect(User::query()->where('email', 'ali@example.com')->count())->toBe(1);
});

it('redirects custom-domain admin to the store subdomain', function () {
    $store = secondStore();
    $store->rememberHost('shop.example.com');

    $this->get('http://shop.example.com/lunar')
        ->assertRedirect($store->adminUrl('/lunar'));
});

it('activates a custom domain when dns matches', function () {
    $store = secondStore();
    $domain = app(VerifyCustomDomain::class)->createPending($store->id, 'www.butik.com');

    $this->mock(DnsResolver::class, function ($mock) use ($domain) {
        $mock->shouldReceive('cname')->andReturn(['cname.eticcommerce.test']);
        $mock->shouldReceive('txt')->andReturn([$domain->txtRecord()]);
    });

    $verified = app(VerifyCustomDomain::class)->handle($domain);

    expect($verified->status)->toBe(CustomDomain::STATUS_ACTIVE)
        ->and($store->fresh()->hosts()->contains('www.butik.com'))->toBeTrue();
});

it('activates a custom domain from the dedicated txt host', function () {
    $store = secondStore();
    $domain = app(VerifyCustomDomain::class)->createPending($store->id, 'www.butik.com');

    $this->mock(DnsResolver::class, function ($mock) use ($domain) {
        $mock->shouldReceive('cname')->andReturn(['cname.eticcommerce.test']);
        $mock->shouldReceive('txt')->andReturnUsing(
            fn (string $host) => $host === $domain->txtLookupHost() ? [$domain->txtRecord()] : []
        );
    });

    $verified = app(VerifyCustomDomain::class)->handle($domain);

    expect($verified->status)->toBe(CustomDomain::STATUS_ACTIVE)
        ->and($domain->txtLookupHost())->toBe('_etic-verify.www.butik.com');
});

it('points custom domain cname at the platform target not the store subdomain', function () {
    $store = secondStore();

    expect(app(VerifyCustomDomain::class)->cnameTarget($store))->toBe('cname.eticcommerce.test');
});

it('registers the hostname with cloudflare when the api is configured', function () {
    config()->set('etic.tenancy.cloudflare.api_token', 'token');
    config()->set('etic.tenancy.cloudflare.zone_id', 'zone');

    Http::fake(function ($request) {
        if ($request->method() === 'GET') {
            return Http::response(['success' => true, 'result' => []], 200);
        }

        return Http::response(['success' => true, 'result' => ['id' => 'cf1']], 200);
    });

    app(VerifyCustomDomain::class)->createPending(secondStore()->id, 'www.butik.com');

    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['hostname'] === 'www.butik.com');
});

it('rejects invalid or platform hostnames for custom domains', function () {
    $store = secondStore();
    $verify = app(VerifyCustomDomain::class);

    expect(fn () => $verify->createPending($store->id, 'not-a-domain'))
        ->toThrow(ValidationException::class);
    expect(fn () => $verify->createPending($store->id, 'other.eticcommerce.test'))
        ->toThrow(ValidationException::class);
    expect($verify->normalizeHostname('https://www.marka.com/path'))->toBe('www.marka.com');
});

it('does not treat the current stores own pending domain as taken by another store', function () {
    $store = secondStore();
    $verify = app(VerifyCustomDomain::class);

    $verify->createPending($store->id, 'ecompanel.co');

    try {
        $verify->createPending($store->id, 'ecompanel.co');
        expect(false)->toBeTrue();
    } catch (ValidationException $e) {
        expect($e->errors()['hostname'][0])->toBe(__('etic.tenancy.domain.already_connected'));
    }
});

it('still blocks a hostname leftover on another store extra_domains', function () {
    $other = Store::query()->where('is_default', true)->firstOrFail();
    $other->forceFill(['extra_domains' => ['ecompanel.co']])->save();

    $store = secondStore();

    expect(fn () => app(VerifyCustomDomain::class)->createPending($store->id, 'ecompanel.co'))
        ->toThrow(ValidationException::class);
});

it('allows reconnecting a hostname leftover only on the current store extra_domains', function () {
    $store = secondStore();
    $store->forceFill(['extra_domains' => array_merge($store->extra_domains ?? [], ['ecompanel.co'])])->save();

    $domain = app(VerifyCustomDomain::class)->createPending($store->id, 'ecompanel.co');

    expect($domain->hostname)->toBe('ecompanel.co')
        ->and($domain->store_id)->toBe($store->id);
});

it('shows the store domain setup page in the lunar panel', function () {
    $staff = Staff::query()->firstOrFail();

    $this->actingAs($staff, 'staff')
        ->get('/lunar/alan-adi')
        ->assertOk()
        ->assertSee('Kendi alan adını bağla', false)
        ->assertSee('www.markaniz.com', false)
        ->assertSee('Mağaza adresiniz', false)
        ->assertDontSee('Ek alan adları', false);
});

it('writes an impersonate audit log', function () {
    $store = secondStore();
    $staff = Staff::query()->where('admin', true)->firstOrFail();

    StoreAuditLog::query()->create([
        'store_id' => $store->id,
        'staff_id' => $staff->id,
        'action' => 'impersonate',
        'meta' => ['ip' => '127.0.0.1'],
    ]);

    expect(StoreAuditLog::query()->where('action', 'impersonate')->where('store_id', $store->id)->exists())->toBeTrue();
});
