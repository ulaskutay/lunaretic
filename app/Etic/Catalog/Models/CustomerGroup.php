<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use App\Etic\Support\Concerns\HasPerStoreDefaultRecord;
use App\Etic\Support\StoreContext;
use Lunar\Models\CustomerGroup as LunarCustomerGroup;
use Spatie\LaravelBlink\BlinkFacade as Blink;

class CustomerGroup extends LunarCustomerGroup
{
    use BelongsToStore;
    use HasPerStoreDefaultRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'handle',
        'default',
        'attribute_data',
        'store_id',
    ];

    public static function bootHasDefaultRecord(): void
    {
        // Default is per store; Lunar's global unique-default boot would steal it.
    }

    public static function getDefault()
    {
        $storeId = app(StoreContext::class)->store()?->id ?: 'none';
        $key = 'lunar_default_'.static::class.'_'.$storeId;

        return Blink::once($key, function () {
            return static::query()->default(true)->first()
                ?? static::query()->where('handle', 'retail')->first();
        });
    }
}
