<?php

namespace App\Etic\Store\Models;

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
}
