<?php

namespace App\Etic\Support;

use App\Etic\Catalog\Filament\EditProductExtension;
use App\Etic\Catalog\Filament\ListProductsExtension;
use App\Etic\Catalog\Filament\Pages\ImportProductsPage;
use App\Etic\Catalog\Filament\ProductResourceExtension;
use App\Etic\Catalog\Models\Product as EticProduct;
use App\Etic\CMS\Filament\Resources\BlogCategoryResource;
use App\Etic\CMS\Filament\Resources\BlogPostResource;
use App\Etic\CMS\Filament\Resources\MenuResource;
use App\Etic\CMS\Filament\Resources\PageResource;
use App\Etic\Integrations\Marketing\Filament\Pages\MarketingSettings;
use App\Etic\Integrations\Marketing\Http\Middleware\HydrateTracking;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Integrations\Marketing\TrackingSettings;
use App\Etic\Integrations\Payments\IyzicoPaymentType;
use App\Etic\Integrations\Shipping\Filament\Pages\ShippingSettings;
use App\Etic\Integrations\Shipping\ShippingProviderInterface;
use App\Etic\Integrations\Shipping\TableRateShippingModifier;
use App\Etic\Integrations\Shipping\TableRateShippingProvider;
use App\Etic\Media\MediaRelationManagerExtension;
use App\Etic\Media\RemoteImageDownloader;
use App\Etic\Orders\Filament\ListOrdersExtension;
use App\Etic\SEO\Filament\Resources\RedirectResource;
use App\Etic\Store\Filament\Resources\StoreResource;
use App\Etic\Store\Filament\Resources\StoreSettingResource;
use App\Etic\Storefront\Livewire\AddToCart;
use App\Etic\Storefront\Livewire\MiniCart;
use App\Etic\Theme\ActiveTheme;
use App\Etic\Theme\Filament\Pages\ThemeSettingsPage;
use App\Etic\Theme\ThemeRegistry;
use App\Etic\Theme\ThemeSettings;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ListOrders;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\EditProduct;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Admin\Support\RelationManagers\MediaRelationManager;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Facades\Payments;
use Lunar\Facades\Telemetry;
use Lunar\Models\Contracts\Product as ProductContract;

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
        ]);

        $this->app->singleton(StoreContext::class);
        $this->app->singleton(ThemeRegistry::class);
        $this->app->singleton(ThemeSettings::class);
        $this->app->singleton(ActiveTheme::class);
        $this->app->singleton(TrackingDispatcher::class);
        $this->app->bind(ShippingProviderInterface::class, TableRateShippingProvider::class);

        ModelManifest::replace(ProductContract::class, EticProduct::class);

        LunarPanel::panel(function ($panel) {
            $navigationGroups = new \ReflectionProperty($panel, 'navigationGroups');
            $navigationGroups->setAccessible(true);
            $navigationGroups->setValue($panel, []);

            return $panel
                ->brandName('Etic Commerce')
                ->databaseNotifications()
                ->databaseNotificationsPolling('10s')
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
                    ShippingSettings::class,
                    MarketingSettings::class,
                ])
                ->resources([
                    PageResource::class,
                    BlogPostResource::class,
                    BlogCategoryResource::class,
                    MenuResource::class,
                    RedirectResource::class,
                    StoreResource::class,
                    StoreSettingResource::class,
                ]);
        })->extensions([
            MediaRelationManager::class => MediaRelationManagerExtension::class,
            ListOrders::class => ListOrdersExtension::class,
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
    }
}
