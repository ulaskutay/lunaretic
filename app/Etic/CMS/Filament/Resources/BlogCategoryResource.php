<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\Resources\BlogCategoryResource\Pages;
use App\Etic\CMS\Models\BlogCategory;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 12;

    public static function getModelLabel(): string
    {
        return __('etic.filament.categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.categories.plural');
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
                ->label(__('etic.filament.categories.name'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                    if (filled($get('slug')) || ! filled($state)) {
                        return;
                    }

                    $set('slug', Str::slug($state));
                }),
            TextInput::make('slug')
                ->label(__('etic.filament.categories.slug'))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('etic.filament.categories.name'))
                ->searchable(),
            TextColumn::make('slug')
                ->label(__('etic.filament.categories.slug')),
            TextColumn::make('posts_count')
                ->counts('posts')
                ->label(__('etic.filament.categories.posts')),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
