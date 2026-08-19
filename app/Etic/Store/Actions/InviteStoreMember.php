<?php

namespace App\Etic\Store\Actions;

use App\Etic\Store\Models\Store;
use App\Etic\Store\Models\StoreMember;
use App\Etic\Store\Notifications\StoreInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;

class InviteStoreMember
{
    /**
     * @return array{staff: Staff, password: string, created: bool}
     */
    public function handle(Store $store, string $email, string $role = 'owner', ?string $password = null): array
    {
        $email = strtolower(trim($email));
        $password = filled($password) ? (string) $password : Str::password(12);
        $created = false;

        $staff = Staff::query()->where('email', $email)->first();

        if (! $staff) {
            $created = true;
            $parts = explode('@', $email);
            $staff = Staff::query()->create([
                'first_name' => Str::title(str_replace(['.', '_', '-'], ' ', $parts[0])),
                'last_name' => $store->name,
                'email' => $email,
                'password' => $password,
                'admin' => false,
            ]);
        } else {
            $staff->forceFill(['password' => $password])->save();
        }

        StoreMember::query()->withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'staff_id' => $staff->id],
            ['role' => $role]
        );

        app(GrantStorePanelAccess::class)->handle($staff);

        Notification::send($staff, new StoreInvitation($store, $password));

        return [
            'staff' => $staff,
            'password' => $password,
            'created' => $created,
        ];
    }
}
