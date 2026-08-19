<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\ProductType as LunarProductType;

class ProductType extends LunarProductType
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'store_id',
    ];
}
