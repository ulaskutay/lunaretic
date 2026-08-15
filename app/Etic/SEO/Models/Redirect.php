<?php

namespace App\Etic\SEO\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $table = 'etic_redirects';

    protected $fillable = [
        'from_path',
        'to_url',
        'status_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
        ];
    }
}
