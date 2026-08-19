<?php

namespace App\Etic\Integrations\Shipping\Filament\Pages;

use App\Etic\Integrations\Shipping\ShippingCarrierCatalog;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Integrations\Shipping\ShippingRates;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ShippingSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'kargo-ayarlari';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?string $expandedCarrier = null;

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.shipping.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.shipping.plural');
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
                ->visible(false)
                ->live()
                ->afterStateUpdated(fn (?string $state) => $this->fillFromStore($state))
                ->dehydrated(false),
            Section::make(__('etic.filament.shipping.section'))
                ->description(__('etic.filament.shipping.help'))
                ->extraAttributes(['class' => 'etic-shipping-rates-card'])
                ->schema([
                    Repeater::make('rates')
                        ->label(__('etic.filament.shipping.rates'))
                        ->required()
                        ->minItems(1)
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->schema([
                            TextInput::make('name')
                                ->label(__('etic.filament.shipping.name'))
                                ->required()
                                ->maxLength(80),
                            TextInput::make('identifier')
                                ->label(__('etic.filament.shipping.identifier'))
                                ->required()
                                ->alphaDash()
                                ->maxLength(40),
                            TextInput::make('description')
                                ->label(__('etic.filament.shipping.description'))
                                ->maxLength(191)
                                ->default('Türkiye içi teslimat'),
                            TextInput::make('price_tl')
                                ->label(__('etic.filament.shipping.price'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix('₺')
                                ->required(),
                            TextInput::make('max_subtotal_tl')
                                ->label(__('etic.filament.shipping.max_subtotal'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix('₺')
                                ->helperText(__('etic.filament.shipping.max_subtotal_help')),
                        ])
                        ->columns(2),
                ]),
            Section::make(__('etic.filament.shipping.carrier_library'))
                ->description(__('etic.filament.shipping.carrier_library_help'))
                ->extraAttributes(['class' => 'etic-shipping-carriers-section'])
                ->schema($this->carrierAccordionItems()),
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
                            ->label(__('etic.filament.shipping.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function toggleCarrier(string $carrier): void
    {
        if (ShippingCarrierCatalog::find($carrier) === null) {
            return;
        }

        $this->expandedCarrier = $this->expandedCarrier === $carrier ? null : $carrier;
    }

    public function toggleCarrierEnabled(string $carrier): void
    {
        if (ShippingCarrierCatalog::find($carrier) === null) {
            return;
        }

        $field = "{$carrier}_enabled";
        $this->data[$field] = ! (bool) ($this->data[$field] ?? false);
        $this->expandedCarrier = $carrier;
    }

    public function save(): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $state = $this->form->getState();
        $rates = app(ShippingRates::class);
        $rates->save($rates->fromFormState($state['rates'] ?? []));

        app(ShippingCredentials::class)->saveAras([
            'enabled' => (bool) ($state['aras_enabled'] ?? false),
            'username' => $state['aras_username'] ?? null,
            'password' => $state['aras_password'] ?? null,
            'customer_code' => $state['aras_customer_code'] ?? null,
            'test_mode' => (bool) ($state['aras_test_mode'] ?? true),
            'default_weight_kg' => (float) ($state['aras_default_weight_kg'] ?? 1),
            'default_piece_count' => (int) ($state['aras_default_piece_count'] ?? 1),
            'mark_dispatched' => (bool) ($state['aras_mark_dispatched'] ?? true),
        ]);

        app(ShippingCredentials::class)->saveSurat([
            'enabled' => (bool) ($state['surat_enabled'] ?? false),
            'username' => $state['surat_username'] ?? null,
            'password' => $state['surat_password'] ?? null,
            'web_password' => $state['surat_web_password'] ?? null,
            'test_mode' => (bool) ($state['surat_test_mode'] ?? true),
            'default_weight_kg' => (float) ($state['surat_default_weight_kg'] ?? 1),
            'default_piece_count' => (int) ($state['surat_default_piece_count'] ?? 1),
            'mark_dispatched' => (bool) ($state['surat_mark_dispatched'] ?? true),
        ]);

        app(ShippingCredentials::class)->saveMng([
            'enabled' => (bool) ($state['mng_enabled'] ?? false),
            'client_id' => $state['mng_client_id'] ?? null,
            'client_secret' => $state['mng_client_secret'] ?? null,
            'customer_number' => $state['mng_customer_number'] ?? null,
            'password' => $state['mng_password'] ?? null,
            'default_city_code' => (int) ($state['mng_default_city_code'] ?? 34),
            'default_district_code' => (int) ($state['mng_default_district_code'] ?? 100),
            'test_mode' => (bool) ($state['mng_test_mode'] ?? true),
            'default_weight_kg' => (float) ($state['mng_default_weight_kg'] ?? 1),
            'default_piece_count' => (int) ($state['mng_default_piece_count'] ?? 1),
            'mark_dispatched' => (bool) ($state['mng_mark_dispatched'] ?? true),
        ]);

        app(ShippingCredentials::class)->saveYurtici([
            'enabled' => (bool) ($state['yurtici_enabled'] ?? false),
            'username' => $state['yurtici_username'] ?? null,
            'password' => $state['yurtici_password'] ?? null,
            'test_mode' => (bool) ($state['yurtici_test_mode'] ?? true),
            'default_weight_kg' => (float) ($state['yurtici_default_weight_kg'] ?? 1),
            'default_desi' => (float) ($state['yurtici_default_desi'] ?? 1),
            'default_piece_count' => (int) ($state['yurtici_default_piece_count'] ?? 1),
            'mark_dispatched' => (bool) ($state['yurtici_mark_dispatched'] ?? true),
        ]);

        Notification::make()
            ->title(__('etic.filament.shipping.saved'))
            ->success()
            ->send();
    }

    /**
     * @return list<View|Group>
     */
    private function carrierAccordionItems(): array
    {
        $items = [
            View::make('filament.shipping.carrier-accordion-styles'),
        ];

        foreach ($this->carrierSchemas() as $key => $schema) {
            $items[] = View::make('filament.shipping.carrier-accordion-header')
                ->viewData(fn (): array => $this->carrierHeaderViewData($key));

            $items[] = Group::make($schema)
                ->visible(fn (): bool => $this->expandedCarrier === $key)
                ->columns(2)
                ->extraAttributes([
                    'id' => 'carrier-panel-'.$key,
                    'class' => 'etic-shipping-accordion-panel',
                ]);
        }

        return $items;
    }

    /**
     * @return array<string, list<Component>>
     */
    private function carrierSchemas(): array
    {
        return [
            'aras' => [
                Toggle::make('aras_enabled')
                    ->label(__('etic.filament.shipping.aras_enabled')),
                TextInput::make('aras_username')
                    ->label(__('etic.filament.shipping.aras_username'))
                    ->maxLength(80),
                TextInput::make('aras_password')
                    ->label(__('etic.filament.shipping.aras_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.secret_keep'))
                    ->maxLength(120),
                TextInput::make('aras_customer_code')
                    ->label(__('etic.filament.shipping.aras_customer_code'))
                    ->helperText(__('etic.filament.shipping.aras_customer_code_help'))
                    ->maxLength(40),
                Toggle::make('aras_test_mode')
                    ->label(__('etic.filament.shipping.aras_test_mode'))
                    ->helperText(__('etic.filament.shipping.aras_test_mode_help')),
                TextInput::make('aras_default_weight_kg')
                    ->label(__('etic.filament.shipping.aras_weight'))
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->suffix('kg'),
                TextInput::make('aras_default_piece_count')
                    ->label(__('etic.filament.shipping.aras_piece_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(99),
                Toggle::make('aras_mark_dispatched')
                    ->label(__('etic.filament.shipping.aras_mark_dispatched'))
                    ->helperText(__('etic.filament.shipping.aras_mark_dispatched_help')),
            ],
            'surat' => [
                Toggle::make('surat_enabled')
                    ->label(__('etic.filament.shipping.surat_enabled')),
                TextInput::make('surat_username')
                    ->label(__('etic.filament.shipping.surat_username'))
                    ->maxLength(40),
                TextInput::make('surat_password')
                    ->label(__('etic.filament.shipping.surat_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.secret_keep'))
                    ->maxLength(120),
                TextInput::make('surat_web_password')
                    ->label(__('etic.filament.shipping.surat_web_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.surat_web_password_help'))
                    ->maxLength(120),
                Toggle::make('surat_test_mode')
                    ->label(__('etic.filament.shipping.surat_test_mode'))
                    ->helperText(__('etic.filament.shipping.surat_test_mode_help')),
                TextInput::make('surat_default_weight_kg')
                    ->label(__('etic.filament.shipping.surat_weight'))
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->suffix('kg'),
                TextInput::make('surat_default_piece_count')
                    ->label(__('etic.filament.shipping.surat_piece_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(99),
                Toggle::make('surat_mark_dispatched')
                    ->label(__('etic.filament.shipping.surat_mark_dispatched'))
                    ->helperText(__('etic.filament.shipping.surat_mark_dispatched_help')),
            ],
            'mng' => [
                Toggle::make('mng_enabled')
                    ->label(__('etic.filament.shipping.mng_enabled')),
                TextInput::make('mng_client_id')
                    ->label(__('etic.filament.shipping.mng_client_id'))
                    ->maxLength(120),
                TextInput::make('mng_client_secret')
                    ->label(__('etic.filament.shipping.mng_client_secret'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.secret_keep'))
                    ->maxLength(120),
                TextInput::make('mng_customer_number')
                    ->label(__('etic.filament.shipping.mng_customer_number'))
                    ->maxLength(40),
                TextInput::make('mng_password')
                    ->label(__('etic.filament.shipping.mng_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.secret_keep'))
                    ->maxLength(120),
                TextInput::make('mng_default_city_code')
                    ->label(__('etic.filament.shipping.mng_city_code'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('etic.filament.shipping.mng_city_code_help')),
                TextInput::make('mng_default_district_code')
                    ->label(__('etic.filament.shipping.mng_district_code'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('etic.filament.shipping.mng_district_code_help')),
                Toggle::make('mng_test_mode')
                    ->label(__('etic.filament.shipping.mng_test_mode'))
                    ->helperText(__('etic.filament.shipping.mng_test_mode_help')),
                TextInput::make('mng_default_weight_kg')
                    ->label(__('etic.filament.shipping.mng_weight'))
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->suffix('kg'),
                TextInput::make('mng_default_piece_count')
                    ->label(__('etic.filament.shipping.mng_piece_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(99),
                Toggle::make('mng_mark_dispatched')
                    ->label(__('etic.filament.shipping.mng_mark_dispatched'))
                    ->helperText(__('etic.filament.shipping.mng_mark_dispatched_help')),
            ],
            'yurtici' => [
                Toggle::make('yurtici_enabled')
                    ->label(__('etic.filament.shipping.yurtici_enabled')),
                TextInput::make('yurtici_username')
                    ->label(__('etic.filament.shipping.yurtici_username'))
                    ->maxLength(80),
                TextInput::make('yurtici_password')
                    ->label(__('etic.filament.shipping.yurtici_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.shipping.secret_keep'))
                    ->maxLength(120),
                Toggle::make('yurtici_test_mode')
                    ->label(__('etic.filament.shipping.yurtici_test_mode'))
                    ->helperText(__('etic.filament.shipping.yurtici_test_mode_help')),
                TextInput::make('yurtici_default_weight_kg')
                    ->label(__('etic.filament.shipping.yurtici_weight'))
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->suffix('kg'),
                TextInput::make('yurtici_default_desi')
                    ->label(__('etic.filament.shipping.yurtici_desi'))
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1),
                TextInput::make('yurtici_default_piece_count')
                    ->label(__('etic.filament.shipping.yurtici_piece_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(99),
                Toggle::make('yurtici_mark_dispatched')
                    ->label(__('etic.filament.shipping.yurtici_mark_dispatched'))
                    ->helperText(__('etic.filament.shipping.yurtici_mark_dispatched_help')),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function carrierHeaderViewData(string $key): array
    {
        $carrier = ShippingCarrierCatalog::find($key);
        $credentials = app(ShippingCredentials::class);
        $enabled = (bool) ($this->data["{$key}_enabled"] ?? false);
        $complete = $this->carrierLooksComplete($key, $credentials);

        return [
            'carrier' => $carrier,
            'expanded' => $this->expandedCarrier === $key,
            'enabled' => $enabled,
            'status' => ! $enabled
                ? 'disabled'
                : ($complete ? 'enabled' : 'incomplete'),
        ];
    }

    private function carrierLooksComplete(string $key, ShippingCredentials $credentials): bool
    {
        return match ($key) {
            'aras' => filled($this->data['aras_username'] ?? $credentials->aras()['username'])
                && $this->hasStoredOrSubmittedSecret('aras_password', $credentials->aras()['password']),
            'surat' => filled($this->data['surat_username'] ?? $credentials->surat()['username'])
                && $this->hasStoredOrSubmittedSecret('surat_password', $credentials->surat()['password']),
            'mng' => filled($this->data['mng_client_id'] ?? $credentials->mng()['client_id'])
                && filled($this->data['mng_customer_number'] ?? $credentials->mng()['customer_number'])
                && $this->hasStoredOrSubmittedSecret('mng_client_secret', $credentials->mng()['client_secret'])
                && $this->hasStoredOrSubmittedSecret('mng_password', $credentials->mng()['password']),
            'yurtici' => filled($this->data['yurtici_username'] ?? $credentials->yurtici()['username'])
                && $this->hasStoredOrSubmittedSecret('yurtici_password', $credentials->yurtici()['password']),
            default => false,
        };
    }

    private function hasStoredOrSubmittedSecret(string $field, ?string $stored): bool
    {
        return filled($this->data[$field] ?? null) || filled($stored);
    }

    private function fillFromStore(?string $handle): void
    {
        $handle ??= app(StoreContext::class)->handle();
        $this->bindSelectedStore($handle);
        $rates = app(ShippingRates::class);
        $aras = app(ShippingCredentials::class)->aras();
        $surat = app(ShippingCredentials::class)->surat();
        $mng = app(ShippingCredentials::class)->mng();
        $yurtici = app(ShippingCredentials::class)->yurtici();

        $this->form->fill([
            'store_handle' => $handle,
            'rates' => $rates->toFormState($rates->all()),
            'aras_enabled' => $aras['enabled'],
            'aras_username' => $aras['username'],
            'aras_password' => null,
            'aras_customer_code' => $aras['customer_code'],
            'aras_test_mode' => $aras['test_mode'],
            'aras_default_weight_kg' => $aras['default_weight_kg'],
            'aras_default_piece_count' => $aras['default_piece_count'],
            'aras_mark_dispatched' => $aras['mark_dispatched'],
            'surat_enabled' => $surat['enabled'],
            'surat_username' => $surat['username'],
            'surat_password' => null,
            'surat_web_password' => null,
            'surat_test_mode' => $surat['test_mode'],
            'surat_default_weight_kg' => $surat['default_weight_kg'],
            'surat_default_piece_count' => $surat['default_piece_count'],
            'surat_mark_dispatched' => $surat['mark_dispatched'],
            'mng_enabled' => $mng['enabled'],
            'mng_client_id' => $mng['client_id'],
            'mng_client_secret' => null,
            'mng_customer_number' => $mng['customer_number'],
            'mng_password' => null,
            'mng_default_city_code' => $mng['default_city_code'],
            'mng_default_district_code' => $mng['default_district_code'],
            'mng_test_mode' => $mng['test_mode'],
            'mng_default_weight_kg' => $mng['default_weight_kg'],
            'mng_default_piece_count' => $mng['default_piece_count'],
            'mng_mark_dispatched' => $mng['mark_dispatched'],
            'yurtici_enabled' => $yurtici['enabled'],
            'yurtici_username' => $yurtici['username'],
            'yurtici_password' => null,
            'yurtici_test_mode' => $yurtici['test_mode'],
            'yurtici_default_weight_kg' => $yurtici['default_weight_kg'],
            'yurtici_default_desi' => $yurtici['default_desi'],
            'yurtici_default_piece_count' => $yurtici['default_piece_count'],
            'yurtici_mark_dispatched' => $yurtici['mark_dispatched'],
        ]);
    }

    private function bindSelectedStore(?string $handle): void
    {
        if (filled($handle)) {
            app(StoreContext::class)->bindByHandle($handle);
        }
    }
}
