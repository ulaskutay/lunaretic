<?php

use App\Etic\CMS\MegaMenu;
use App\Etic\CMS\MenuLink;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\MenuItem;
use App\Etic\Support\CommerceBootstrap;
use Lunar\Admin\Models\Staff;

it('builds collection and page urls for menu items', function () {
    expect(MenuLink::collectionUrl(MenuLink::ALL_COLLECTIONS))->toBe('/koleksiyon')
        ->and(MenuLink::collectionUrl('boxer'))->toBe('/koleksiyon/boxer')
        ->and(MenuLink::pageUrl('gizlilik'))->toBe('/sayfa/gizlilik')
        ->and(MenuLink::customUrl('blog'))->toBe('/blog');
});

it('hydrates menu item types from stored urls', function () {
    expect(MenuLink::hydrate(['url' => '/koleksiyon'])['type'])->toBe(MenuLink::COLLECTION)
        ->and(MenuLink::hydrate(['url' => '/koleksiyon'])['collection_key'])->toBe(MenuLink::ALL_COLLECTIONS)
        ->and(MenuLink::hydrate(['url' => '/koleksiyon/boxer'])['collection_key'])->toBe('boxer')
        ->and(MenuLink::hydrate(['url' => '/sayfa/hakkimizda'])['type'])->toBe(MenuLink::PAGE)
        ->and(MenuLink::hydrate(['url' => '/sayfa/hakkimizda'])['page_slug'])->toBe('hakkimizda')
        ->and(MenuLink::hydrate(['url' => '/blog'])['type'])->toBe(MenuLink::CUSTOM);
});

it('strips form-only fields when saving a menu item', function () {
    $saved = MenuLink::dehydrate([
        'label' => 'Boxer',
        'type' => MenuLink::COLLECTION,
        'collection_key' => 'boxer',
        'page_slug' => 'ignored',
        'url' => '/eski',
    ]);

    expect($saved)->toBe([
        'label' => 'Boxer',
        'url' => '/koleksiyon/boxer',
    ]);
});

it('renders the carded menu builder in admin', function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();
    app(CommerceBootstrap::class)->admin();

    $staff = Staff::query()->firstOrFail();
    $menu = Menu::query()->where('handle', 'header')->firstOrFail();

    $this->actingAs($staff, 'staff')
        ->get('/lunar/menus/'.$menu->getKey().'/edit')
        ->assertOk()
        ->assertSee('Menü yapısı')
        ->assertSee('Kategori')
        ->assertSee('Sayfa')
        ->assertSee('Özel bağlantı')
        ->assertSee('Öğe ekle');
});

it('groups mega menu columns from nested items', function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();

    $menu = Menu::query()->where('handle', 'header')->firstOrFail();
    $parent = $menu->items()->firstOrFail();
    $column = MenuItem::query()->create([
        'menu_id' => $menu->id,
        'parent_id' => $parent->id,
        'label' => 'Hazır Giyim',
        'url' => '/koleksiyon',
        'position' => 1,
    ]);
    MenuItem::query()->create([
        'menu_id' => $menu->id,
        'parent_id' => $column->id,
        'label' => 'Boxer',
        'url' => '/koleksiyon/boxer',
        'position' => 1,
    ]);

    $parent->load('children.children');

    expect(MegaMenu::columns($parent))->toMatchArray([
        [
            'title' => 'Hazır Giyim',
            'url' => '/koleksiyon',
            'links' => [
                ['label' => 'Boxer', 'url' => '/koleksiyon/boxer'],
            ],
        ],
    ]);
});

it('inherits the menu id when a nested item is created without one', function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();

    $menu = Menu::query()->where('handle', 'header')->firstOrFail();
    $parent = $menu->items()->firstOrFail();

    $child = $parent->children()->create([
        'label' => 'Boxer',
        'url' => '/koleksiyon/boxer',
        'position' => 1,
    ]);

    expect($child->menu_id)->toBe($menu->id);
});

it('renders nested header menu items on the storefront', function () {
    app(CommerceBootstrap::class)->catalog();
    app(CommerceBootstrap::class)->cms();

    $menu = Menu::query()->where('handle', 'header')->firstOrFail();
    $parent = $menu->items()->firstOrFail();

    MenuItem::query()->create([
        'menu_id' => $menu->id,
        'parent_id' => $parent->id,
        'label' => 'Alt Kategori',
        'url' => '/koleksiyon/boxer',
        'position' => 1,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('etic-header__mega', false)
        ->assertSee('Alt Kategori')
        ->assertSee('/koleksiyon/boxer', false);
});
