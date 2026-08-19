<?php

namespace App\Etic\Store\Models;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $table = 'etic_store_settings';

    protected $fillable = [
        'channel_handle',
        'group',
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('etic_store', function ($query): void {
            $context = app(StoreContext::class);

            if ($context->isolationBypassed()) {
                return;
            }

            $handle = $context->handle();

            if ($handle !== '') {
                $query->where('channel_handle', $handle);
            }
        });

        static::creating(function (self $setting): void {
            if (blank($setting->channel_handle)) {
                $setting->channel_handle = app(StoreContext::class)->handle();
            }
        });
    }
}
