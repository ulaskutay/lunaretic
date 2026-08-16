<?php

namespace App\Etic\Store\Filament\Resources\StoreResource\Pages;

use App\Etic\Support\CommerceBootstrap;
use App\Etic\Store\Filament\Resources\StoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    protected function afterCreate(): void
    {
        app(CommerceBootstrap::class)->provisionStoreDefaults($this->record);
    }
}
