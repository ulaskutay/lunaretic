<?php

namespace App\Etic\SEO\Http\Controllers;

use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Page;
use App\Etic\Storefront\StorefrontPaths;
use App\Etic\Support\StoreContext;
use Illuminate\Http\Response;
use Lunar\Models\Product;

class SitemapController
{
    public function __invoke(StoreContext $store): Response
    {
        $base = $store->primaryUrl();

        $urls = collect([
            $base,
            $base.'/koleksiyon',
            $base.'/blog',
        ]);

        Product::query()
            ->channel($store->channel())
            ->where('status', 'published')
            ->with('defaultUrl')
            ->each(function (Product $product) use ($urls, $base) {
                $slug = $product->defaultUrl?->slug;

                if ($slug) {
                    $urls->push($base.StorefrontPaths::product($slug));
                }
            });

        Page::query()->forStore()->where('is_published', true)->each(function (Page $page) use ($urls, $base) {
            $urls->push($base.'/sayfa/'.$page->slug);
        });

        BlogPost::query()->forStore()->published()->each(function (BlogPost $post) use ($urls, $base) {
            $urls->push($base.'/blog/'.$post->slug);
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls->unique() as $loc) {
            $xml .= '<url><loc>'.e($loc).'</loc></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
