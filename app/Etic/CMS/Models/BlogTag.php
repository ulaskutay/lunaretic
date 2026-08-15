<?php

namespace App\Etic\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    protected $table = 'etic_blog_tags';

    protected $fillable = ['name', 'slug'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'etic_blog_post_tag', 'blog_tag_id', 'blog_post_id');
    }
}
