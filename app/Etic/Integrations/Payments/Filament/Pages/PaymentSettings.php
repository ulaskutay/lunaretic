<?php

namespace App\Etic\Integrations\Payments\Filament\Pages;

use App\Etic\Integrations\Payments\PaymentCredentials;
use App\Etic\Integrations\Payments\PaymentProviderCatalog;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
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
class PaymentSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'odeme-ayarlari';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?string $expandedProvider = null;

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.payments.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.payments.plural');
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
            Section::make(__('etic.filament.payments.provider_library'))
                ->description(__('etic.filament.payments.provider_library_help'))
                ->extraAttributes(['class' => 'etic-payment-providers-section'])
                ->schema($this->providerAccordionItems()),
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
                            ->label(__('etic.filament.payments.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function toggleProvider(string $provider): void
    {
        if (PaymentProviderCatalog::find($provider) === null) {
            return;
        }

        $this->expandedProvider = $this->expandedProvider === $provider ? null : $provider;
    }

    public function toggleProviderEnabled(string $provider): void
    {
        if (PaymentProviderCatalog::find($provider) === null) {
            return;
        }

        $field = "{$provider}_enabled";
        $this->data[$field] = ! (bool) ($this->data[$field] ?? false);
        $this->expandedProvider = $provider;
    }

    public function save(): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $state = $this->form->getState();
        $credentials = app(PaymentCredentials::class);

        $credentials->savePaytr([
            'enabled' => (bool) ($state['paytr_enabled'] ?? false),
            'merchant_id' => $state['paytr_merchant_id'] ?? null,
            'merchant_key' => $state['paytr_merchant_key'] ?? null,
            'merchant_salt' => $state['paytr_merchant_salt'] ?? null,
            'test_mode' => (int) ($state['paytr_test_mode'] ?? 0),
            'debug_on' => (int) ($state['paytr_debug_on'] ?? 0),
            'no_installment' => (int) ($state['paytr_no_installment'] ?? 0),
            'max_installment' => (int) ($state['paytr_max_installment'] ?? 0),
            'currency' => $state['paytr_currency'] ?? 'TL',
            'lang' => $state['paytr_lang'] ?? 'tr',
            'timeout_limit' => (int) ($state['paytr_timeout_limit'] ?? 30),
        ]);

        $credentials->saveIyzico([
            'enabled' => (bool) ($state['iyzico_enabled'] ?? false),
            'api_key' => $state['iyzico_api_key'] ?? null,
            'secret_key' => $state['iyzico_secret_key'] ?? null,
            'base_url' => $state['iyzico_base_url'] ?? null,
        ]);

        Notification::make()
            ->title(__('etic.filament.payments.saved'))
            ->success()
            ->send();

        $this->fillFromStore($this->data['store_handle'] ?? null);
    }

    /**
     * @return list<View|Group>
     */
    private function providerAccordionItems(): array
    {
        $items = [
            View::make('filament.shipping.carrier-accordion-styles'),
        ];

        foreach ($this->providerSchemas() as $key => $schema) {
            $items[] = View::make('filament.payments.provider-accordion-header')
                ->viewData(fn (): array => $this->providerHeaderViewData($key));

            $items[] = Group::make($schema)
                ->visible(fn (): bool => $this->expandedProvider === $key)
                ->columns(2)
                ->extraAttributes([
                    'id' => 'provider-panel-'.$key,
                    'class' => 'etic-shipping-accordion-panel',
                ]);
        }

        return $items;
    }

    /**
     * @return array<string, list<Component>>
     */
    private function providerSchemas(): array
    {
        return [
            'paytr' => [
                Toggle::make('paytr_enabled')
                    ->label(__('etic.filament.payments.paytr_enabled')),
                TextInput::make('paytr_merchant_id')
                    ->label(__('etic.filament.payments.paytr_merchant_id'))
                    ->maxLength(40),
                TextInput::make('paytr_merchant_key')
                    ->label(__('etic.filament.payments.paytr_merchant_key'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.payments.secret_keep'))
                    ->maxLength(120),
                TextInput::make('paytr_merchant_salt')
                    ->label(__('etic.filament.payments.paytr_merchant_salt'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.payments.secret_keep'))
                    ->maxLength(120),
                Toggle::make('paytr_test_mode')
                    ->label(__('etic.filament.payments.paytr_test_mode'))
                    ->helperText(__('etic.filament.payments.paytr_test_mode_help')),
                Toggle::make('paytr_debug_on')
                    ->label(__('etic.filament.payments.paytr_debug_on'))
                    ->helperText(__('etic.filament.payments.paytr_debug_on_help')),
                Toggle::make('paytr_no_installment')
                    ->label(__('etic.filament.payments.paytr_no_installment'))
                    ->helperText(__('etic.filament.payments.paytr_no_installment_help')),
                TextInput::make('paytr_max_installment')
                    ->label(__('etic.filament.payments.paytr_max_installment'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(12)
                    ->helperText(__('etic.filament.payments.paytr_max_installment_help')),
                Select::make('paytr_currency')
                    ->label(__('etic.filament.payments.paytr_currency'))
                    ->options([
                        'TL' => 'TL',
                        'TRY' => 'TRY',
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ]),
                Select::make('paytr_lang')
                    ->label(__('etic.filament.payments.paytr_lang'))
                    ->options([
                        'tr' => 'Türkçe',
                        'en' => 'English',
                    ]),
                TextInput::make('paytr_timeout_limit')
                    ->label(__('etic.filament.payments.paytr_timeout'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->suffix(__('etic.filament.payments.minutes')),
                TextInput::make('paytr_callback_url')
                    ->label(__('etic.filament.payments.paytr_callback_url'))
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('etic.filament.payments.paytr_callback_help'))
                    ->columnSpanFull(),
            ],
            'iyzico' => [
                Toggle::make('iyzico_enabled')
                    ->label(__('etic.filament.payments.iyzico_enabled')),
                TextInput::make('iyzico_api_key')
                    ->label(__('etic.filament.payments.iyzico_api_key'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.payments.secret_keep'))
                    ->maxLength(120),
                TextInput::make('iyzico_secret_key')
                    ->label(__('etic.filament.payments.iyzico_secret_key'))
                    ->password()
                    ->revealable()
                    ->helperText(__('etic.filament.payments.secret_keep'))
                    ->maxLength(120),
                TextInput::make('iyzico_base_url')
                    ->label(__('etic.filament.payments.iyzico_base_url'))
                    ->url()
                    ->placeholder('https://sandbox-api.iyzipay.com')
                    ->maxLength(191),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function providerHeaderViewData(string $key): array
    {
        $provider = PaymentProviderCatalog::find($key);
        $credentials = app(PaymentCredentials::class);
        $enabled = (bool) ($this->data["{$key}_enabled"] ?? false);
        $complete = $this->providerLooksComplete($key, $credentials);

        return [
            'provider' => $provider,
            'expanded' => $this->expandedProvider === $key,
            'enabled' => $enabled,
            'status' => ! $enabled
                ? 'disabled'
                : ($complete ? 'enabled' : 'incomplete'),
        ];
    }

    private function providerLooksComplete(string $key, PaymentCredentials $credentials): bool
    {
        return match ($key) {
            'paytr' => filled($this->data['paytr_merchant_id'] ?? $credentials->paytr()['merchant_id'])
                && $this->hasStoredOrSubmittedSecret('paytr_merchant_key', $credentials->paytr()['merchant_key'])
                && $this->hasStoredOrSubmittedSecret('paytr_merchant_salt', $credentials->paytr()['merchant_salt']),
            'iyzico' => $this->hasStoredOrSubmittedSecret('iyzico_api_key', $credentials->iyzico()['api_key'])
                && $this->hasStoredOrSubmittedSecret('iyzico_secret_key', $credentials->iyzico()['secret_key']),
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

        $credentials = app(PaymentCredentials::class);
        $paytr = $credentials->paytr();
        $iyzico = $credentials->iyzico();

        $this->form->fill([
            'store_handle' => $handle,
            'paytr_enabled' => $paytr['enabled'],
            'paytr_merchant_id' => $paytr['merchant_id'],
            'paytr_merchant_key' => null,
            'paytr_merchant_salt' => null,
            'paytr_test_mode' => (bool) $paytr['test_mode'],
            'paytr_debug_on' => (bool) $paytr['debug_on'],
            'paytr_no_installment' => (bool) $paytr['no_installment'],
            'paytr_max_installment' => $paytr['max_installment'],
            'paytr_currency' => $paytr['currency'],
            'paytr_lang' => $paytr['lang'],
            'paytr_timeout_limit' => $paytr['timeout_limit'],
            'paytr_callback_url' => route('paytr.callback', absolute: true),
            'iyzico_enabled' => $iyzico['enabled'],
            'iyzico_api_key' => null,
            'iyzico_secret_key' => null,
            'iyzico_base_url' => $iyzico['base_url'],
        ]);
    }

    private function bindSelectedStore(?string $handle): void
    {
        if (filled($handle)) {
            app(StoreContext::class)->bindByHandle($handle);
        }
    }
}
