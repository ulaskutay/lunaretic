<?php

namespace App\Etic\Support;

use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\MenuItem;
use App\Etic\CMS\Models\Page;
use App\Etic\Store\Models\StoreSetting;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Lunar\DiscountTypes\AmountOff;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Discount;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxRate;
use Lunar\Models\TaxRateAmount;
use Lunar\Models\TaxZone;

class CommerceBootstrap
{
    public function foundation(): void
    {
        $language = Language::query()->firstOrCreate(
            ['code' => 'tr'],
            ['name' => 'Türkçe', 'default' => true]
        );

        Language::query()->where('id', '!=', $language->id)->update(['default' => false]);

        $currency = Currency::query()->firstOrCreate(
            ['code' => 'TRY'],
            [
                'name' => 'Türk Lirası',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]
        );

        Currency::query()->where('id', '!=', $currency->id)->update(['default' => false]);

        $channel = Channel::query()->firstOrCreate(
            ['handle' => config('etic.store.handle', 'boxers')],
            [
                'name' => config('etic.store.name', 'Etic Commerce'),
                'default' => true,
                'url' => config('app.url'),
            ]
        );

        Channel::query()->where('id', '!=', $channel->id)->update(['default' => false]);

        CustomerGroup::query()->firstOrCreate(
            ['handle' => 'retail'],
            ['name' => 'Perakende', 'default' => true]
        );

        CollectionGroup::query()->firstOrCreate(
            ['handle' => 'kategoriler'],
            ['name' => 'Kategoriler']
        );

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'TR'],
            [
                'name' => 'Turkey',
                'iso3' => 'TUR',
                'phonecode' => '90',
                'capital' => 'Ankara',
                'currency' => 'TRY',
                'native' => 'Türkiye',
                'emoji' => '🇹🇷',
                'emoji_u' => 'U+1F1F9 U+1F1F7',
            ]
        );

        $taxClass = TaxClass::query()->firstOrCreate(
            ['name' => 'KDV'],
            ['default' => true]
        );

        $taxZone = TaxZone::query()->firstOrCreate(
            ['name' => 'Türkiye'],
            [
                'zone_type' => 'country',
                'price_display' => 'tax_inclusive',
                'default' => true,
                'active' => true,
            ]
        );

        $taxZone->countries()->firstOrCreate(['country_id' => $country->id]);

        $taxRate = TaxRate::query()->firstOrCreate(
            ['name' => 'KDV %10', 'tax_zone_id' => $taxZone->id],
            ['priority' => 1]
        );

        TaxRateAmount::query()->firstOrCreate(
            [
                'tax_rate_id' => $taxRate->id,
                'tax_class_id' => $taxClass->id,
            ],
            ['percentage' => 10]
        );

        if (! Attribute::query()->where('handle', 'name')->exists()) {
            $group = AttributeGroup::query()->create([
                'attributable_type' => Product::morphName(),
                'name' => collect(['tr' => 'Detaylar']),
                'handle' => 'details',
                'position' => 1,
            ]);

            $collectionGroup = AttributeGroup::query()->create([
                'attributable_type' => Collection::morphName(),
                'name' => collect(['tr' => 'Detaylar']),
                'handle' => 'collection_details',
                'position' => 1,
            ]);

            foreach ([['name', 'Ad', false], ['description', 'Açıklama', true]] as [$handle, $label, $rich]) {
                Attribute::query()->create([
                    'attribute_type' => Product::morphName(),
                    'attribute_group_id' => $group->id,
                    'position' => $handle === 'name' ? 1 : 2,
                    'name' => ['tr' => $label],
                    'handle' => $handle,
                    'section' => 'main',
                    'type' => TranslatedText::class,
                    'required' => $handle === 'name',
                    'default_value' => null,
                    'configuration' => ['richtext' => $rich],
                    'system' => $handle === 'name',
                    'description' => ['tr' => ''],
                ]);

                Attribute::query()->create([
                    'attribute_type' => Collection::morphName(),
                    'attribute_group_id' => $collectionGroup->id,
                    'position' => $handle === 'name' ? 1 : 2,
                    'name' => ['tr' => $label],
                    'handle' => $handle,
                    'section' => 'main',
                    'type' => TranslatedText::class,
                    'required' => $handle === 'name',
                    'default_value' => null,
                    'configuration' => ['richtext' => $rich],
                    'system' => $handle === 'name',
                    'description' => ['tr' => ''],
                ]);
            }
        }

        $type = ProductType::query()->firstOrCreate(['name' => 'Boxer']);
        $type->mappedAttributes()->sync(
            Attribute::query()->where('attribute_type', Product::morphName())->pluck('id')
        );
    }

    public function catalog(): Product
    {
        $this->foundation();

        $brand = Brand::query()->firstOrCreate(['name' => 'Etic Boxer']);

        $group = CollectionGroup::query()->where('handle', 'kategoriler')->firstOrFail();
        $collection = Collection::query()->firstOrCreate(
            [
                'collection_group_id' => $group->id,
            ],
            [
                'type' => 'main',
                'sort' => 'custom',
                'attribute_data' => [
                    'name' => new TranslatedText(collect(['tr' => 'Boxer'])),
                    'description' => new TranslatedText(collect(['tr' => 'Erkek boxer koleksiyonu'])),
                ],
            ]
        );

        $language = Language::query()->where('code', 'tr')->firstOrFail();
        $collection->urls()->firstOrCreate(
            ['slug' => 'boxer', 'language_id' => $language->id],
            ['default' => true]
        );

        $color = ProductOption::query()->firstOrCreate(
            ['handle' => 'color'],
            [
                'name' => ['tr' => 'Renk'],
                'label' => ['tr' => 'Renk'],
                'shared' => true,
            ]
        );

        $size = ProductOption::query()->firstOrCreate(
            ['handle' => 'size'],
            [
                'name' => ['tr' => 'Beden'],
                'label' => ['tr' => 'Beden'],
                'shared' => true,
            ]
        );

        $colors = collect(['Siyah', 'Beyaz', 'Gri'])->map(function (string $name, int $i) use ($color) {
            return ProductOptionValue::query()->firstOrCreate(
                ['product_option_id' => $color->id, 'position' => $i + 1],
                ['name' => ['tr' => $name]]
            );
        });

        $sizes = collect(['S', 'M', 'L', 'XL'])->map(function (string $name, int $i) use ($size) {
            return ProductOptionValue::query()->firstOrCreate(
                ['product_option_id' => $size->id, 'position' => $i + 1],
                ['name' => ['tr' => $name]]
            );
        });

        $product = Product::query()->firstOrCreate(
            ['brand_id' => $brand->id, 'status' => 'published'],
            [
                'product_type_id' => ProductType::query()->where('name', 'Boxer')->value('id'),
                'attribute_data' => [
                    'name' => new TranslatedText(collect(['tr' => 'Klasik Boxer'])),
                    'description' => new TranslatedText(collect(['tr' => 'Pamuklu, nefes alan klasik boxer.'])),
                ],
            ]
        );

        $product->urls()->firstOrCreate(
            ['slug' => 'klasik-boxer', 'language_id' => $language->id],
            ['default' => true]
        );

        $collection->products()->syncWithoutDetaching([$product->id => ['position' => 1]]);
        $product->productOptions()->syncWithoutDetaching([
            $color->id => ['position' => 1],
            $size->id => ['position' => 2],
        ]);

        $taxClass = TaxClass::getDefault();
        $currency = Currency::query()->where('code', 'TRY')->firstOrFail();

        foreach ($colors as $colorValue) {
            foreach ($sizes as $sizeValue) {
                $sku = 'BX-'.Str::upper(Str::slug($colorValue->translate('name')).'-'.$sizeValue->translate('name'));

                $variant = ProductVariant::query()->firstOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'tax_class_id' => $taxClass->id,
                        'stock' => 25,
                        'purchasable' => 'in_stock',
                        'shippable' => true,
                        'unit_quantity' => 1,
                    ]
                );

                $variant->values()->syncWithoutDetaching([$colorValue->id, $sizeValue->id]);

                if (! $variant->prices()->exists()) {
                    $variant->prices()->create([
                        'price' => 24900,
                        'compare_price' => 34900,
                        'currency_id' => $currency->id,
                        'min_quantity' => 1,
                    ]);
                }
            }
        }

        $discount = Discount::query()->firstOrCreate(
            ['coupon' => 'BOXER10'],
            [
                'name' => 'Hoş geldin',
                'handle' => 'welcome10',
                'type' => AmountOff::class,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
                'uses' => 0,
                'max_uses' => 1000,
                'priority' => 1,
                'stop' => false,
                'data' => [
                    'percentage' => 10,
                    'fixed_value' => false,
                    'min_prices' => ['TRY' => 0],
                ],
            ]
        );

        $discount->channels()->syncWithoutDetaching([
            Channel::query()->where('handle', config('etic.store.handle'))->value('id') => [
                'enabled' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
            ],
        ]);

        $discount->customerGroups()->syncWithoutDetaching([
            CustomerGroup::query()->where('handle', 'retail')->value('id') => [
                'enabled' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
                'visible' => true,
            ],
        ]);

        return $product->fresh(['variants']);
    }

    public function cms(): void
    {
        $pages = [
            'anasayfa' => 'Ana Sayfa',
            'hakkimizda' => 'Hakkımızda',
            'iletisim' => 'İletişim',
            'sss' => 'SSS',
            'gizlilik' => 'Gizlilik',
            'kullanim-kosullari' => 'Kullanım Koşulları',
            'kargo' => 'Kargo',
            'iade' => 'İade',
        ];

        foreach ($pages as $slug => $title) {
            $page = Page::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'content' => '<p>'.$title.' içeriği.</p>',
                    'is_published' => true,
                ]
            );

            $page->seo()->firstOrCreate([], [
                'title' => $title.' | Etic Commerce',
                'description' => $title.' sayfası',
                'robots' => 'index,follow',
            ]);
        }

        $header = Menu::query()->firstOrCreate(['handle' => 'header'], ['name' => 'Header']);
        $footer = Menu::query()->firstOrCreate(['handle' => 'footer'], ['name' => 'Footer']);

        if ($header->allItems()->doesntExist()) {
            MenuItem::query()->create(['menu_id' => $header->id, 'label' => 'Ürünler', 'url' => '/koleksiyon', 'position' => 1]);
            MenuItem::query()->create(['menu_id' => $header->id, 'label' => 'Hakkımızda', 'url' => '/sayfa/hakkimizda', 'position' => 2]);
        }

        if ($footer->allItems()->doesntExist()) {
            MenuItem::query()->create(['menu_id' => $footer->id, 'label' => 'Gizlilik', 'url' => '/sayfa/gizlilik', 'position' => 1]);
            MenuItem::query()->create(['menu_id' => $footer->id, 'label' => 'İade', 'url' => '/sayfa/iade', 'position' => 2]);
        }

        BlogPost::query()->firstOrCreate(
            ['slug' => 'boxer-rehberi'],
            [
                'title' => 'Doğru boxer nasıl seçilir?',
                'excerpt' => 'Kumaş, beden ve konfor ipuçları.',
                'content' => '<p>Boxer seçiminde kumaş ve beden uyumu önemlidir.</p>',
                'author' => 'Etic Ajans',
                'published_at' => now(),
                'is_published' => true,
            ]
        );

        StoreSetting::query()->firstOrCreate(
            [
                'channel_handle' => config('etic.store.handle'),
                'group' => 'theme',
                'key' => 'primary_color',
            ],
            ['value' => '#111827']
        );
    }

    public function admin(): void
    {
        Staff::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@eticcommerce.test')],
            [
                'first_name' => 'Etic',
                'last_name' => 'Admin',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'admin' => true,
            ]
        );
    }
}
