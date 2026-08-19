<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\ProductOption as LunarProductOption;

class ProductOption extends LunarProductOption
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'label',
        'handle',
        'shared',
        'meta',
        'store_id',
    ];
}
