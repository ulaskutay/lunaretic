<?php

namespace App\Etic\SEO\Http\Controllers;

use App\Etic\Support\StoreContext;
use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(StoreContext $store): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /lunar',
            'Disallow: /sepet',
            'Disallow: /odeme',
            'Sitemap: '.$store->primaryUrl().'/sitemap.xml',
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
