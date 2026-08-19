<?php

namespace App\Etic\Support;

use App\Etic\Catalog\AssignProductAvailability;
use App\Etic\Catalog\Filament\EditProductExtension;
use App\Etic\Catalog\Filament\ListProductsExtension;
use App\Etic\Catalog\Filament\Pages\ImportProductsPage;
use App\Etic\Catalog\Filament\ProductResourceExtension;
use App\Etic\Catalog\Models\Attribute as EticAttribute;
use App\Etic\Catalog\Models\AttributeGroup as EticAttributeGroup;
use App\Etic\Catalog\Models\Brand as EticBrand;
use App\Etic\Catalog\Models\CollectionGroup as EticCollectionGroup;
use App\Etic\Catalog\Models\CustomerGroup as EticCustomerGroup;
use App\Etic\Catalog\Models\Product as EticProduct;
use App\Etic\Catalog\Models\ProductOption as EticProductOption;
use App\Etic\Catalog\Models\ProductOptionValue as EticProductOptionValue;
use App\Etic\Catalog\Models\ProductType as EticProductType;
use App\Etic\Catalog\Models\TaxClass as EticTaxClass;
use App\Etic\CMS\Filament\Resources\BlogCategoryResource;
use App\Etic\CMS\Filament\Resources\BlogPostResource;
use App\Etic\CMS\Filament\Resources\MenuResource;
use App\Etic\CMS\Filament\Resources\PageResource;
use App\Etic\Integrations\Marketing\Filament\Pages\MarketingSettings;
use App\Etic\Integrations\Marketing\Http\Middleware\HydrateTracking;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Integrations\Marketing\TrackingSettings;
use App\Etic\Integrations\Payments\Filament\Pages\PaymentSettings;
use App\Etic\Integrations\Payments\IyzicoPaymentType;
use App\Etic\Integrations\Payments\PaytrPaymentType;
use App\Etic\Integrations\Shipping\Filament\Pages\ShippingSettings;
use App\Etic\Integrations\Shipping\ShippingProviderInterface;
use App\Etic\Integrations\Shipping\TableRateShippingModifier;
use App\Etic\Integrations\Shipping\TableRateShippingProvider;
use App\Etic\Media\MediaRelationManagerExtension;
use App\Etic\Media\RemoteImageDownloader;
use App\Etic\Media\StoreMediaPathGenerator;
use App\Etic\Orders\Filament\ListOrdersExtension;
use App\Etic\Orders\Filament\ManageOrderExtension;
use App\Etic\Search\CatalogProductSearch;
use App\Etic\SEO\Filament\Resources\RedirectResource;
use App\Etic\Store\Filament\Pages\DomainSettingsPage;
use App\Etic\Store\Filament\Resources\CustomDomainResource;
use App\Etic\Store\Filament\Resources\StoreResource;
use App\Etic\Store\Http\Middleware\EnsureStoreStaff;
use App\Etic\Storefront\Livewire\AddToCart;
use App\Etic\Storefront\Livewire\MiniCart;
use App\Etic\Theme\ActiveTheme;
use App\Etic\Theme\Filament\Pages\ThemeSettingsPage;
use App\Etic\Theme\ThemeRegistry;
use App\Etic\Theme\ThemeSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Scout;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ListOrders;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\EditProduct;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Admin\Support\RelationManagers\MediaRelationManager;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Facades\Payments;
use Lunar\Facades\Telemetry;
use Lunar\Models\Collection;
use Lunar\Models\Contracts\Attribute as AttributeContract;
use Lunar\Models\Contracts\AttributeGroup as AttributeGroupContract;
use Lunar\Models\Contracts\Brand as BrandContract;
use Lunar\Models\Contracts\CollectionGroup as CollectionGroupContract;
use Lunar\Models\Contracts\CustomerGroup as CustomerGroupContract;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Contracts\ProductOption as ProductOptionContract;
use Lunar\Models\Contracts\ProductOptionValue as ProductOptionValueContract;
use Lunar\Models\Contracts\ProductType as ProductTypeContract;
use Lunar\Models\Contracts\TaxClass as TaxClassContract;
use Lunar\Models\Discount;
use Lunar\Models\Order;
use Lunar\Models\TaxClass as LunarTaxClass;
use Meilisearch\Client as MeilisearchClient;

class EticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('etic.php'), 'etic');

        $maxUploadKb = max(1024, (int) config('etic.media.max_upload_kb', 51200));
        config([
            'livewire.temporary_file_upload.disk' => 'local',
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.$maxUploadKb],
            'livewire.temporary_file_upload.max_upload_time' => 10,
            'media-library.max_file_size' => $maxUploadKb * 1024,
            'media-library.queue_conversions_by_default' => true,
            'media-library.media_downloader' => RemoteImageDownloader::class,
            'media-library.path_generator' => StoreMediaPathGenerator::class,
        ]);

        $this->app->singleton(CatalogProductSearch::class);
        $this->app->singleton(MeilisearchClient::class, function ($app) {
            $config = $app['config']->get('scout.meilisearch');
            $factory = new HttpFactory;

            return new MeilisearchClient(
                $config['host'],
                $config['key'],
                new GuzzleHttpClient([
                    'timeout' => (float) ($config['timeout'] ?? 1.5),
                    'connect_timeout' => (float) ($config['connect_timeout'] ?? 0.4),
                    'http_errors' => false,
                ]),
                $factory,
                [sprintf('Meilisearch Laravel Scout (v%s)', Scout::VERSION)],
                $factory,
            );
        });
        $this->app->singleton(StoreContext::class);
        $this->app->singleton(ThemeRegistry::class);
        $this->app->singleton(ThemeSettings::class);
        $this->app->singleton(ActiveTheme::class);
        $this->app->singleton(TrackingDispatcher::class);
        $this->app->bind(ShippingProviderInterface::class, TableRateShippingProvider::class);

        ModelManifest::replace(ProductContract::class, EticProduct::class);
        ModelManifest::replace(BrandContract::class, EticBrand::class);
        ModelManifest::replace(ProductTypeContract::class, EticProductType::class);
        ModelManifest::replace(ProductOptionContract::class, EticProductOption::class);
        ModelManifest::replace(ProductOptionValueContract::class, EticProductOptionValue::class);
        ModelManifest::replace(AttributeGroupContract::class, EticAttributeGroup::class);
        ModelManifest::replace(AttributeContract::class, EticAttribute::class);
        ModelManifest::replace(CollectionGroupContract::class, EticCollectionGroup::class);
        ModelManifest::replace(CustomerGroupContract::class, EticCustomerGroup::class);
        ModelManifest::replace(TaxClassContract::class, EticTaxClass::class);

        LunarPanel::panel(function ($panel) {
            $navigationGroups = new \ReflectionProperty($panel, 'navigationGroups');
            $navigationGroups->setAccessible(true);
            $navigationGroups->setValue($panel, []);

            return $panel
                ->brandName('Etic Commerce')
                ->databaseNotifications()
                ->databaseNotificationsPolling(null)
                ->navigationGroups([
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.catalog')),
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.sales')),
                    NavigationGroup::make(fn () => __('etic.filament.nav.cms')),
                    NavigationGroup::make(fn () => __('etic.filament.nav.seo')),
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.settings'))
                        ->collapsed(),
                ])
                ->pages([
                    ImportProductsPage::class,
                    ThemeSettingsPage::class,
                    DomainSettingsPage::class,
                    ShippingSettings::class,
                    MarketingSettings::class,
                    PaymentSettings::class,
                ])
                ->resources([
                    PageResource::class,
                    BlogPostResource::class,
                    BlogCategoryResource::class,
                    MenuResource::class,
                    RedirectResource::class,
                    StoreResource::class,
                    CustomDomainResource::class,
                ])
                ->authMiddleware([
                    Authenticate::class,
                    EnsureStoreStaff::class,
                ]);
        })->extensions([
            MediaRelationManager::class => MediaRelationManagerExtension::class,
            ListOrders::class => ListOrdersExtension::class,
            ManageOrder::class => ManageOrderExtension::class,
            ProductResource::class => ProductResourceExtension::class,
            ListProducts::class => ListProductsExtension::class,
            EditProduct::class => EditProductExtension::class,
        ])->register();
    }

    public function boot(): void
    {
        $locale = (string) config('etic.store.locale', 'tr');
        app()->setLocale($locale);
        app()->setFallbackLocale($locale);

        trans('lunarpanel::product.plural_label');
        trans('lunarpanel::productoption.plural_label');
        trans('lunarpanel::auth.roles.admin.label');
        trans('lunarpanel::order.plural_label');
        trans('lunarpanel::relationmanagers.medias.title');

        Lang::addLines([
            'product.form.producttype.label' => 'Kategori',
            'product.table.producttype.label' => 'Kategori',
            'product.tabs.published' => 'Yayında',
            'product.tabs.draft' => 'Taslak',
            'auth.permissions.catalog:manage-products.description' => 'Personelin ürünleri, kategorileri ve markaları düzenlemesine izin verir',
            'order.table.reference.label' => 'Sipariş Numarası',
            'order.form.reference.label' => 'Sipariş Numarası',
            'order.infolist.reference.label' => 'Sipariş Numarası',
            'relationmanagers.medias.actions.create.label' => 'Görseller ekle',
            'relationmanagers.medias.form.media.label' => 'Görseller',
            'relationmanagers.medias.form.primary.label' => 'Kapak görseli',
            'productoption.label' => 'Ürün Seçeneği',
            'productoption.plural_label' => 'Ürün Seçenekleri',
        ], $locale, 'lunarpanel');

        Telemetry::optOut();

        $this->loadViewsFrom(resource_path('themes/'.config('etic.theme', 'default')), 'theme');

        Payments::extend('iyzico', fn ($app) => $app->make(IyzicoPaymentType::class));
        Payments::extend('paytr', fn ($app) => $app->make(PaytrPaymentType::class));

        $this->app->make(ShippingModifiers::class)->add(
            TableRateShippingModifier::class
        );

        Livewire::component('etic.add-to-cart', AddToCart::class);
        Livewire::component('etic.mini-cart', MiniCart::class);

        View::composer(['theme::*', 'components.storefront-layout'], function ($view) {
            $view->with('eticStore', app(StoreContext::class));
            $view->with('eticTheme', app(ActiveTheme::class));
            $view->with('eticTracking', app(TrackingDispatcher::class));
            $view->with('eticTrackingConfig', app(TrackingSettings::class)->resolved());
        });

        Blade::anonymousComponentPath(resource_path('themes/'.config('etic.theme', 'default').'/components'), 'theme');

        $this->app['router']->pushMiddlewareToGroup('web', HydrateTracking::class);

        EticProduct::addGlobalScope('etic_store', new StoreChannelScope);
        Order::addGlobalScope('etic_store', new StoreChannelScope);
        Collection::addGlobalScope('etic_store', new StoreChannelScope);
        Discount::addGlobalScope('etic_store', new StoreChannelScope);

        $attachCurrentChannel = function (Model $model): void {
            $channelId = app(StoreContext::class)->channelId();

            if (! $channelId || ! method_exists($model, 'channels')) {
                return;
            }

            $model->channels()->sync([
                $channelId => [
                    'enabled' => true,
                    'starts_at' => now()->subMinute(),
                    'ends_at' => null,
                ],
            ]);
        };

        EticProduct::created(function (Model $model): void {
            app(AssignProductAvailability::class)->handle($model);
        });
        Collection::created($attachCurrentChannel);
        Discount::created($attachCurrentChannel);

        LunarTaxClass::flushEventListeners();
    }
}
