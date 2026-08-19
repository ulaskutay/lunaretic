<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\AttributeGroup as LunarAttributeGroup;

class AttributeGroup extends LunarAttributeGroup
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attributable_type',
        'name',
        'handle',
        'position',
        'store_id',
    ];
}
