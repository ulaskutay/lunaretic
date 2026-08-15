<?php

namespace App\Etic\SEO\Filament\Resources;

use App\Etic\SEO\Filament\Resources\RedirectResource\Pages;
use App\Etic\SEO\Models\Redirect;
use App\Etic\Support\Filament\ChannelSelect;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('etic.filament.redirects.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('etic.filament.redirects.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('etic.filament.nav.seo');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ChannelSelect::make(),
            TextInput::make('from_path')
                ->label(__('etic.filament.redirects.from'))
                ->required()
                ->prefix('/')
                ->helperText(__('etic.filament.redirects.from_help')),
            TextInput::make('to_url')
                ->label(__('etic.filament.redirects.to'))
                ->required()
                ->helperText(__('etic.filament.redirects.to_help')),
            Select::make('status_code')
                ->label(__('etic.filament.redirects.status'))
                ->options([
                    301 => __('etic.filament.redirects.permanent'),
                    302 => __('etic.filament.redirects.temporary'),
                ])
                ->default(301)
                ->required(),
            Toggle::make('is_active')
                ->label(__('etic.filament.redirects.active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('from_path')
                ->label(__('etic.filament.redirects.from')),
            TextColumn::make('to_url')
                ->label(__('etic.filament.redirects.to')),
            TextColumn::make('status_code')
                ->label(__('etic.filament.redirects.status')),
            IconColumn::make('is_active')
                ->label(__('etic.filament.redirects.active'))
                ->boolean(),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
