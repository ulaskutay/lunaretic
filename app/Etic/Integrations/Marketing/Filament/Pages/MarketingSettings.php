<?php

namespace App\Etic\Integrations\Marketing\Filament\Pages;

use App\Etic\Integrations\Marketing\TrackingSettings;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class MarketingSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'pazarlama-ayarlari';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.marketing.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.marketing.plural');
    }

    public function mount(): void
    {
        $this->fillFromStore(app(StoreContext::class)->handle());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('store_handle')
                ->label(__('etic.filament.stores.label'))
                ->options(fn () => Store::query()->orderBy('name')->pluck('name', 'handle'))
                ->visible(fn () => Store::query()->count() > 1)
                ->live()
                ->afterStateUpdated(fn (?string $state) => $this->fillFromStore($state))
                ->dehydrated(false),
            Section::make(__('etic.filament.marketing.pixels'))
                ->description(__('etic.filament.marketing.pixels_help'))
                ->schema([
                    TextInput::make('ga4_measurement_id')
                        ->label(__('etic.filament.marketing.ga4'))
                        ->placeholder('G-XXXXXXXX')
                        ->maxLength(40),
                    TextInput::make('gtm_container_id')
                        ->label(__('etic.filament.marketing.gtm'))
                        ->placeholder('GTM-XXXXXXX')
                        ->maxLength(40),
                    TextInput::make('meta_pixel_id')
                        ->label(__('etic.filament.marketing.meta'))
                        ->placeholder('1234567890')
                        ->maxLength(40),
                    Toggle::make('meta_capi_enabled')
                        ->label(__('etic.filament.marketing.capi_enabled'))
                        ->helperText(__('etic.filament.marketing.capi_enabled_help')),
                    TextInput::make('meta_capi_token')
                        ->label(__('etic.filament.marketing.capi_token'))
                        ->password()
                        ->revealable()
                        ->helperText(__('etic.filament.marketing.capi_token_help'))
                        ->maxLength(512),
                    TextInput::make('meta_test_event_code')
                        ->label(__('etic.filament.marketing.capi_test'))
                        ->placeholder('TEST12345')
                        ->helperText(__('etic.filament.marketing.capi_test_help'))
                        ->maxLength(40),
                    TextInput::make('search_console_verification')
                        ->label(__('etic.filament.marketing.search_console'))
                        ->maxLength(120),
                ]),
            Section::make(__('etic.filament.marketing.merchant'))
                ->description(__('etic.filament.marketing.merchant_help'))
                ->schema([
                    Toggle::make('merchant_feed_enabled')
                        ->label(__('etic.filament.marketing.merchant_enabled'))
                        ->default(true),
                    TextInput::make('merchant_feed_url')
                        ->label(__('etic.filament.marketing.merchant_url'))
                        ->disabled()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('etic.filament.marketing.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $state = $this->form->getState();
        app(TrackingSettings::class)->save($state);

        Notification::make()
            ->title(__('etic.filament.marketing.saved'))
            ->success()
            ->send();
    }

    private function fillFromStore(?string $handle): void
    {
        $handle ??= app(StoreContext::class)->handle();
        $this->bindSelectedStore($handle);
        $settings = app(TrackingSettings::class)->resolved();
        unset($settings['meta_capi_token']);

        $this->form->fill([
            'store_handle' => $handle,
            ...$settings,
            'meta_capi_token' => null,
            'merchant_feed_url' => app(StoreContext::class)->primaryUrl().'/feed/google-merchant.xml',
        ]);
    }

    private function bindSelectedStore(?string $handle): void
    {
        if (filled($handle)) {
            app(StoreContext::class)->bindByHandle($handle);
        }
    }
}
