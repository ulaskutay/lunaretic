<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\CollectionGroup as LunarCollectionGroup;

class CollectionGroup extends LunarCollectionGroup
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'handle',
        'store_id',
    ];
}
