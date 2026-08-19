<?php

namespace App\Etic\Store\Filament\Resources\StoreResource\Pages;

use App\Etic\Store\Actions\InviteStoreMember;
use App\Etic\Store\Filament\Resources\StoreResource;
use App\Etic\Store\Models\Store;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Store $store */
        $store = $this->getRecord();

        $data['panel_members'] = $store->members()
            ->with('staff')
            ->get()
            ->map(fn ($member) => trim(($member->staff?->email ?? '').' ('.$member->role.')'))
            ->filter()
            ->implode("\n") ?: __('etic.filament.stores.members_empty');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['owner_email'], $data['owner_password'], $data['panel_members']);

        return $data;
    }

    protected function afterSave(): void
    {
        $email = trim((string) ($this->data['owner_email'] ?? ''));

        if ($email === '') {
            return;
        }

        /** @var Store $store */
        $store = $this->getRecord();
        $password = filled($this->data['owner_password'] ?? null) ? (string) $this->data['owner_password'] : null;

        $invite = app(InviteStoreMember::class)->handle($store, $email, 'owner', $password);

        Notification::make()
            ->title(__('etic.filament.stores.credentials_ready'))
            ->body(__('etic.filament.stores.credentials_body', [
                'email' => $invite['staff']->email,
                'password' => $invite['password'],
                'url' => $store->adminUrl(),
            ]))
            ->success()
            ->persistent()
            ->send();
    }
}
