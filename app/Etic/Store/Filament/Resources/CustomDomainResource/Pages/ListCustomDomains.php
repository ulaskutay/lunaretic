<?php

namespace App\Etic\Store\Filament\Resources\CustomDomainResource\Pages;

use App\Etic\Store\Filament\Resources\CustomDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomDomains extends ListRecords
{
    protected static string $resource = CustomDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
