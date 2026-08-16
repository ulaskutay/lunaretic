<?php

namespace App\Etic\Catalog\Models;

use Lunar\Models\Product as LunarProduct;

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
}
