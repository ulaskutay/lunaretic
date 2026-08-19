<?php

namespace App\Etic\Store\Filament\Resources\StoreResource\Pages;

use App\Etic\Store\Actions\ProvisionStore;
use App\Etic\Store\Filament\Resources\StoreResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ProvisionStore::class)->handle($data, auth('staff')->user());
    }

    protected function afterCreate(): void
    {
        $credentials = session('etic.store_credentials');

        if (! is_array($credentials)) {
            return;
        }

        Notification::make()
            ->title(__('etic.filament.stores.credentials_ready'))
            ->body(__('etic.filament.stores.credentials_body', $credentials))
            ->success()
            ->persistent()
            ->send();
    }
}
