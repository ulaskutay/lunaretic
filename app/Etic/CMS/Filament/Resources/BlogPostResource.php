<?php

namespace App\Etic\CMS\Filament\Resources;

use App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;
use App\Etic\CMS\Models\BlogPost;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
            ChannelSelect::make(),
            TextInput::make('title')
                ->label(__('etic.filament.posts.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                    if (filled($get('slug')) || ! filled($state)) {
                        return;
                    }

                    $set('slug', Str::slug($state));
                }),
            TextInput::make('slug')
                ->label(__('etic.filament.posts.slug'))
                ->required(),
            TextInput::make('author')
                ->label(__('etic.filament.posts.author')),
            Select::make('blog_category_id')
                ->label(__('etic.filament.posts.category'))
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__('etic.filament.posts.category'))
                        ->required(),
                    TextInput::make('slug')
                        ->label(__('etic.filament.posts.slug'))
                        ->required(),
                ]),
            Select::make('tags')
                ->label(__('etic.filament.posts.tags'))
                ->relationship('tags', 'name')
                ->multiple()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__('etic.filament.posts.tags'))
                        ->required(),
                    TextInput::make('slug')
                        ->label(__('etic.filament.posts.slug'))
                        ->required(),
                ]),
            Textarea::make('excerpt')
                ->label(__('etic.filament.posts.excerpt')),
            FileUpload::make('featured_image')
                ->label(__('etic.filament.posts.image'))
                ->image()
                ->disk('public')
                ->directory('blog')
                ->visibility('public'),
            RichEditor::make('content')
                ->label(__('etic.filament.posts.content')),
            DateTimePicker::make('published_at')
                ->label(__('etic.filament.posts.published_at'))
                ->default(now()),
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
            TextColumn::make('category.name')
                ->label(__('etic.filament.posts.category')),
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
