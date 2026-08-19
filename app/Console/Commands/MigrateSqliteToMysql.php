<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToMysql extends Command
{
    protected $signature = 'etic:migrate-sqlite-to-mysql
                            {--force : Onay isteme}
                            {--skip-backup : SQLite yedeği alma}
                            {--data-only : Yalnız veri kopyala (migrate atla)}';

    protected $description = 'SQLite verisini MySQL\'e taşır (önce migrate, sonra satır kopyası).';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('DB_CONNECTION=mysql olmalı. .env dosyasını güncelleyin.');

            return self::FAILURE;
        }

        if (blank(config('database.connections.mysql.password'))) {
            $this->error('DB_PASSWORD boş. aaPanel şifresini .env içine yazın.');

            return self::FAILURE;
        }

        $sqlitePath = config('database.connections.sqlite_legacy.database');

        if (! is_file($sqlitePath)) {
            $this->error("SQLite dosyası bulunamadı: {$sqlitePath}");

            return self::FAILURE;
        }

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->error('MySQL bağlantısı başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        $sqliteTables = collect(DB::connection('sqlite_legacy')
            ->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
            ->pluck('name');

        if ($sqliteTables->isEmpty()) {
            $this->error('SQLite dosyasında tablo yok.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('MySQL şeması migrate edilecek ve SQLite verisi kopyalanacak. Devam?')) {
            return self::SUCCESS;
        }

        if (! $this->option('skip-backup')) {
            $backup = $sqlitePath.'.bak.'.date('YmdHis');
            copy($sqlitePath, $backup);
            $this->info("SQLite yedek: {$backup}");
        }

        $this->info('MySQL şeması oluşturuluyor (migrate)...');
        if (! $this->option('data-only')) {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(trim(Artisan::output()));
        } else {
            $this->comment('migrate atlandı (--data-only).');
        }

        $this->info('Veri kopyalanıyor...');
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

        $copied = 0;

        foreach ($sqliteTables as $table) {
            if (! Schema::connection('mysql')->hasTable($table)) {
                $this->warn("Atlandı (MySQL'de yok): {$table}");

                continue;
            }

            $rows = DB::connection('sqlite_legacy')->table($table)->count();

            if ($rows === 0) {
                continue;
            }

            DB::connection('mysql')->table($table)->truncate();

            $this->copyTableRows($table);

            if (Schema::connection('mysql')->hasColumn($table, 'id')) {
                $maxId = DB::connection('mysql')->table($table)->max('id');

                if ($maxId !== null) {
                    DB::connection('mysql')->statement(
                        'ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.((int) $maxId + 1)
                    );
                }
            }

            $this->line("  {$table}: {$rows} satır");
            $copied += $rows;
        }

        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("Tamam. {$copied} satır kopyalandı.");
        $this->comment('Öneri: php artisan optimize && queue/storefront servislerini yeniden başlatın.');

        return self::SUCCESS;
    }

    private function copyTableRows(string $table): void
    {
        $source = DB::connection('sqlite_legacy')->table($table);

        if (Schema::connection('sqlite_legacy')->hasColumn($table, 'id')) {
            $source->orderBy('id')->chunk(500, function ($chunk) use ($table): void {
                DB::connection('mysql')->table($table)->insert(
                    $chunk->map(fn ($row) => (array) $row)->all()
                );
            });

            return;
        }

        // id yoksa lazy/chunk kullanılamaz; küçük tablolar için tek seferde al.
        foreach ($source->get() as $row) {
            DB::connection('mysql')->table($table)->insert((array) $row);
        }
    }
}
