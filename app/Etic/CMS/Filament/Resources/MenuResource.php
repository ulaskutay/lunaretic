<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\Resources\MenuResource\Pages;
use App\Etic\CMS\Models\Menu;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 12;

    public static function getModelLabel(): string
    {
        return __('etic.filament.menus.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.menus.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('etic.filament.nav.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ChannelSelect::make(),
            TextInput::make('name')
                ->label(__('etic.filament.menus.name'))
                ->required(),
            TextInput::make('handle')
                ->label(__('etic.filament.menus.handle'))
                ->required(),
            Repeater::make('allItems')
                ->label(__('etic.filament.menus.items'))
                ->relationship()
                ->schema([
                    TextInput::make('label')
                        ->label(__('etic.filament.menus.item_label'))
                        ->required(),
                    TextInput::make('url')
                        ->label(__('etic.filament.menus.item_url'))
                        ->required(),
                    TextInput::make('position')
                        ->label(__('etic.filament.menus.item_position'))
                        ->numeric()
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('etic.filament.menus.name')),
            TextColumn::make('handle')
                ->label(__('etic.filament.menus.handle')),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
