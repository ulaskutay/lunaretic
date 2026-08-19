<?php

use App\Etic\Store\Models\Store;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\StoreContext;
use App\Etic\Theme\ThemeRegistry;
use App\Etic\Theme\ThemeSettings;
use Lunar\Admin\Models\Staff;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
});

it('discovers the atelier theme from theme.json', function () {
    $theme = app(ThemeRegistry::class)->get('atelier');

    expect($theme)->not->toBeNull()
        ->and($theme->name())->toBe('Atelier')
        ->and($theme->title())->toBe('Atölye (Atelier)')
        ->and($theme->description())->toContain('Lüks moda')
        ->and($theme->defaults()['header_style'])->toBe('overlay')
        ->and($theme->defaults()['font_heading'])->toBe('playfair')
        ->and($theme->defaults()['hero_enabled'])->toBeTrue()
        ->and($theme->toPickerArray())->toHaveKeys(['handle', 'name', 'title', 'description', 'palette']);
});

it('switches the storefront to the atelier overlay header', function () {
    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'atelier']);

    $this->get('/')
        ->assertOk()
        ->assertSee('etic-header--overlay', false)
        ->assertSee('Playfair Display', false)
        ->assertSee('etic-hero', false)
        ->assertSee('etic-featured', false)
        ->assertSee('etic-product', false)
        ->assertSee('data-etic-search-toggle', false);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.theme.handle', 'atelier')
        ->assertJsonPath('data.theme.header_style', 'overlay')
        ->assertJsonPath('data.theme.css_variables.--etic-font-heading', '"Playfair Display", Georgia, "Times New Roman", serif')
        ->assertJsonPath('data.theme.featured.title', 'Yeni gelenler — Koleksiyon')
        ->assertJsonPath('data.theme.hero.enabled', true)
        ->assertJsonPath('data.theme.editorial.title', 'Unutulmaz bir gece')
        ->assertJsonPath('data.theme.editorial.cta_url', '/koleksiyon')
        ->assertJsonPath('data.theme.editorial_secondary.title', 'Özgür ruhlar için')
        ->assertJsonPath('data.theme.editorial_secondary.cta_url', '/koleksiyon')
        ->assertJsonPath('data.theme.best_sellers.title', 'Çok satanlar')
        ->assertJsonPath('data.theme.best_sellers.cta', 'Tümünü gör')
        ->assertJsonPath('data.theme.best_sellers.url', '/koleksiyon?sort=best_selling')
        ->assertJsonPath('data.theme.banners.left.title', 'Zarif konfor')
        ->assertJsonPath('data.theme.banners.right.title', 'Sezonun dokusu')
        ->assertJsonPath('data.theme.shop_look.title', 'Görünümü tamamla')
        ->assertJsonPath('data.theme.countdown.title', 'Sezon indirimi')
        ->assertJsonPath('data.theme.countdown.ends_at', '2026-11-27T23:59:59+00:00');

    $this->get('/sayfa/hakkimizda')
        ->assertOk()
        ->assertSee('etic-static--story', false)
        ->assertSee('etic-static__intro', false);
});

it('keeps the default header when the store theme is default', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('etic-header--overlay', false)
        ->assertSee('etic-input', false);
});

it('renders a theme preview without publishing it', function () {
    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'default']);

    $this->get('/?theme_preview=atelier')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('etic-header--overlay', false)
        ->assertSee('Playfair Display', false);

    expect(Store::query()->where('handle', 'omnipanel')->value('theme'))->toBe('default');
});

it('discovers the default theme from theme.json', function () {
    $theme = app(ThemeRegistry::class)->get('default');

    expect($theme)->not->toBeNull()
        ->and($theme->name())->toBe('Default')
        ->and($theme->defaults())->toHaveKey('color_primary');
});

it('keeps default and atelier theme settings isolated', function () {
    app(ThemeSettings::class)->save([
        'color_primary' => '#111827',
        'font_heading' => 'display',
        'logo_text' => 'Default Shop',
    ]);

    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'atelier']);
    app(StoreContext::class)->bindByHandle('omnipanel');

    expect(app(ThemeSettings::class)->get('font_heading'))->toBe('playfair')
        ->and(app(ThemeSettings::class)->get('logo_text'))->toBeNull();

    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'default']);
    app(StoreContext::class)->bindByHandle('omnipanel');

    expect(app(ThemeSettings::class)->get('font_heading'))->toBe('display')
        ->and(app(ThemeSettings::class)->get('logo_text'))->toBe('Default Shop');
});

it('shows the theme picker and selected theme settings in admin', function () {
    app(CommerceBootstrap::class)->admin();
    $staff = Staff::query()->firstOrFail();

    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'atelier']);

    $this->actingAs($staff, 'staff')
        ->get('/lunar/tema-ayarlari')
        ->assertOk()
        ->assertSee('Atelier')
        ->assertSee('Default')
        ->assertSee('Aktif')
        ->assertSee('Özelleştir')
        ->assertSee('Yayınla')
        ->assertSee('Ayarları Kaydet')
        ->assertSee('Bölümü göster');
});

it('exposes theme tokens to the storefront html and api', function () {
    app(ThemeSettings::class)->save([
        'color_primary' => '#1a1a1a',
        'announcement' => 'Ücretsiz kargo',
        'logo_text' => 'Boxer Co',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('--etic-color-primary: #1a1a1a', false)
        ->assertSee('Ücretsiz kargo')
        ->assertSee('Boxer Co');

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.theme.handle', 'default')
        ->assertJsonPath('data.theme.css_variables.--etic-color-primary', '#1a1a1a')
        ->assertJsonPath('data.theme.announcement', 'Ücretsiz kargo');
});

it('includes a favicon on home and catalog pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('favicon.svg', false);

    $this->get('/koleksiyon')
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('favicon.svg', false);

    $this->get('/koleksiyon/favicon.ico')->assertNotFound();
});

it('hides disabled atelier sections on the storefront', function () {
    Store::query()->where('handle', 'omnipanel')->update(['theme' => 'atelier']);
    app(StoreContext::class)->bindByHandle('omnipanel');
    app(ThemeSettings::class)->save([
        'hero_enabled' => false,
        'featured_enabled' => false,
        'countdown_enabled' => false,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('etic-hero', false)
        ->assertDontSee('etic-featured', false)
        ->assertDontSee('etic-countdown', false)
        ->assertSee('etic-editorial', false);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.theme.hero.enabled', false)
        ->assertJsonPath('data.theme.featured.enabled', false)
        ->assertJsonPath('data.theme.countdown.enabled', false)
        ->assertJsonPath('data.theme.editorial.enabled', true);
});
