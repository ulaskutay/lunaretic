<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etic_stores', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();
            $table->string('name');
            $table->string('primary_domain')->nullable()->index();
            $table->json('extra_domains')->nullable();
            $table->string('theme')->default('default');
            $table->string('locale', 12)->default('tr');
            $table->string('currency', 8)->default('TRY');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('etic_blog_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unsignedBigInteger('channel_id')->nullable()->after('id')->index();
            $table->unique(['channel_id', 'slug']);
        });

        Schema::table('etic_pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['channel_id', 'slug']);
        });

        Schema::table('etic_blog_posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['channel_id', 'slug']);
        });

        Schema::table('etic_menus', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->unique(['channel_id', 'handle']);
        });

        Schema::table('etic_redirects', function (Blueprint $table) {
            $table->dropUnique(['from_path']);
            $table->unsignedBigInteger('channel_id')->nullable()->after('id')->index();
            $table->unique(['channel_id', 'from_path']);
        });
    }

    public function down(): void
    {
        Schema::table('etic_redirects', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'from_path']);
            $table->dropColumn('channel_id');
            $table->unique('from_path');
        });

        Schema::table('etic_menus', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'handle']);
            $table->unique('handle');
        });

        Schema::table('etic_blog_posts', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('etic_pages', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('etic_blog_categories', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'slug']);
            $table->dropColumn('channel_id');
            $table->unique('slug');
        });

        Schema::dropIfExists('etic_stores');
    }
};
