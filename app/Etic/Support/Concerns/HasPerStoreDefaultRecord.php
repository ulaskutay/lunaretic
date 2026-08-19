<?php

namespace App\Etic\Support\Concerns;

trait HasPerStoreDefaultRecord
{
    public static function bootHasPerStoreDefaultRecord(): void
    {
        static::updated(function (self $record): void {
            if ($record->default) {
                static::withoutGlobalScopes()
                    ->where('store_id', $record->store_id)
                    ->whereDefault(true)
                    ->where('id', '!=', $record->id)
                    ->update(['default' => false]);
            }
        });

        static::created(function (self $record): void {
            if ($record->default) {
                static::withoutGlobalScopes()
                    ->where('store_id', $record->store_id)
                    ->whereDefault(true)
                    ->where('id', '!=', $record->id)
                    ->update(['default' => false]);
            }
        });
    }
}
