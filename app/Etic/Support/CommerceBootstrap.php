<?php

namespace App\Etic\Support;

use App\Etic\CMS\Models\BlogCategory;
use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Menu;
use App\Etic\CMS\Models\MenuItem;
use App\Etic\CMS\Models\Page;
use App\Etic\Store\Models\Store;
use App\Etic\Store\Models\StoreSetting;
use Illuminate\Support\Facades\DB;
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
use Lunar\Models\TaxZone;
use App\Etic\Support\TaxClassResolver;

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

        $store = Store::query()->firstOrCreate(
            ['handle' => config('etic.store.handle', 'boxers')],
            [
                'name' => config('etic.store.name', 'Etic Commerce'),
                'primary_domain' => Store::normalizeHost(parse_url((string) config('app.url'), PHP_URL_HOST)),
                'theme' => (string) config('etic.theme', 'default'),
                'locale' => (string) config('etic.store.locale', 'tr'),
                'currency' => (string) config('etic.store.currency', 'TRY'),
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $appHost = Store::normalizeHost((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $updates = [];

        if (! $store->is_default || ! $store->is_active) {
            $updates['is_default'] = true;
            $updates['is_active'] = true;
        }

        if ($appHost !== '' && filter_var($appHost, FILTER_VALIDATE_IP) && $store->primary_domain !== $appHost) {
            $updates['primary_domain'] = $appHost;
        }

        if ($updates !== []) {
            $store->forceFill($updates)->save();
        } else {
            $store->syncChannel();
        }

        $channel = Channel::query()->where('handle', $store->handle)->firstOrFail();

        Channel::query()->where('id', '!=', $channel->id)->update(['default' => false]);

        foreach (['etic_pages', 'etic_blog_posts', 'etic_menus', 'etic_blog_categories', 'etic_redirects'] as $table) {
            DB::table($table)->whereNull('channel_id')->update(['channel_id' => $channel->id]);
        }

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

        $taxZone = TaxZone::query()->firstOrCreate(
            ['name' => 'Türkiye'],
            [
                'zone_type' => 'country',
                'price_display' => 'tax_inclusive',
                'default' => true,
                'active' => true,
            ]
        );

        $taxZone->forceFill([
            'price_display' => 'tax_inclusive',
            'default' => true,
            'active' => true,
        ])->save();

        TaxZone::query()->where('id', '!=', $taxZone->id)->update(['default' => false]);

        $taxZone->countries()->firstOrCreate(['country_id' => $country->id]);

        $taxClass = app(TaxClassResolver::class)->forPercentage(
            (int) config('etic.tax.default_rate', 10)
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
                'model_code' => 'BX-KLASIK',
                'attribute_data' => [
                    'name' => new TranslatedText(collect(['tr' => 'Klasik Boxer'])),
                    'description' => new TranslatedText(collect(['tr' => 'Pamuklu, nefes alan klasik boxer.'])),
                ],
            ]
        );

        if (blank($product->model_code)) {
            $product->forceFill(['model_code' => 'BX-KLASIK'])->save();
        }

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

        $channelId = Channel::query()->where('handle', config('etic.store.handle'))->value('id');

        foreach ($pages as $slug => $title) {
            $page = Page::query()->firstOrCreate(
                ['slug' => $slug, 'channel_id' => $channelId],
                [
                    'title' => $title,
                    'template' => $this->cmsPageTemplate($slug),
                    'content' => $this->cmsPageContent($slug, $title),
                    'is_published' => true,
                ]
            );

            $placeholder = '<p>'.$title.' içeriği.</p>';

            if ($page->content === $placeholder) {
                $page->fill([
                    'template' => $this->cmsPageTemplate($slug),
                    'content' => $this->cmsPageContent($slug, $title),
                ])->save();
            } elseif (blank($page->template) || $page->template === 'page') {
                $page->fill(['template' => $this->cmsPageTemplate($slug)])->save();
            }

            $page->seo()->firstOrCreate([], [
                'title' => $title.' | Etic Commerce',
                'description' => $title.' sayfası',
                'robots' => 'index,follow',
            ]);
        }

        $header = Menu::query()->firstOrCreate(
            ['handle' => 'header', 'channel_id' => $channelId],
            ['name' => 'Header']
        );
        $footer = Menu::query()->firstOrCreate(
            ['handle' => 'footer', 'channel_id' => $channelId],
            ['name' => 'Footer']
        );

        if ($header->allItems()->doesntExist()) {
            MenuItem::query()->create(['menu_id' => $header->id, 'label' => 'Ürünler', 'url' => '/koleksiyon', 'position' => 1]);
            MenuItem::query()->create(['menu_id' => $header->id, 'label' => 'Blog', 'url' => '/blog', 'position' => 2]);
            MenuItem::query()->create(['menu_id' => $header->id, 'label' => 'Hakkımızda', 'url' => '/sayfa/hakkimizda', 'position' => 3]);
        }

        if ($footer->allItems()->doesntExist()) {
            MenuItem::query()->create(['menu_id' => $footer->id, 'label' => 'Gizlilik', 'url' => '/sayfa/gizlilik', 'position' => 1]);
            MenuItem::query()->create(['menu_id' => $footer->id, 'label' => 'İade', 'url' => '/sayfa/iade', 'position' => 2]);
        }

        $category = BlogCategory::query()->firstOrCreate(
            ['slug' => 'rehber', 'channel_id' => $channelId],
            ['name' => 'Rehber']
        );

        BlogPost::query()->firstOrCreate(
            ['slug' => 'boxer-rehberi', 'channel_id' => $channelId],
            [
                'title' => 'Doğru boxer nasıl seçilir?',
                'excerpt' => 'Kumaş, beden ve konfor ipuçları.',
                'content' => '<p>Boxer seçiminde kumaş ve beden uyumu önemlidir.</p>',
                'author' => 'Etic Ajans',
                'published_at' => now(),
                'is_published' => true,
                'blog_category_id' => $category->id,
            ]
        );

        StoreSetting::query()->firstOrCreate(
            [
                'channel_handle' => config('etic.store.handle'),
                'group' => 'theme',
                'key' => 'values',
            ],
            ['value' => json_encode(['color_primary' => '#111827'], JSON_UNESCAPED_UNICODE)]
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

    private function cmsPageTemplate(string $slug): string
    {
        return match ($slug) {
            'hakkimizda' => 'story',
            'iletisim' => 'contact',
            'sss' => 'faq',
            'gizlilik', 'kullanim-kosullari', 'kargo', 'iade' => 'legal',
            default => 'page',
        };
    }

    private function cmsPageContent(string $slug, string $title): string
    {
        return match ($slug) {
            'hakkimizda' => <<<'HTML'
<p>Zamansız tasarımlar, özenli detaylar ve günlük yaşama eşlik eden seçkiler üretiyoruz. Her parça, konforu sade bir estetikle bir araya getirmek için tasarlanır.</p>
<h2>Nasıl çalışıyoruz</h2>
<p>Kumaştan dikişe kadar her adımı yakından takip ederiz. Sezon trendlerinden çok, uzun süre giyilen, kolay eşleşen parçalara odaklanırız. Koleksiyonlarımız yavaş evrilir; böylece dolabınıza her sezon yeniden yer açmak zorunda kalmazsınız.</p>
<h2>Kalite ve özen</h2>
<p>Daha az, daha iyi. Dayanıklı kumaşlar, net kalıplar ve onarılabilir detaylarla ürünlerin ömrünü uzatmayı hedefleriz. Atölye sürecinde ölçü, doku ve renk tutarlılığı aynı titizlikle kontrol edilir.</p>
HTML,
            'iletisim' => <<<'HTML'
<p>Sipariş, beden, kargo veya iade sorularınız için ekibimiz size yardımcı olur. Mesajınıza genellikle aynı iş günü içinde dönüş yaparız.</p>
<h2>Çalışma saatleri</h2>
<p>Hafta içi 09:00–18:00. Resmi tatillerde dönüşler bir sonraki iş gününe kayabilir. Acil sipariş güncellemeleri için WhatsApp hattımızı kullanabilirsiniz.</p>
HTML,
            'sss' => <<<'HTML'
<h2>Siparişim ne zaman kargoya verilir?</h2>
<p>Stoktaki ürünler genellikle 1 iş günü içinde kargoya teslim edilir. Yoğun dönemlerde bu süre 2 iş gününe uzayabilir. Kargo takip numarası e-posta ve hesabınıza düşer.</p>
<h2>Hangi kargo firmasıyla gönderim yapıyorsunuz?</h2>
<p>Siparişler yurt içi kargo ağımız üzerinden gönderilir. Teslimat süresi bölgeye göre 1–3 iş günüdür. 500 TL üzeri siparişlerde kargo ücretsizdir.</p>
<h2>İade nasıl yapılır?</h2>
<p>Teslimattan sonra 30 gün içinde, etiketli ve kullanılmamış ürünleri iade edebilirsiniz. İade sayfasındaki adımları izleyin; kargo kodu e-posta ile iletilir.</p>
<h2>Bedenimi emin değilim, ne yapmalıyım?</h2>
<p>Ürün sayfasındaki beden tablosunu kullanın. Hâlâ kararsızsanız müşteri desteğine yazın; kalıp ve kumaş önerisi paylaşırız. Uygun olmayan bedeni 30 gün içinde değiştirebilirsiniz.</p>
<h2>Ödeme güvenli mi?</h2>
<p>Kart ödemeleri 3D Secure ile alınır. Sipariş özeti ve fatura bilgileri hesabınızda saklanır. Kapıda ödeme seçeneği sepet tutarına göre değişiklik gösterebilir.</p>
HTML,
            'gizlilik' => <<<'HTML'
<p>Bu gizlilik metni, sitemizi ziyaret ederken veya alışveriş yaparken toplanan kişisel verilerin nasıl işlendiğini açıklar.</p>
<h2>Toplanan veriler</h2>
<p>Hesap oluşturma, sipariş, bülten ve müşteri desteği süreçlerinde ad, e-posta, teslimat adresi, telefon ve ödeme işlemi için gerekli bilgiler toplanır. Site kullanımına dair çerezler, analiz ve reklam araçları da kullanılabilir.</p>
<h2>Kullanım amacı</h2>
<p>Veriler siparişi tamamlamak, müşteri desteği vermek, yasal yükümlülükleri yerine getirmek ve (izin verdiğinizde) kampanya ile kişiselleştirilmiş deneyim sunmak için işlenir.</p>
<h2>Paylaşım ve saklama</h2>
<p>Ödeme, kargo ve altyapı sağlayıcılarıyla yalnızca hizmetin gerektirdiği ölçüde paylaşım yapılır. Veriler, ilgili mevzuattaki süreler boyunca saklanır. Haklarınız için destek ekibimize yazabilirsiniz.</p>
HTML,
            'kullanim-kosullari' => <<<'HTML'
<p>Siteyi kullanarak aşağıdaki satış ve kullanım koşullarını kabul etmiş olursunuz. Koşullar, siparişin verildiği andaki haliyle geçerlidir.</p>
<h2>Sipariş ve fiyat</h2>
<p>Ürün fiyatları KDV dahildir. Stok ve fiyat bilgisi sipariş onayına kadar güncellenebilir. Ödeme alındıktan sonra sipariş kesinleşir ve e-posta ile teyit edilir.</p>
<h2>Teslimat</h2>
<p>Teslimat, siparişte belirtilen adrese yapılır. Yanlış adres veya teslim alınmayan kargolarda oluşan ek ücretler müşteriye yansıtılabilir.</p>
<h2>Fikri mülkiyet</h2>
<p>Site içeriği, görseller ve marka unsurları izinsiz kopyalanamaz, ticari amaçla kullanılamaz.</p>
HTML,
            'kargo' => <<<'HTML'
<p>Siparişiniz özenle hazırlanır ve anlaşmalı kargo ağımız üzerinden gönderilir. Teslimat süresi bölgeye göre 1–3 iş günüdür.</p>
<h2>Kargo ücreti</h2>
<p>Sepet tutarı 500 TL’nin altındaysa kargo ücreti ödeme adımında görünür. Bu eşiğin üzerindeki siparişlerde kargo ücretsizdir. Kampanya dönemlerinde eşik değişiklik gösterebilir.</p>
<h2>Takip</h2>
<p>Kargo takip numarası, sipariş kargoya verildiğinde e-posta ve hesabınıza iletilir. Teslimat sırasında evde bulunamazsanız kargo şubesinden teslim alabilirsiniz.</p>
HTML,
            'iade' => <<<'HTML'
<p>Teslimattan sonra 14 gün içinde cayma, 30 gün içinde iade veya değişim hakkınız vardır. Ürünün etiketli, kullanılmamış ve orijinal ambalajında olması gerekir.</p>
<h2>İade adımları</h2>
<p>Hesabınız veya destek hattımız üzerinden iade talebi oluşturun. Size iletilen kargo koduyla ürünü ücretsiz gönderebilirsiniz. İade incelendikten sonra tutar, ödeme yönteminize iade edilir.</p>
<h2>İade edilemeyen ürünler</h2>
<p>Hijyen bandı açılmış iç giyim, kişiselleştirilmiş ürünler ve indirimli son satış ürünleri iade kapsamı dışında kalabilir. Detay için siparişinizden bize yazın.</p>
HTML,
            default => '<p>'.$title.' hakkında güncel bilgileri bu sayfada paylaşırız.</p>',
        };
    }
}
