<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('etic_platform_payments');
        Schema::dropIfExists('etic_custom_domains');
        Schema::dropIfExists('etic_subscriptions');
        Schema::dropIfExists('etic_store_members');
        Schema::dropIfExists('etic_plans');

        if (Schema::hasTable('etic_stores')) {
            Schema::table('etic_stores', function (Blueprint $table): void {
                if (Schema::hasColumn('etic_stores', 'owner_id')) {
                    $table->dropConstrainedForeignId('owner_id');
                }

                if (Schema::hasColumn('etic_stores', 'provisioned_at')) {
                    $table->dropColumn('provisioned_at');
                }

                if (Schema::hasColumn('etic_stores', 'subscription_status')) {
                    $table->dropColumn('subscription_status');
                }
            });
        }
    }

    public function down(): void
    {
        // SaaS layer removed intentionally; no rollback.
    }
};
