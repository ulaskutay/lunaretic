<?php

namespace App\Etic\Store\Filament\Resources;

use App\Etic\Store\Actions\VerifyCustomDomain;
use App\Etic\Store\CloudflareCustomHostnames;
use App\Etic\Store\Filament\Resources\CustomDomainResource\Pages;
use App\Etic\Store\Models\CustomDomain;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use App\Etic\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class CustomDomainResource extends Resource
{
    protected static ?string $model = CustomDomain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() === 'platform';
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() === 'platform';
    }

    public static function getModelLabel(): string
    {
        return __('etic.filament.domains.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.domains.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() === 'platform'
            ? __('etic.filament.nav.platform')
            : __('lunarpanel::global.sections.settings');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Filament::getCurrentOrDefaultPanel()?->getId() === 'platform') {
            return $query->withoutGlobalScopes()->with('store');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $isPlatform = Filament::getCurrentOrDefaultPanel()?->getId() === 'platform';

        return $schema->components([
            Select::make('store_id')
                ->label(__('etic.filament.stores.label'))
                ->options(fn () => Store::query()->orderBy('name')->pluck('name', 'id'))
                ->required()
                ->visible($isPlatform)
                ->default(fn () => app(StoreContext::class)->store()?->id)
                ->dehydrated(),
            TextInput::make('hostname')
                ->label(__('etic.filament.domains.hostname'))
                ->placeholder('www.markaniz.com')
                ->required()
                ->maxLength(191)
                ->helperText(function (): string {
                    $target = app(StoreContext::class)->store()?->primary_domain
                        ?: Tenancy::subdomainFor(app(StoreContext::class)->handle());

                    return __('etic.filament.domains.hostname_help', ['target' => $target ?: 'magaza.omnipanel.co']);
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('hostname')
                ->label(__('etic.filament.domains.hostname'))
                ->searchable(),
            TextColumn::make('store.name')
                ->label(__('etic.filament.stores.label'))
                ->visible(fn (): bool => Filament::getCurrentOrDefaultPanel()?->getId() === 'platform'),
            TextColumn::make('status')
                ->label(__('etic.filament.domains.status'))
                ->badge(),
            TextColumn::make('ssl_status')
                ->label(__('etic.filament.domains.ssl')),
            TextColumn::make('verification_token')
                ->label(__('etic.filament.domains.txt'))
                ->formatStateUsing(fn (CustomDomain $record): string => $record->txtRecord())
                ->copyable()
                ->toggleable(),
        ])->recordActions([
            Action::make('verify')
                ->label(__('etic.filament.domains.verify'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action(function (CustomDomain $record): void {
                    try {
                        app(VerifyCustomDomain::class)->handle($record);
                        Notification::make()->title(__('etic.filament.domains.verified'))->success()->send();
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->before(function (CustomDomain $record): void {
                    $record->store?->forgetHost($record->hostname);
                    app(CloudflareCustomHostnames::class)->unregister($record->hostname);
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomDomains::route('/'),
            'create' => Pages\CreateCustomDomain::route('/create'),
        ];
    }
}
