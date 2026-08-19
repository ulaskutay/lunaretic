<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\Brand as LunarBrand;

class Brand extends LunarBrand
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'store_id',
        'attribute_data',
    ];
}
