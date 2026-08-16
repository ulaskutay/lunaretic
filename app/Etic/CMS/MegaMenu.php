<?php

namespace App\Etic\CMS;

use App\Etic\CMS\Models\MenuItem;
use App\Etic\Theme\ActiveTheme;
use Illuminate\Support\Collection;

class MegaMenu
{
    /**
     * @return array<int, array{title: ?string, url: ?string, links: array<int, array{label: string, url: string}>}>
     */
    public static function columns(MenuItem $item): array
    {
        $children = $item->children;
        $groups = $children->filter(fn (MenuItem $child): bool => $child->children->isNotEmpty());
        $leaves = $children->filter(fn (MenuItem $child): bool => $child->children->isEmpty());

        $columns = $groups->map(fn (MenuItem $group): array => [
            'title' => $group->label,
            'url' => $group->url,
            'links' => $group->children
                ->map(fn (MenuItem $link): array => [
                    'label' => $link->label,
                    'url' => $link->url,
                ])
                ->values()
                ->all(),
        ])->values();

        if ($leaves->isNotEmpty()) {
            $columns->push([
                'title' => $groups->isEmpty() ? $item->label : null,
                'url' => $groups->isEmpty() ? $item->url : null,
                'links' => $leaves
                    ->map(fn (MenuItem $link): array => [
                        'label' => $link->label,
                        'url' => $link->url,
                    ])
                    ->values()
                    ->all(),
            ]);
        }

        return $columns->all();
    }

    /**
     * @return array<int, array{label: string, url: string, image: string}>
     */
    public static function tiles(?ActiveTheme $theme = null): array
    {
        $theme ??= theme();

        return Collection::make([
            [
                'label' => (string) (theme_setting('editorial_title') ?: theme_setting('editorial_kicker') ?: __('etic.filament.menus.tile_featured')),
                'url' => (string) (theme_setting('editorial_cta_url') ?: '/koleksiyon'),
                'image' => $theme->editorialImageUrl(),
            ],
            [
                'label' => (string) (theme_setting('editorial_secondary_title') ?: theme_setting('editorial_secondary_kicker') ?: __('etic.filament.menus.tile_new')),
                'url' => (string) (theme_setting('editorial_secondary_cta_url') ?: '/koleksiyon?sort=newest'),
                'image' => $theme->secondaryEditorialImageUrl(),
            ],
            [
                'label' => (string) (theme_setting('banner_left_title') ?: __('etic.filament.menus.tile_featured')),
                'url' => (string) (theme_setting('banner_left_url') ?: '/koleksiyon'),
                'image' => $theme->leftBannerImageUrl(),
            ],
            [
                'label' => (string) (theme_setting('banner_right_title') ?: __('etic.filament.menus.tile_new')),
                'url' => (string) (theme_setting('banner_right_url') ?: '/koleksiyon?sort=newest'),
                'image' => $theme->rightBannerImageUrl(),
            ],
        ])
            ->filter(fn (array $tile): bool => filled($tile['image']))
            ->unique('image')
            ->take(2)
            ->values()
            ->all();
    }
}
