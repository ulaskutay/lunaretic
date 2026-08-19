<?php

namespace App\Etic\Platform;

use App\Etic\Store\Filament\Resources\CustomDomainResource;
use App\Etic\Store\Filament\Resources\StoreResource;
use App\Etic\Store\Http\Middleware\EnsurePlatformAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('platform')
            ->path('platform')
            ->brandName('Etic Platform')
            ->login()
            ->authGuard('staff')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->font('Poppins')
            ->navigationGroups([
                NavigationGroup::make(fn () => __('etic.filament.nav.platform')),
            ])
            ->resources([
                StoreResource::class,
                CustomDomainResource::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsurePlatformAdmin::class,
            ]);
    }
}
