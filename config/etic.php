<?php

return [
    'store' => [
        'handle' => env('ETIC_STORE_HANDLE', 'boxers'),
        'name' => env('ETIC_STORE_NAME', 'Etic Commerce'),
        'primary_url' => env('APP_URL', 'http://localhost:8000'),
        'locale' => env('ETIC_LOCALE', 'tr'),
        'currency' => env('ETIC_CURRENCY', 'TRY'),
    ],

    'theme' => env('ETIC_THEME', 'default'),

    'tax' => [
        // Excel'de KDV oranı boşsa veya ürün panelden eklenirse kullanılır.
        'default_rate' => (int) env('ETIC_DEFAULT_VAT_RATE', 10),
    ],

    'tenancy' => [
        'fallback_to_default' => true,
    ],

    'media' => [
        // Kilobytes. PHP upload_max_filesize / post_max_size must be at least this large.
        'max_upload_kb' => (int) env('ETIC_MEDIA_MAX_UPLOAD_KB', 51200),
    ],

    'shipping' => [
        'table_rates' => [
            ['max_subtotal' => 50000, 'price' => 9900, 'name' => 'Standart Kargo', 'identifier' => 'standard', 'description' => 'Türkiye içi teslimat'],
            ['max_subtotal' => null, 'price' => 0, 'name' => 'Ücretsiz Kargo', 'identifier' => 'free', 'description' => '500 ₺ ve üzeri siparişlerde'],
        ],
    ],

    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'base_url' => env('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'),
    ],

    'tracking' => [
        'ga4_measurement_id' => env('GA4_MEASUREMENT_ID'),
        'gtm_container_id' => env('GTM_CONTAINER_ID'),
        'meta_pixel_id' => env('META_PIXEL_ID'),
        'meta_capi_enabled' => (bool) env('META_CAPI_ENABLED', false),
        'meta_capi_token' => env('META_CAPI_ACCESS_TOKEN'),
        'meta_test_event_code' => env('META_TEST_EVENT_CODE'),
        'meta_graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        'search_console_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'merchant_feed_enabled' => true,
    ],
];
