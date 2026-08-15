<?php

namespace App\Etic\Integrations\Marketing\Http\Controllers;

use App\Etic\Integrations\Marketing\GoogleMerchantFeed;
use App\Etic\Integrations\Marketing\TrackingSettings;
use Illuminate\Http\Response;

class GoogleMerchantFeedController
{
    public function __invoke(GoogleMerchantFeed $feed, TrackingSettings $settings): Response
    {
        if (! $settings->get('merchant_feed_enabled')) {
            abort(404);
        }

        return response($feed->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
