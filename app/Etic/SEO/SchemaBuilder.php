<?php

namespace App\Etic\SEO;

use App\Etic\Support\StoreContext;
use Illuminate\Support\Collection;

class SchemaBuilder
{
    public function __construct(private StoreContext $store) {}

    public function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('etic.store.name'),
            'url' => $this->store->primaryUrl(),
        ];
    }

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('etic.store.name'),
            'url' => $this->store->primaryUrl(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $this->store->primaryUrl().'/ara?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ];
    }

    public function product(string $name, string $url, int $priceMinor, string $currency, bool $inStock, ?string $image = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'url' => $url,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $currency,
                'price' => number_format($priceMinor / 100, 2, '.', ''),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $url,
            ],
        ];

        if ($image) {
            $data['image'] = $image;
        }

        return $data;
    }

    public function article(string $title, string $url, ?string $date): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'url' => $url,
            'datePublished' => $date,
        ];
    }

    public function encode(array ...$graphs): string
    {
        return collect($graphs)->map(fn ($graph) => json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->implode("\n");
    }
}
