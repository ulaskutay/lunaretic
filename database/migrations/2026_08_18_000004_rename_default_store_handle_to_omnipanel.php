<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FROM = 'boxers';

    private const TO = 'omnipanel';

    public function up(): void
    {
        $this->renameHandle(self::FROM, self::TO);
        $this->renameMediaDirectory(self::FROM, self::TO);
    }

    public function down(): void
    {
        $this->renameHandle(self::TO, self::FROM);
        $this->renameMediaDirectory(self::TO, self::FROM);
    }

    private function renameHandle(string $from, string $to): void
    {
        if (! Schema::hasTable('etic_stores')) {
            return;
        }

        if (DB::table('etic_stores')->where('handle', $to)->exists()) {
            return;
        }

        DB::table('etic_stores')->where('handle', $from)->update(['handle' => $to]);

        if (Schema::hasTable('lunar_channels')) {
            if (! DB::table('lunar_channels')->where('handle', $to)->exists()) {
                DB::table('lunar_channels')->where('handle', $from)->update(['handle' => $to]);
            }
        }

        foreach (['etic_store_settings', 'etic_tracking_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->where('channel_handle', $from)->update(['channel_handle' => $to]);
        }
    }

    private function renameMediaDirectory(string $from, string $to): void
    {
        $base = storage_path('app/public/stores');
        $source = $base.'/'.$from;
        $target = $base.'/'.$to;

        if (is_dir($source) && ! is_dir($target)) {
            File::move($source, $target);
        }
    }
};
