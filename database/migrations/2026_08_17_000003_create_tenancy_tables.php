<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etic_stores', function (Blueprint $table): void {
            if (! Schema::hasColumn('etic_stores', 'provisioned_at')) {
                $table->timestamp('provisioned_at')->nullable()->after('is_default');
            }

            if (! Schema::hasColumn('etic_stores', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('provisioned_at');
            }

            if (! Schema::hasColumn('etic_stores', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::create('etic_store_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('etic_stores')->cascadeOnDelete();
            $table->unsignedBigInteger('staff_id');
            $table->string('role', 32)->default('staff');
            $table->timestamps();

            $table->unique(['store_id', 'staff_id']);
            $table->index('staff_id');
        });

        Schema::create('etic_custom_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('etic_stores')->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->string('status', 32)->default('pending');
            $table->string('verification_token', 64);
            $table->timestamp('verified_at')->nullable();
            $table->string('ssl_status', 32)->default('pending');
            $table->timestamps();
        });

        Schema::create('etic_store_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('etic_stores')->cascadeOnDelete();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->string('action', 64);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('store_id')->nullable()->after('id')->index();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['store_id', 'email']);
        });

        $defaultStoreId = Schema::hasTable('etic_stores')
            ? DB::table('etic_stores')->where('is_default', true)->value('id')
            : null;

        if ($defaultStoreId) {
            DB::table('users')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['store_id', 'email']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('store_id');
            $table->unique('email');
        });

        Schema::dropIfExists('etic_store_audit_logs');
        Schema::dropIfExists('etic_custom_domains');
        Schema::dropIfExists('etic_store_members');

        Schema::table('etic_stores', function (Blueprint $table): void {
            if (Schema::hasColumn('etic_stores', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['suspended_at', 'provisioned_at'] as $column) {
                if (Schema::hasColumn('etic_stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
