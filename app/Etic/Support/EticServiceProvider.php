<?php

namespace App\Etic\Support;

use App\Etic\CMS\Filament\Resources\BlogPostResource;
use App\Etic\CMS\Filament\Resources\MenuResource;
use App\Etic\CMS\Filament\Resources\PageResource;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\Integrations\Payments\IyzicoPaymentType;
use App\Etic\Integrations\Shipping\Filament\Pages\ShippingSettings;
use App\Etic\Integrations\Shipping\ShippingProviderInterface;
use App\Etic\Integrations\Shipping\TableRateShippingModifier;
use App\Etic\Integrations\Shipping\TableRateShippingProvider;
use App\Etic\Media\MediaRelationManagerExtension;
use App\Etic\Orders\Filament\ListOrdersExtension;
use App\Etic\SEO\Filament\Resources\RedirectResource;
use App\Etic\SEO\Http\Middleware\ApplyRedirects;
use App\Etic\Store\Filament\Resources\StoreSettingResource;
use App\Etic\Storefront\Livewire\AddToCart;
use App\Etic\Storefront\Livewire\MiniCart;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ListOrders;
use Lunar\Admin\Support\RelationManagers\MediaRelationManager;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\Payments;
use Lunar\Facades\Telemetry;

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
        ]);

        $this->app->singleton(StoreContext::class);
        $this->app->singleton(TrackingDispatcher::class);
        $this->app->bind(ShippingProviderInterface::class, TableRateShippingProvider::class);

        LunarPanel::panel(function ($panel) {
            $navigationGroups = new \ReflectionProperty($panel, 'navigationGroups');
            $navigationGroups->setAccessible(true);
            $navigationGroups->setValue($panel, []);

            return $panel
                ->brandName('Etic Commerce')
                ->navigationGroups([
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.catalog')),
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.sales')),
                    NavigationGroup::make(fn () => __('etic.filament.nav.cms')),
                    NavigationGroup::make(fn () => __('etic.filament.nav.seo')),
                    NavigationGroup::make(fn () => __('lunarpanel::global.sections.settings'))
                        ->collapsed(),
                ])
                ->pages([
                    ShippingSettings::class,
                ])
                ->resources([
                    PageResource::class,
                    BlogPostResource::class,
                    MenuResource::class,
                    RedirectResource::class,
                    StoreSettingResource::class,
                ]);
        })->extensions([
            MediaRelationManager::class => MediaRelationManagerExtension::class,
            ListOrders::class => ListOrdersExtension::class,
        ])->register();
    }

    public function boot(): void
    {
        $locale = (string) config('etic.store.locale', 'tr');
        app()->setLocale($locale);
        app()->setFallbackLocale($locale);

        trans('lunarpanel::product.plural_label');
        trans('lunarpanel::auth.roles.admin.label');
        trans('lunarpanel::order.plural_label');
        trans('lunarpanel::relationmanagers.medias.title');

        Lang::addLines([
            'product.form.producttype.label' => 'Kategori',
            'product.table.producttype.label' => 'Kategori',
            'auth.permissions.catalog:manage-products.description' => 'Personelin ürünleri, kategorileri ve markaları düzenlemesine izin verir',
            'order.table.reference.label' => 'Sipariş Numarası',
            'order.form.reference.label' => 'Sipariş Numarası',
            'order.infolist.reference.label' => 'Sipariş Numarası',
            'relationmanagers.medias.actions.create.label' => 'Görseller ekle',
            'relationmanagers.medias.form.media.label' => 'Görseller',
            'relationmanagers.medias.form.primary.label' => 'Kapak görseli',
        ], $locale, 'lunarpanel');

        Telemetry::optOut();

        $this->loadViewsFrom(resource_path('themes/'.config('etic.theme', 'default')), 'theme');

        Payments::extend('iyzico', fn ($app) => $app->make(IyzicoPaymentType::class));

        $this->app->make(ShippingModifiers::class)->add(
            TableRateShippingModifier::class
        );

        Livewire::component('etic.add-to-cart', AddToCart::class);
        Livewire::component('etic.mini-cart', MiniCart::class);

        View::composer('theme::*', function ($view) {
            $view->with('eticStore', app(StoreContext::class));
            $view->with('eticTracking', app(TrackingDispatcher::class));
        });

        Blade::anonymousComponentPath(resource_path('themes/'.config('etic.theme', 'default').'/components'), 'theme');

        $this->app['router']->pushMiddlewareToGroup('web', ApplyRedirects::class);
    }
}
