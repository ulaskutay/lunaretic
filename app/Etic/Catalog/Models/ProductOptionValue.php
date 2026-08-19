<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use Lunar\Models\ProductOptionValue as LunarProductOptionValue;

class ProductOptionValue extends LunarProductOptionValue
{
    use BelongsToStore;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_option_id',
        'name',
        'position',
        'meta',
        'store_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $value): void {
            if (blank($value->store_id) && $value->product_option_id) {
                $value->store_id = ProductOption::withoutGlobalScopes()
                    ->whereKey($value->product_option_id)
                    ->value('store_id');
            }
        });
    }
}
