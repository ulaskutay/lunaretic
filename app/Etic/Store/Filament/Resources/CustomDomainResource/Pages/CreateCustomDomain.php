<?php

namespace App\Etic\Store\Filament\Resources\CustomDomainResource\Pages;

use App\Etic\Store\Actions\VerifyCustomDomain;
use App\Etic\Store\Filament\Resources\CustomDomainResource;
use App\Etic\Support\StoreContext;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomDomain extends CreateRecord
{
    protected static string $resource = CustomDomainResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $storeId = $data['store_id'] ?? app(StoreContext::class)->store()?->id;

        if (Filament::getCurrentOrDefaultPanel()?->getId() !== 'platform') {
            $storeId = app(StoreContext::class)->store()?->id;
        }

        return app(VerifyCustomDomain::class)->createPending((int) $storeId, (string) $data['hostname']);
    }
}
