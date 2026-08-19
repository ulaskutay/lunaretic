<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Search\ProductIndexer;
use Lunar\Models\Contracts\CustomerGroup as CustomerGroupContract;
use Lunar\Models\Product as LunarProduct;
use Lunar\Search\ScoutIndexer;

class Product extends LunarProduct
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attribute_data',
        'product_type_id',
        'status',
        'brand_id',
        'model_code',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'published',
    ];

    public function indexer()
    {
        $config = config('lunar.search.indexers', []);

        return app($config[static::class] ?? ScoutIndexer::class);
    }

    public static function getExtraCustomerGroupPivotValues(CustomerGroupContract $customerGroup): array
    {
        return [
            'purchasable' => (bool) $customerGroup->default,
        ];
    }
}
