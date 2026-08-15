<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etic_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('template')->default('page');
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('etic_blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('etic_blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('etic_blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('blog_category_id')->nullable()->index();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('etic_blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('etic_blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('etic_blog_tags')->cascadeOnDelete();
            $table->unique(['blog_post_id', 'blog_tag_id']);
        });

        Schema::create('etic_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('etic_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('etic_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('etic_menu_items')->nullOnDelete();
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('etic_seo', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();
        });

        Schema::create('etic_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('etic_store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_handle')->index();
            $table->string('group')->default('general');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['channel_handle', 'group', 'key']);
        });

        Schema::create('etic_tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_handle')->index();
            $table->string('provider');
            $table->json('payload')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->unique(['channel_handle', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etic_tracking_settings');
        Schema::dropIfExists('etic_store_settings');
        Schema::dropIfExists('etic_redirects');
        Schema::dropIfExists('etic_seo');
        Schema::dropIfExists('etic_menu_items');
        Schema::dropIfExists('etic_menus');
        Schema::dropIfExists('etic_blog_post_tag');
        Schema::dropIfExists('etic_blog_posts');
        Schema::dropIfExists('etic_blog_tags');
        Schema::dropIfExists('etic_blog_categories');
        Schema::dropIfExists('etic_pages');
    }
};
