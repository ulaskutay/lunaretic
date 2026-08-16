<?php

namespace App\Etic\CMS;

class MenuLink
{
    public const CUSTOM = 'custom';

    public const COLLECTION = 'collection';

    public const PAGE = 'page';

    public const ALL_COLLECTIONS = '__all__';

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::COLLECTION => __('etic.filament.menus.types.collection'),
            self::PAGE => __('etic.filament.menus.types.page'),
            self::CUSTOM => __('etic.filament.menus.types.custom'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        $url = trim((string) ($data['url'] ?? ''));
        $path = static::path($url);

        if ($path === '/koleksiyon' || str_starts_with($path, '/koleksiyon/')) {
            $slug = trim(substr($path, strlen('/koleksiyon')), '/');

            $data['type'] = self::COLLECTION;
            $data['collection_key'] = $slug === '' ? self::ALL_COLLECTIONS : $slug;
            $data['page_slug'] = null;

            return $data;
        }

        if (str_starts_with($path, '/sayfa/')) {
            $data['type'] = self::PAGE;
            $data['page_slug'] = trim(substr($path, strlen('/sayfa/')), '/');
            $data['collection_key'] = null;

            return $data;
        }

        $data['type'] = self::CUSTOM;
        $data['collection_key'] = null;
        $data['page_slug'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        $type = (string) ($data['type'] ?? self::CUSTOM);

        $data['url'] = match ($type) {
            self::COLLECTION => static::collectionUrl($data['collection_key'] ?? null),
            self::PAGE => static::pageUrl($data['page_slug'] ?? null),
            default => static::customUrl($data['url'] ?? null),
        };

        unset($data['type'], $data['collection_key'], $data['page_slug']);

        return $data;
    }

    public static function collectionUrl(mixed $key): string
    {
        $key = trim((string) $key);

        if ($key === '' || $key === self::ALL_COLLECTIONS) {
            return '/koleksiyon';
        }

        return '/koleksiyon/'.$key;
    }

    public static function pageUrl(mixed $slug): string
    {
        $slug = trim((string) $slug, '/');

        return $slug === '' ? '/' : '/sayfa/'.$slug;
    }

    public static function customUrl(mixed $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '/';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }

        return '/'.$url;
    }

    public static function path(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $url;
    }
}
