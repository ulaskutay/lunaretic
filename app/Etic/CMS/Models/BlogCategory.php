<?php

namespace App\Etic\CMS\Models;

use App\Etic\Support\Concerns\BelongsToChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use BelongsToChannel;

    protected $table = 'etic_blog_categories';

    protected $fillable = ['name', 'slug', 'channel_id'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'blog_category_id');
    }

    protected static function booted(): void
    {
        static::saving(function (BlogCategory $category): void {
            $category->slug = $category->slug ?: Str::slug($category->name);
        });
    }
}
