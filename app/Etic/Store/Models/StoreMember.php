<?php

namespace App\Etic\Store\Models;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Admin\Models\Staff;

class StoreMember extends Model
{
    protected $table = 'etic_store_members';

    protected $fillable = [
        'store_id',
        'staff_id',
        'role',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('store', function ($query): void {
            $context = app(StoreContext::class);

            if ($context->isolationBypassed()) {
                return;
            }

            $storeId = $context->store()?->id;

            if ($storeId) {
                $query->where('store_id', $storeId);
            }
        });
    }
}
