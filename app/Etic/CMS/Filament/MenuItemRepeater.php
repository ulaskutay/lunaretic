<?php

namespace App\Etic\CMS\Filament;

use App\Etic\CMS\CmsPageLayout;
use App\Etic\CMS\MenuLink;
use App\Etic\CMS\Models\MenuItem;
use App\Etic\CMS\Models\Page;
use App\Etic\Support\StoreContext;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Lunar\Models\Collection;

class MenuItemRepeater
{
    public static function make(string $name = 'items', int $depth = 0): Repeater
    {
        $repeater = Repeater::make($name)
            ->relationship()
            ->orderColumn('position')
            ->defaultItems(0)
            ->collapsible()
            ->compact()
            ->cloneable()
            ->reorderable()
            ->addActionLabel(match ($depth) {
                0 => __('etic.filament.menus.add_item'),
                1 => __('etic.filament.menus.add_child'),
                default => __('etic.filament.menus.add_link'),
            })
            ->itemLabel(fn (array $state): string => filled($state['label'] ?? null)
                ? (string) $state['label']
                : __('etic.filament.menus.new_item'))
            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => MenuLink::hydrate($data))
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => MenuLink::dehydrate($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => MenuLink::dehydrate($data))
            ->schema(static::fields($depth))
            ->columnSpanFull();

        if ($depth === 0) {
            $repeater->label(__('etic.filament.menus.items'));
        }

        return $repeater;
    }

    /**
     * @return array<int, mixed>
     */
    private static function fields(int $depth): array
    {
        $fields = [
            ToggleButtons::make('type')
                ->label(__('etic.filament.menus.item_type'))
                ->options(MenuLink::types())
                ->icons([
                    MenuLink::COLLECTION => 'heroicon-o-squares-2x2',
                    MenuLink::PAGE => 'heroicon-o-document-text',
                    MenuLink::CUSTOM => 'heroicon-o-link',
                ])
                ->colors([
                    MenuLink::COLLECTION => 'primary',
                    MenuLink::PAGE => 'info',
                    MenuLink::CUSTOM => 'gray',
                ])
                ->default(MenuLink::COLLECTION)
                ->inline()
                ->grouped()
                ->live()
                ->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('label')
                    ->label(__('etic.filament.menus.item_label'))
                    ->required()
                    ->maxLength(191)
                    ->live(onBlur: true),
                Select::make('collection_key')
                    ->label(__('etic.filament.menus.item_collection'))
                    ->options(fn (): array => static::collectionOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get): bool => $get('type') === MenuLink::COLLECTION)
                    ->required(fn (Get $get): bool => $get('type') === MenuLink::COLLECTION)
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (filled($get('label')) || blank($state)) {
                            return;
                        }

                        $set('label', static::collectionOptions()[$state] ?? null);
                    }),
                Select::make('page_slug')
                    ->label(__('etic.filament.menus.item_page'))
                    ->options(fn (): array => static::pageOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get): bool => $get('type') === MenuLink::PAGE)
                    ->required(fn (Get $get): bool => $get('type') === MenuLink::PAGE)
                    ->createOptionForm([
                        TextInput::make('title')
                            ->label(__('etic.filament.pages.title'))
                            ->required()
                            ->maxLength(191)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (filled($get('slug')) || ! filled($state)) {
                                    return;
                                }

                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->label(__('etic.filament.pages.slug'))
                            ->required()
                            ->maxLength(191),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        $page = Page::query()->create([
                            'title' => $data['title'],
                            'slug' => $data['slug'] ?: Str::slug((string) $data['title']),
                            'template' => CmsPageLayout::PAGE,
                            'is_published' => true,
                            'channel_id' => app(StoreContext::class)->channelId(),
                        ]);

                        return $page->slug;
                    })
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (filled($get('label')) || blank($state)) {
                            return;
                        }

                        $set('label', static::pageOptions()[$state] ?? null);
                    }),
                TextInput::make('url')
                    ->label(__('etic.filament.menus.item_url'))
                    ->placeholder('/blog')
                    ->helperText(__('etic.filament.menus.item_url_help'))
                    ->maxLength(191)
                    ->visible(fn (Get $get): bool => $get('type') === MenuLink::CUSTOM)
                    ->required(fn (Get $get): bool => $get('type') === MenuLink::CUSTOM),
            ]),
        ];

        if ($depth < 2) {
            $fields[] = static::make('children', $depth + 1)
                ->label($depth === 0
                    ? __('etic.filament.menus.children')
                    : __('etic.filament.menus.column_links'))
                ->helperText($depth === 0
                    ? __('etic.filament.menus.children_help')
                    : __('etic.filament.menus.column_links_help'))
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Repeater $component): array {
                    $data = MenuLink::dehydrate($data);
                    $parent = $component->getRecord();

                    if ($parent instanceof MenuItem && filled($parent->menu_id)) {
                        $data['menu_id'] = $parent->menu_id;
                    }

                    return $data;
                });
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private static function collectionOptions(): array
    {
        $options = [
            MenuLink::ALL_COLLECTIONS => __('etic.filament.menus.all_collections'),
        ];

        $collections = Collection::query()
            ->channel(app(StoreContext::class)->channel())
            ->with('defaultUrl')
            ->get();

        foreach ($collections as $collection) {
            $slug = $collection->defaultUrl?->slug;

            if (blank($slug)) {
                continue;
            }

            $options[$slug] = (string) ($collection->translateAttribute('name') ?: $slug);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function pageOptions(): array
    {
        return Page::query()
            ->forStore()
            ->orderBy('title')
            ->pluck('title', 'slug')
            ->all();
    }
}
