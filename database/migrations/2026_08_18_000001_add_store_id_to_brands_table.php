<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('lunar.database.table_prefix', 'lunar_').'brands';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'store_id')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('store_id')->nullable()->after('id');
                $table->index('store_id');
            });
        }

        if (Schema::hasTable('etic_stores')) {
            $defaultStoreId = DB::table('etic_stores')->where('is_default', true)->value('id')
                ?? DB::table('etic_stores')->orderBy('id')->value('id');

            if ($defaultStoreId) {
                DB::table($tableName)->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
            }
        }

        $hasStoreForeign = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreign): bool => in_array('store_id', $foreign['columns'], true));

        if (! $hasStoreForeign) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('store_id')->references('id')->on('etic_stores')->cascadeOnDelete();
            });
        }

        try {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unique(['store_id', 'name']);
            });
        } catch (Throwable) {
        }
    }

    public function down(): void
    {
        $tableName = config('lunar.database.table_prefix', 'lunar_').'brands';

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'store_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropUnique(['store_id', 'name']);
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
