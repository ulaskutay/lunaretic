<?php

return [

    'definitions' => [
        'asset' => \App\Etic\Media\EticMediaDefinitions::class,
        'brand' => \App\Etic\Media\EticMediaDefinitions::class,
        'collection' => \App\Etic\Media\EticMediaDefinitions::class,
        'product' => \App\Etic\Media\EticMediaDefinitions::class,
        'product-option' => \App\Etic\Media\EticMediaDefinitions::class,
        'product-option-value' => \App\Etic\Media\EticMediaDefinitions::class,
    ],

    'collection' => 'images',

    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],

];
