<?php

use App\Etic\Theme\ActiveTheme;

if (! function_exists('theme')) {
    function theme(): ActiveTheme
    {
        return app(ActiveTheme::class);
    }
}

if (! function_exists('theme_setting')) {
    function theme_setting(string $key, mixed $default = null): mixed
    {
        return theme()->setting($key, $default);
    }
}

if (! function_exists('theme_enabled')) {
    function theme_enabled(string $key, bool $default = true): bool
    {
        return theme()->enabled($key, $default);
    }
}
