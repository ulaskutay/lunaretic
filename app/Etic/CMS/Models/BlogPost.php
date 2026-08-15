<?php

namespace App\Etic\CMS\Models;

use App\Etic\SEO\Concerns\HasSeo;
use App\Etic\Support\Concerns\BelongsToChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use BelongsToChannel;
    use HasSeo;

    protected $table = 'etic_blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author',
        'published_at',
        'is_published',
        'blog_category_id',
        'channel_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'etic_blog_post_tag', 'blog_post_id', 'blog_tag_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function featuredImageUrl(): ?string
    {
        if (! filled($this->featured_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->featured_image);
    }

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post): void {
            $post->slug = $post->slug ?: Str::slug($post->title);
        });
    }
}
