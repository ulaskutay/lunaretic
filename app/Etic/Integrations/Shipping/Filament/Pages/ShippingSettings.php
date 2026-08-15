<?php

namespace App\Etic\Integrations\Shipping\Filament\Pages;

use App\Etic\Integrations\Shipping\ShippingRates;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
class ShippingSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'kargo-ayarlari';

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
                ->live()
                ->afterStateUpdated(fn (?string $state) => $this->fillFromStore($state))
                ->dehydrated(false),
            Section::make(__('etic.filament.shipping.section'))
                ->description(__('etic.filament.shipping.help'))
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

    public function save(): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $state = $this->form->getState();
        $rates = app(ShippingRates::class);
        $rates->save($rates->fromFormState($state['rates'] ?? []));

        Notification::make()
            ->title(__('etic.filament.shipping.saved'))
            ->success()
            ->send();
    }

    private function fillFromStore(?string $handle): void
    {
        $handle ??= app(StoreContext::class)->handle();
        $this->bindSelectedStore($handle);
        $rates = app(ShippingRates::class);

        $this->form->fill([
            'store_handle' => $handle,
            'rates' => $rates->toFormState($rates->all()),
        ]);
    }

    private function bindSelectedStore(?string $handle): void
    {
        if (filled($handle)) {
            app(StoreContext::class)->bindByHandle($handle);
        }
    }
}
