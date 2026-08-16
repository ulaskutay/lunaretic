<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\MenuItemRepeater;
use App\Etic\CMS\Filament\Resources\MenuResource\Pages;
use App\Etic\CMS\Models\Menu;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
            Section::make(__('etic.filament.menus.details'))
                ->description(__('etic.filament.menus.details_help'))
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    ChannelSelect::make(),
                    TextInput::make('name')
                        ->label(__('etic.filament.menus.name'))
                        ->required()
                        ->maxLength(191)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if (filled($get('handle')) || ! filled($state)) {
                                return;
                            }

                            $set('handle', Str::slug($state));
                        }),
                    TextInput::make('handle')
                        ->label(__('etic.filament.menus.handle'))
                        ->required()
                        ->alphaDash()
                        ->maxLength(80)
                        ->columnSpan(1),
                ]),
            Section::make(__('etic.filament.menus.structure'))
                ->description(__('etic.filament.menus.structure_help'))
                ->icon('heroicon-o-squares-2x2')
                ->schema([
                    MenuItemRepeater::make(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('etic.filament.menus.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('handle')
                ->label(__('etic.filament.menus.handle'))
                ->badge()
                ->color('gray'),
            TextColumn::make('all_items_count')
                ->counts('allItems')
                ->label(__('etic.filament.menus.item_count')),
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
