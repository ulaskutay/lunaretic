<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'product_types',
        'product_options',
        'product_option_values',
        'attribute_groups',
        'attributes',
        'collection_groups',
        'customer_groups',
        'tax_classes',
    ];

    public function up(): void
    {
        $prefix = config('lunar.database.table_prefix', 'lunar_');
        $defaultStoreId = $this->defaultStoreId();

        foreach ($this->tables as $suffix) {
            $tableName = $prefix.$suffix;

            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'store_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id');
                });
            }

            if ($defaultStoreId) {
                DB::table($tableName)->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
            }
        }

        $this->replaceUniqueIndexes($prefix);

        foreach ($this->tables as $suffix) {
            $tableName = $prefix.$suffix;

            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'store_id')) {
                continue;
            }

            $hasStoreForeign = collect(Schema::getForeignKeys($tableName))
                ->contains(fn (array $foreign): bool => in_array('store_id', $foreign['columns'], true));

            if ($hasStoreForeign) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('store_id')->references('id')->on('etic_stores')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $prefix = config('lunar.database.table_prefix', 'lunar_');

        foreach ($this->tables as $suffix) {
            $tableName = $prefix.$suffix;

            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'store_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }
    }

    private function defaultStoreId(): ?int
    {
        if (! Schema::hasTable('etic_stores')) {
            return null;
        }

        return DB::table('etic_stores')->where('is_default', true)->value('id')
            ?? DB::table('etic_stores')->orderBy('id')->value('id');
    }

    private function replaceUniqueIndexes(string $prefix): void
    {
        $this->dropUniqueIfExists($prefix.'collection_groups', ['handle']);
        $this->addUniqueIfMissing($prefix.'collection_groups', ['store_id', 'handle']);

        $this->dropUniqueIfExists($prefix.'attribute_groups', ['handle']);
        $this->addUniqueIfMissing($prefix.'attribute_groups', ['store_id', 'handle']);

        $this->dropUniqueIfExists($prefix.'attributes', ['attribute_type', 'handle']);
        $this->addUniqueIfMissing($prefix.'attributes', ['store_id', 'attribute_type', 'handle']);

        $this->dropUniqueIfExists($prefix.'customer_groups', ['handle']);
        $this->addUniqueIfMissing($prefix.'customer_groups', ['store_id', 'handle']);

        $this->addUniqueIfMissing($prefix.'product_types', ['store_id', 'name']);
        $this->addUniqueIfMissing($prefix.'product_options', ['store_id', 'handle']);
        $this->addUniqueIfMissing($prefix.'tax_classes', ['store_id', 'name']);
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueIfExists(string $table, array $columns): void
    {
        if (! Schema::hasTable($table) || ! $this->hasUniqueIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->dropUnique($columns);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfMissing(string $table, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->hasUniqueIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->unique($columns);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasUniqueIndex(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(function (array $index) use ($columns): bool {
            return ($index['unique'] ?? false) && ($index['columns'] ?? []) === $columns;
        });
    }
};
