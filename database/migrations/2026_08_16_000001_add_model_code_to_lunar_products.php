<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = $this->productsTable();

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'model_code')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->string('model_code')->nullable()->index();
        });
    }

    public function down(): void
    {
        $table = $this->productsTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'model_code')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn('model_code');
        });
    }

    private function productsTable(): string
    {
        return config('lunar.database.table_prefix', 'lunar_').'products';
    }
};
