<?php

namespace App\Etic\Theme;

use App\Etic\CMS\Models\Menu;
use App\Etic\Support\StoreContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ActiveTheme
{
    private const FONT_STACKS = [
        'system' => 'ui-sans-serif, system-ui, sans-serif',
        'display' => '"Instrument Sans", ui-sans-serif, system-ui, sans-serif',
        'serif' => 'Georgia, "Times New Roman", serif',
        'playfair' => '"Playfair Display", Georgia, "Times New Roman", serif',
        'montserrat' => '"Montserrat", ui-sans-serif, system-ui, sans-serif',
    ];

    private const RADIUS = [
        'none' => '0px',
        'sm' => '0.5rem',
        'md' => '1rem',
        'xl' => '1.5rem',
    ];

    private const CONTAINERS = [
        'narrow' => 'max-w-4xl',
        'default' => 'max-w-6xl',
        'wide' => 'max-w-7xl',
    ];

    public function __construct(
        private StoreContext $store,
        private ThemeRegistry $themes,
        private ThemeSettings $settings,
    ) {}

    public function handle(): string
    {
        return $this->store->theme();
    }

    public function name(): string
    {
        return $this->manifest()->name();
    }

    public function path(): string
    {
        return $this->manifest()->path;
    }

    public function manifest(): ThemeManifest
    {
        return $this->themes->getOrDefault($this->handle());
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings->get($key, $default);
    }

    public function enabled(string $key, bool $default = true): bool
    {
        $value = $this->setting($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->settings->resolved($this->handle());
    }

    public function menu(string $handle): ?Menu
    {
        return Menu::query()->forStore()->where('handle', $handle)->with('items.children.children')->first();
    }

    public function logoText(): string
    {
        return (string) ($this->setting('logo_text') ?: $this->store->name());
    }

    public function logoUrl(): ?string
    {
        return $this->mediaUrl($this->setting('logo'));
    }

    public function faviconUrl(): ?string
    {
        return $this->absolute($this->mediaUrl($this->setting('favicon')));
    }

    public function faviconHref(): string
    {
        return $this->faviconUrl() ?: asset('favicon.svg');
    }

    public function heroImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('hero_image'));
    }

    public function editorialImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('editorial_image'));
    }

    public function secondaryEditorialImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('editorial_secondary_image'));
    }

    public function leftBannerImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('banner_left_image'));
    }

    public function rightBannerImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('banner_right_image'));
    }

    public function footerImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('footer_image'));
    }

    public function shopLookImageUrl(): ?string
    {
        return $this->mediaUrl($this->setting('shop_look_image'));
    }

    /**
     * @return list<array{product_id: int|null, x: float, y: float}>
     */
    public function shopLookHotspots(): array
    {
        $defaults = [
            [28, 34],
            [61, 27],
            [45, 62],
            [72, 76],
        ];

        return collect($defaults)
            ->map(function (array $position, int $index): array {
                $number = $index + 1;
                $productId = $this->setting("shop_look_{$number}_product");

                return [
                    'product_id' => filled($productId) ? (int) $productId : null,
                    'x' => max(0, min(100, (float) $this->setting("shop_look_{$number}_x", $position[0]))),
                    'y' => max(0, min(100, (float) $this->setting("shop_look_{$number}_y", $position[1]))),
                ];
            })
            ->values()
            ->all();
    }

    public function countdownEndsAt(): ?string
    {
        return $this->dateTime($this->setting('countdown_ends_at'));
    }

    public function containerClass(): string
    {
        return self::CONTAINERS[(string) $this->setting('container', 'default')] ?? self::CONTAINERS['default'];
    }

    /**
     * @return list<string>
     */
    public function viteInputs(): array
    {
        $inputs = ['resources/css/app.css', 'resources/js/app.js'];

        foreach ([$this->manifest()->cssPath(), $this->manifest()->jsPath()] as $path) {
            if (is_string($path) && $path !== '' && $this->viteKnows($path)) {
                $inputs[] = $path;
            }
        }

        return $inputs;
    }

    /**
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        $settings = $this->settings();

        return [
            '--etic-color-background' => (string) ($settings['color_background'] ?? '#fafafa'),
            '--etic-color-surface' => (string) ($settings['color_surface'] ?? '#ffffff'),
            '--etic-color-text' => (string) ($settings['color_text'] ?? '#171717'),
            '--etic-color-muted' => (string) ($settings['color_muted'] ?? '#737373'),
            '--etic-color-primary' => (string) ($settings['color_primary'] ?? '#111827'),
            '--etic-color-primary-text' => (string) ($settings['color_primary_text'] ?? '#ffffff'),
            '--etic-color-accent' => (string) ($settings['color_accent'] ?? '#111827'),
            '--etic-font-heading' => $this->fontStack((string) ($settings['font_heading'] ?? 'display')),
            '--etic-font-body' => $this->fontStack((string) ($settings['font_body'] ?? 'system')),
            '--etic-radius' => self::RADIUS[(string) ($settings['radius'] ?? 'md')] ?? self::RADIUS['md'],
        ];
    }

    public function cssVariablesStyle(): string
    {
        return collect($this->cssVariables())
            ->map(fn (string $value, string $name) => $name.': '.$value)
            ->implode('; ');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $settings = $this->settings();

        return [
            'handle' => $this->handle(),
            'name' => $this->name(),
            'logo_text' => $this->logoText(),
            'logo' => $this->absolute($this->logoUrl()),
            'favicon' => $this->faviconUrl(),
            'announcement' => $settings['announcement'] ?? null,
            'header_style' => $settings['header_style'] ?? 'simple',
            'container' => $settings['container'] ?? 'default',
            'footer_text' => $settings['footer_text'] ?? null,
            'footer_image' => $this->absolute($this->footerImageUrl()),
            'newsletter' => [
                'enabled' => $this->enabled('newsletter_enabled'),
                'kicker' => $settings['newsletter_kicker'] ?? null,
                'title' => $settings['newsletter_title'] ?? null,
                'description' => $settings['newsletter_description'] ?? null,
                'placeholder' => $settings['newsletter_placeholder'] ?? null,
                'cta' => $settings['newsletter_cta'] ?? null,
                'benefits' => [
                    [
                        'title' => $settings['benefit_returns_title'] ?? null,
                        'description' => $settings['benefit_returns_description'] ?? null,
                    ],
                    [
                        'title' => $settings['benefit_shipping_title'] ?? null,
                        'description' => $settings['benefit_shipping_description'] ?? null,
                    ],
                    [
                        'title' => $settings['benefit_support_title'] ?? null,
                        'description' => $settings['benefit_support_description'] ?? null,
                    ],
                ],
            ],
            'social' => [
                'instagram' => $settings['social_instagram'] ?? null,
                'tiktok' => $settings['social_tiktok'] ?? null,
                'facebook' => $settings['social_facebook'] ?? null,
                'whatsapp' => $settings['social_whatsapp'] ?? null,
            ],
            'hero' => [
                'enabled' => $this->enabled('hero_enabled'),
                'kicker' => $settings['hero_kicker'] ?? null,
                'title' => $settings['hero_title'] ?? null,
                'cta_primary' => $settings['hero_cta_primary'] ?? null,
                'cta_primary_url' => $settings['hero_cta_primary_url'] ?? null,
                'cta_secondary' => $settings['hero_cta_secondary'] ?? null,
                'cta_secondary_url' => $settings['hero_cta_secondary_url'] ?? null,
                'image' => $this->absolute($this->heroImageUrl()),
            ],
            'featured' => [
                'enabled' => $this->enabled('featured_enabled'),
                'title' => $settings['featured_title'] ?? null,
            ],
            'editorial' => [
                'enabled' => $this->enabled('editorial_enabled'),
                'kicker' => $settings['editorial_kicker'] ?? null,
                'title' => $settings['editorial_title'] ?? null,
                'cta' => $settings['editorial_cta'] ?? null,
                'cta_url' => $settings['editorial_cta_url'] ?? null,
                'image' => $this->absolute($this->editorialImageUrl()),
            ],
            'editorial_secondary' => [
                'enabled' => $this->enabled('editorial_secondary_enabled'),
                'kicker' => $settings['editorial_secondary_kicker'] ?? null,
                'title' => $settings['editorial_secondary_title'] ?? null,
                'cta' => $settings['editorial_secondary_cta'] ?? null,
                'cta_url' => $settings['editorial_secondary_cta_url'] ?? null,
                'image' => $this->absolute($this->secondaryEditorialImageUrl()),
            ],
            'best_sellers' => [
                'enabled' => $this->enabled('best_sellers_enabled'),
                'title' => $settings['best_sellers_title'] ?? null,
                'cta' => $settings['best_sellers_cta'] ?? null,
                'url' => '/koleksiyon?sort=best_selling',
            ],
            'banners' => [
                'enabled' => $this->enabled('banners_enabled'),
                'left' => [
                    'image' => $this->absolute($this->leftBannerImageUrl()),
                    'title' => $settings['banner_left_title'] ?? null,
                    'subtitle' => $settings['banner_left_subtitle'] ?? null,
                    'cta' => $settings['banner_left_cta'] ?? null,
                    'url' => $settings['banner_left_url'] ?? null,
                ],
                'right' => [
                    'image' => $this->absolute($this->rightBannerImageUrl()),
                    'title' => $settings['banner_right_title'] ?? null,
                    'subtitle' => $settings['banner_right_subtitle'] ?? null,
                    'cta' => $settings['banner_right_cta'] ?? null,
                    'url' => $settings['banner_right_url'] ?? null,
                ],
            ],
            'shop_look' => [
                'enabled' => $this->enabled('shop_look_enabled'),
                'kicker' => $settings['shop_look_kicker'] ?? null,
                'title' => $settings['shop_look_title'] ?? null,
                'image' => $this->absolute($this->shopLookImageUrl()),
                'hotspots' => $this->shopLookHotspots(),
            ],
            'countdown' => [
                'enabled' => $this->enabled('countdown_enabled'),
                'title' => $settings['countdown_title'] ?? null,
                'description' => $settings['countdown_description'] ?? null,
                'ends_at' => $this->countdownEndsAt(),
            ],
            'css_variables' => $this->cssVariables(),
        ];
    }

    private function viteKnows(string $path): bool
    {
        if (is_file(public_path('hot'))) {
            return true;
        }

        $manifestPath = public_path('build/manifest.json');

        if (! is_file($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        return is_array($manifest) && isset($manifest[$path]);
    }

    private function fontStack(string $key): string
    {
        return self::FONT_STACKS[$key] ?? self::FONT_STACKS['system'];
    }

    private function mediaUrl(mixed $path): ?string
    {
        if (! filled($path) || is_array($path)) {
            return null;
        }

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function dateTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value, config('app.timezone'))->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function absolute(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($this->store->primaryUrl(), '/').'/'.ltrim($url, '/');
    }
}
