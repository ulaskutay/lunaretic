<?php

namespace App\Etic\Storefront;

class StorefrontPaths
{
    public static function product(string $slug): string
    {
        return '/urun/'.ltrim($slug, '/');
    }
}
