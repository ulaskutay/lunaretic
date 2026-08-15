<?php

namespace App\Etic\SEO\Http\Controllers;

use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Page;
use App\Etic\Support\StoreContext;
use Illuminate\Http\Response;
use Lunar\Models\Product;
use Lunar\Models\Url;

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

        Url::query()->where('default', true)->each(function (Url $url) use ($urls, $base) {
            $urls->push($base.'/p/'.$url->slug);
        });

        Page::query()->where('is_published', true)->each(function (Page $page) use ($urls, $base) {
            $urls->push($base.'/sayfa/'.$page->slug);
        });

        BlogPost::query()->where('is_published', true)->each(function (BlogPost $post) use ($urls, $base) {
            $urls->push($base.'/blog/'.$post->slug);
        });

        Product::query(); // keep Lunar import used for future product-only sitemaps

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls->unique() as $loc) {
            $xml .= '<url><loc>'.e($loc).'</loc></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
