<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\Resources\PageResource\Pages;
use App\Etic\CMS\Models\Page;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('etic.filament.pages.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.pages.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('etic.filament.nav.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ChannelSelect::make(),
            TextInput::make('title')
                ->label(__('etic.filament.pages.title'))
                ->required()
                ->maxLength(191),
            TextInput::make('slug')
                ->label(__('etic.filament.pages.slug'))
                ->required()
                ->maxLength(191),
            Toggle::make('is_published')
                ->label(__('etic.filament.pages.published'))
                ->default(true),
            RichEditor::make('content')
                ->label(__('etic.filament.pages.content')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label(__('etic.filament.pages.title'))
                ->searchable(),
            TextColumn::make('slug')
                ->label(__('etic.filament.pages.slug')),
            IconColumn::make('is_published')
                ->label(__('etic.filament.pages.published'))
                ->boolean(),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
