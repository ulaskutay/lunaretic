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

    'storefront' => [
        'origins' => env('ETIC_STOREFRONT_ORIGINS', 'http://localhost:3000'),
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

    'aras' => [
        'enabled' => (bool) env('ARAS_ENABLED', false),
        'username' => env('ARAS_USERNAME'),
        'password' => env('ARAS_PASSWORD'),
        'customer_code' => env('ARAS_CUSTOMER_CODE'),
        'test_mode' => (bool) env('ARAS_TEST_MODE', true),
        'default_weight_kg' => (float) env('ARAS_DEFAULT_WEIGHT_KG', 1),
        'default_piece_count' => (int) env('ARAS_DEFAULT_PIECE_COUNT', 1),
        'mark_dispatched' => (bool) env('ARAS_MARK_DISPATCHED', true),
    ],

    'surat' => [
        'enabled' => (bool) env('SURAT_ENABLED', false),
        'username' => env('SURAT_USERNAME'),
        'password' => env('SURAT_PASSWORD'),
        'web_password' => env('SURAT_WEB_PASSWORD'),
        'test_mode' => (bool) env('SURAT_TEST_MODE', true),
        'default_weight_kg' => (float) env('SURAT_DEFAULT_WEIGHT_KG', 1),
        'default_piece_count' => (int) env('SURAT_DEFAULT_PIECE_COUNT', 1),
        'mark_dispatched' => (bool) env('SURAT_MARK_DISPATCHED', true),
    ],

    'mng' => [
        'enabled' => (bool) env('MNG_ENABLED', false),
        'client_id' => env('MNG_CLIENT_ID'),
        'client_secret' => env('MNG_CLIENT_SECRET'),
        'customer_number' => env('MNG_CUSTOMER_NUMBER'),
        'password' => env('MNG_PASSWORD'),
        'test_mode' => (bool) env('MNG_TEST_MODE', true),
        'default_city_code' => (int) env('MNG_DEFAULT_CITY_CODE', 34),
        'default_district_code' => (int) env('MNG_DEFAULT_DISTRICT_CODE', 100),
        'default_weight_kg' => (float) env('MNG_DEFAULT_WEIGHT_KG', 1),
        'default_piece_count' => (int) env('MNG_DEFAULT_PIECE_COUNT', 1),
        'mark_dispatched' => (bool) env('MNG_MARK_DISPATCHED', true),
    ],

    'yurtici' => [
        'enabled' => (bool) env('YURTICI_ENABLED', false),
        'username' => env('YURTICI_USERNAME'),
        'password' => env('YURTICI_PASSWORD'),
        'test_mode' => (bool) env('YURTICI_TEST_MODE', true),
        'default_weight_kg' => (float) env('YURTICI_DEFAULT_WEIGHT_KG', 1),
        'default_piece_count' => (int) env('YURTICI_DEFAULT_PIECE_COUNT', 1),
        'default_desi' => (float) env('YURTICI_DEFAULT_DESI', 1),
        'mark_dispatched' => (bool) env('YURTICI_MARK_DISPATCHED', true),
    ],

    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'base_url' => env('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'),
    ],

    'paytr' => [
        'merchant_id' => env('PAYTR_MERCHANT_ID'),
        'merchant_key' => env('PAYTR_MERCHANT_KEY'),
        'merchant_salt' => env('PAYTR_MERCHANT_SALT'),
        'test_mode' => (int) env('PAYTR_TEST_MODE', 1),
        'debug_on' => (int) env('PAYTR_DEBUG_ON', 1),
        'no_installment' => (int) env('PAYTR_NO_INSTALLMENT', 0),
        'max_installment' => (int) env('PAYTR_MAX_INSTALLMENT', 0),
        'currency' => env('PAYTR_CURRENCY', 'TL'),
        'lang' => env('PAYTR_LANG', 'tr'),
        'timeout_limit' => (int) env('PAYTR_TIMEOUT_LIMIT', 30),
        'non_3d' => (int) env('PAYTR_NON_3D', 0),
    ],

    'search' => [
        'max_results' => (int) env('ETIC_SEARCH_MAX_RESULTS', 1000),
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
