<?php

namespace App\Etic\Store\Filament\Pages;

use App\Etic\Store\Actions\VerifyCustomDomain;
use App\Etic\Store\Models\CustomDomain;
use App\Etic\Support\StoreContext;
use App\Etic\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * @property-read Schema $form
 */
class DomainSettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'alan-adi';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() !== 'platform';
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() !== 'platform';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.domains.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.domains.plural');
    }

    public function mount(): void
    {
        $this->form->fill([
            'hostname' => '',
            'store_url' => $this->storeUrl(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('etic.filament.domains.store_url_section'))
                ->description(__('etic.filament.domains.store_url_help'))
                ->schema([
                    TextInput::make('store_url')
                        ->label(__('etic.filament.domains.store_url'))
                        ->disabled()
                        ->dehydrated(false),
                ]),
            Section::make(__('etic.filament.domains.connect_section'))
                ->description(__('etic.filament.domains.connect_help', [
                    'target' => $this->cnameTarget(),
                    'max' => (int) config('etic.tenancy.max_custom_domains', 3),
                ]))
                ->schema([
                    TextInput::make('hostname')
                        ->label(__('etic.filament.domains.hostname'))
                        ->placeholder('www.markaniz.com')
                        ->helperText(__('etic.filament.domains.hostname_help', [
                            'target' => $this->cnameTarget(),
                        ]))
                        ->maxLength(191)
                        ->required(),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('connect')
                ->footer([
                    Actions::make([
                        Action::make('connect')
                            ->label(__('etic.filament.domains.connect'))
                            ->submit('connect'),
                    ]),
                ]),
            View::make('filament.domains.list')
                ->viewData(fn (): array => [
                    'domains' => $this->domains(),
                    'target' => $this->cnameTarget(),
                    'statuses' => [
                        CustomDomain::STATUS_PENDING => __('etic.filament.domains.statuses.pending'),
                        CustomDomain::STATUS_VERIFYING => __('etic.filament.domains.statuses.verifying'),
                        CustomDomain::STATUS_ACTIVE => __('etic.filament.domains.statuses.active'),
                        CustomDomain::STATUS_FAILED => __('etic.filament.domains.statuses.failed'),
                    ],
                ]),
        ]);
    }

    public function connect(): void
    {
        $store = app(StoreContext::class)->store();

        if (! $store) {
            return;
        }

        $hostname = (string) ($this->form->getState()['hostname'] ?? '');

        try {
            app(VerifyCustomDomain::class)->createPending($store->id, $hostname);
        } catch (ValidationException $e) {
            Notification::make()
                ->title($e->validator->errors()->first('hostname') ?: $e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->form->fill([
            'hostname' => '',
            'store_url' => $this->storeUrl(),
        ]);

        Notification::make()
            ->title(__('etic.filament.domains.connected'))
            ->body(__('etic.filament.domains.connected_body'))
            ->success()
            ->send();
    }

    public function verifyDomain(int $id): void
    {
        $domain = CustomDomain::query()->find($id);

        if (! $domain) {
            return;
        }

        try {
            app(VerifyCustomDomain::class)->handle($domain);
            Notification::make()->title(__('etic.filament.domains.verified'))->success()->send();
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function removeDomain(int $id): void
    {
        $domain = CustomDomain::query()->find($id);

        if (! $domain) {
            return;
        }

        app(VerifyCustomDomain::class)->forget($domain);

        Notification::make()
            ->title(__('etic.filament.domains.removed'))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, CustomDomain>
     */
    public function domains(): Collection
    {
        return CustomDomain::query()->latest()->get();
    }

    private function storeUrl(): string
    {
        $store = app(StoreContext::class)->store();

        if (! $store) {
            return '';
        }

        return $store->primaryUrl() ?: ('https://'.(Tenancy::subdomainFor($store->handle) ?: $store->handle));
    }

    private function cnameTarget(): string
    {
        $store = app(StoreContext::class)->store();

        if (! $store) {
            return 'magaza.omnipanel.co';
        }

        return app(VerifyCustomDomain::class)->cnameTarget($store) ?: (string) Tenancy::subdomainFor($store->handle);
    }
}
