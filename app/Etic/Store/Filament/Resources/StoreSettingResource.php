<?php

namespace App\Etic\Store\Filament\Resources;

use App\Etic\Store\Filament\Resources\StoreSettingResource\Pages;
use App\Etic\Store\Models\StoreSetting;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    protected static ?string $model = StoreSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('etic.filament.settings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.settings.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('channel_handle')
                ->label(__('etic.filament.settings.channel'))
                ->required()
                ->default(fn () => config('etic.store.handle')),
            TextInput::make('group')
                ->label(__('etic.filament.settings.group'))
                ->required()
                ->default('general'),
            TextInput::make('key')
                ->label(__('etic.filament.settings.key'))
                ->required(),
            Textarea::make('value')
                ->label(__('etic.filament.settings.value')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('channel_handle')
                ->label(__('etic.filament.settings.channel')),
            TextColumn::make('group')
                ->label(__('etic.filament.settings.group')),
            TextColumn::make('key')
                ->label(__('etic.filament.settings.key')),
            TextColumn::make('value')
                ->label(__('etic.filament.settings.value'))
                ->limit(40),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreSettings::route('/'),
            'create' => Pages\CreateStoreSetting::route('/create'),
            'edit' => Pages\EditStoreSetting::route('/{record}/edit'),
        ];
    }
}
