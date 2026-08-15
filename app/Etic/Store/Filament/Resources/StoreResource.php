<?php

namespace App\Etic\Store\Filament\Resources;

use App\Etic\Store\Filament\Resources\StoreResource\Pages;
use App\Etic\Store\Models\Store;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 28;

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
        return __('lunarpanel::global.sections.settings');
    }

    public static function form(Schema $schema): Schema
    {
        $themes = collect(File::directories(resource_path('themes')))
            ->mapWithKeys(function (string $path) {
                $name = basename($path);

                return [$name => $name];
            })
            ->all();

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
                ->unique(ignoreRecord: true),
            TextInput::make('primary_domain')
                ->label(__('etic.filament.stores.primary_domain'))
                ->placeholder('shop.example.com')
                ->maxLength(191),
            TagsInput::make('extra_domains')
                ->label(__('etic.filament.stores.extra_domains'))
                ->placeholder('www.example.com'),
            TextInput::make('theme')
                ->label(__('etic.filament.stores.theme'))
                ->datalist(array_keys($themes))
                ->default('default')
                ->required(),
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
                ->label(__('etic.filament.stores.theme')),
            IconColumn::make('is_active')
                ->label(__('etic.filament.stores.active'))
                ->boolean(),
            IconColumn::make('is_default')
                ->label(__('etic.filament.stores.default'))
                ->boolean(),
        ])->recordActions([
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
