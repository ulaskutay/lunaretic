<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) config('etic.storefront.origins', env('ETIC_STOREFRONT_ORIGINS', 'http://localhost:3000')))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Cart-Token'],

    'max_age' => 0,

    'supports_credentials' => true,

];
