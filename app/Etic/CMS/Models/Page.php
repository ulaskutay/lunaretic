<?php

namespace App\Etic\CMS\Models;

use App\Etic\SEO\Concerns\HasSeo;
use App\Etic\Support\Concerns\BelongsToChannel;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToChannel;
    use HasSeo;

    protected $table = 'etic_pages';

    protected $fillable = [
        'title',
        'slug',
        'template',
        'content',
        'is_published',
        'channel_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
