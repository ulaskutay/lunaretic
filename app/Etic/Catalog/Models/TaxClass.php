<?php

namespace App\Etic\Catalog\Models;

use App\Etic\Support\Concerns\BelongsToStore;
use App\Etic\Support\Concerns\HasPerStoreDefaultRecord;
use Lunar\Models\TaxClass as LunarTaxClass;

class TaxClass extends LunarTaxClass
{
    use BelongsToStore;
    use HasPerStoreDefaultRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'default',
        'store_id',
    ];
}
