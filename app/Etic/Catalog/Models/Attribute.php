<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\Attribute as LunarAttribute;

class Attribute extends LunarAttribute
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attribute_type',
        'attribute_group_id',
        'position',
        'name',
        'handle',
        'section',
        'type',
        'required',
        'default_value',
        'configuration',
        'system',
        'description',
        'store_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attribute): void {
            if (blank($attribute->store_id) && $attribute->attribute_group_id) {
                $attribute->store_id = AttributeGroup::withoutGlobalScopes()
                    ->whereKey($attribute->attribute_group_id)
                    ->value('store_id');
            }
        });
    }
}
