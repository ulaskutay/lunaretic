<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;
use App\Etic\CMS\Models\BlogPost;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return __('etic.filament.posts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.posts.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('etic.filament.nav.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(__('etic.filament.posts.title'))
                ->required(),
            TextInput::make('slug')
                ->label(__('etic.filament.posts.slug'))
                ->required(),
            TextInput::make('author')
                ->label(__('etic.filament.posts.author')),
            Textarea::make('excerpt')
                ->label(__('etic.filament.posts.excerpt')),
            RichEditor::make('content')
                ->label(__('etic.filament.posts.content')),
            DateTimePicker::make('published_at')
                ->label(__('etic.filament.posts.published_at')),
            Toggle::make('is_published')
                ->label(__('etic.filament.posts.published')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label(__('etic.filament.posts.title'))
                ->searchable(),
            IconColumn::make('is_published')
                ->label(__('etic.filament.posts.published'))
                ->boolean(),
            TextColumn::make('published_at')
                ->label(__('etic.filament.posts.published_at'))
                ->dateTime(),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
