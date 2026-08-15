<?php

namespace App\Etic\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'etic_seo';

    protected $fillable = [
        'title',
        'description',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
