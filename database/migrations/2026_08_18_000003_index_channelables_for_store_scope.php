<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lunar_channelables')) {
            return;
        }

        Schema::table('lunar_channelables', function (Blueprint $table): void {
            $table->index(
                ['channel_id', 'enabled', 'channelable_type', 'channelable_id'],
                'lunar_channelables_store_lookup_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lunar_channelables')) {
            return;
        }

        Schema::table('lunar_channelables', function (Blueprint $table): void {
            $table->dropIndex('lunar_channelables_store_lookup_index');
        });
    }
};
