<?php

use App\Etic\Catalog\Models\Brand as EticBrand;
use App\Etic\Catalog\Models\Product as EticProduct;
use App\Etic\Search\ProductIndexer;
use Lunar\Models\Collection;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Search\BrandIndexer;
use Lunar\Search\CollectionIndexer;
use Lunar\Search\CustomerIndexer;
use Lunar\Search\OrderIndexer;
use Lunar\Search\ProductOptionIndexer;

return [

    /*
    |--------------------------------------------------------------------------
    | Models for indexing
    |--------------------------------------------------------------------------
    |
    | The model listed here will be used to create/populate the indexes.
    | You can provide your own model here to run them all on the same
    | search engine.
    |
    */
    'models' => [
        /*
         * These models are required by the system, do not change them.
         */
        EticBrand::class,
        Collection::class,
        Customer::class,
        Order::class,
        EticProduct::class,
        ProductOption::class,

        /*
         * Below you can add your own models for indexing...
         */
        // App\Models\Example::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search engine mapping
    |--------------------------------------------------------------------------
    |
    | You can define what search driver each searchable model should use.
    | If the model isn't defined here, it will use the SCOUT_DRIVER env variable.
    |
    */
    'engine_map' => [
        // EticProduct::class => 'meilisearch',
    ],

    'facets' => [
        EticProduct::class => [
            'brand' => [
                'label' => 'Marka',
            ],
        ],
    ],

    'indexers' => [
        EticBrand::class => BrandIndexer::class,
        Collection::class => CollectionIndexer::class,
        Customer::class => CustomerIndexer::class,
        Order::class => OrderIndexer::class,
        Product::class => ProductIndexer::class,
        EticProduct::class => ProductIndexer::class,
        ProductOption::class => ProductOptionIndexer::class,
    ],

];
