<?php

namespace App\Etic\Store\Filament\Resources;

use App\Etic\Store\Filament\Resources\StoreResource\Pages;
use App\Etic\Store\Models\Store;
use App\Etic\Store\Models\StoreAuditLog;
use App\Etic\Theme\ThemeRegistry;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('etic.filament.stores.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.stores.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() === 'platform'
            ? __('etic.filament.nav.platform')
            : __('lunarpanel::global.sections.settings');
    }

    public static function canAccess(): bool
    {
        return (bool) auth('staff')->user()?->admin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('etic.filament.stores.name'))
                ->required()
                ->maxLength(191),
            TextInput::make('handle')
                ->label(__('etic.filament.stores.handle'))
                ->required()
                ->alphaDash()
                ->maxLength(80)
                ->unique(ignoreRecord: true)
                ->disabledOn('edit')
                ->helperText(__('etic.filament.stores.handle_help')),
            Section::make(__('etic.filament.stores.login_section'))
                ->description(__('etic.filament.stores.login_help'))
                ->schema([
                    Textarea::make('panel_members')
                        ->label(__('etic.filament.stores.members'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit')
                        ->rows(3),
                    TextInput::make('owner_email')
                        ->label(__('etic.filament.stores.owner_email'))
                        ->email()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(191),
                    TextInput::make('owner_password')
                        ->label(__('etic.filament.stores.owner_password'))
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->helperText(__('etic.filament.stores.owner_password_help')),
                ]),
            TextInput::make('primary_domain')
                ->label(__('etic.filament.stores.primary_domain'))
                ->placeholder('butik.eticcommerce.com')
                ->maxLength(191)
                ->helperText(__('etic.filament.stores.primary_domain_help')),
            Select::make('theme')
                ->label(__('etic.filament.stores.theme'))
                ->options(fn () => app(ThemeRegistry::class)->options())
                ->helperText(__('etic.filament.stores.theme_help'))
                ->default('default')
                ->required()
                ->native(false),
            TextInput::make('locale')
                ->label(__('etic.filament.stores.locale'))
                ->default('tr')
                ->required()
                ->maxLength(12),
            TextInput::make('currency')
                ->label(__('etic.filament.stores.currency'))
                ->default('TRY')
                ->required()
                ->maxLength(8),
            Toggle::make('is_active')
                ->label(__('etic.filament.stores.active'))
                ->default(true),
            Toggle::make('is_default')
                ->label(__('etic.filament.stores.default'))
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('etic.filament.stores.name'))
                ->searchable(),
            TextColumn::make('handle')
                ->label(__('etic.filament.stores.handle')),
            TextColumn::make('primary_domain')
                ->label(__('etic.filament.stores.primary_domain')),
            TextColumn::make('theme')
                ->label(__('etic.filament.stores.theme'))
                ->formatStateUsing(fn (?string $state): string => app(ThemeRegistry::class)->get((string) $state)?->name() ?? (string) $state),
            IconColumn::make('is_active')
                ->label(__('etic.filament.stores.active'))
                ->boolean(),
            IconColumn::make('is_default')
                ->label(__('etic.filament.stores.default'))
                ->boolean(),
        ])->recordActions([
            Action::make('impersonate')
                ->label(__('etic.filament.stores.impersonate'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->action(function (Store $record): mixed {
                    StoreAuditLog::query()->create([
                        'store_id' => $record->id,
                        'staff_id' => auth('staff')->id(),
                        'action' => 'impersonate',
                        'meta' => ['ip' => request()->ip()],
                    ]);

                    return redirect()->away($record->adminUrl());
                }),
            Action::make('suspend')
                ->label(__('etic.filament.stores.suspend'))
                ->icon(Heroicon::OutlinedPause)
                ->requiresConfirmation()
                ->visible(fn (Store $record): bool => $record->is_active)
                ->action(function (Store $record): void {
                    $record->forceFill(['is_active' => false])->save();
                    StoreAuditLog::query()->create([
                        'store_id' => $record->id,
                        'staff_id' => auth('staff')->id(),
                        'action' => 'suspend',
                    ]);
                }),
            Action::make('resume')
                ->label(__('etic.filament.stores.resume'))
                ->icon(Heroicon::OutlinedPlay)
                ->visible(fn (Store $record): bool => ! $record->is_active)
                ->action(function (Store $record): void {
                    $record->forceFill(['is_active' => true, 'suspended_at' => null])->save();
                    StoreAuditLog::query()->create([
                        'store_id' => $record->id,
                        'staff_id' => auth('staff')->id(),
                        'action' => 'resume',
                    ]);
                }),
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
