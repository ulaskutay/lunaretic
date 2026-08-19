<?php

namespace App\Etic\Store\Actions;

use App\Etic\Store\Models\Store;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\StoreContext;
use App\Etic\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lunar\Admin\Models\Staff;

class ProvisionStore
{
    public function __construct(
        private CommerceBootstrap $bootstrap,
        private InviteStoreMember $invite,
        private StoreContext $context,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Staff $actor = null): Store
    {
        $handle = strtolower(trim((string) ($data['handle'] ?? '')));

        if (Tenancy::isReservedHandle($handle)) {
            throw ValidationException::withMessages([
                'handle' => __('etic.tenancy.reserved_handle'),
            ]);
        }

        if (blank($data['primary_domain'] ?? null)) {
            $data['primary_domain'] = Tenancy::subdomainFor($handle);
        }

        return $this->context->withoutIsolation(function () use ($data, $actor): Store {
            return DB::transaction(function () use ($data, $actor): Store {
                $store = Store::query()->create([
                    'handle' => $data['handle'],
                    'name' => $data['name'],
                    'primary_domain' => $data['primary_domain'] ?? null,
                    'extra_domains' => $data['extra_domains'] ?? [],
                    'theme' => $data['theme'] ?? 'default',
                    'locale' => $data['locale'] ?? config('etic.store.locale', 'tr'),
                    'currency' => $data['currency'] ?? config('etic.store.currency', 'TRY'),
                    'is_active' => $data['is_active'] ?? true,
                    'is_default' => $data['is_default'] ?? false,
                    'provisioned_at' => now(),
                ]);

                $this->bootstrap->provisionStoreDefaults($store);

                if (filled($data['owner_email'] ?? null)) {
                    $invite = $this->invite->handle(
                        $store,
                        (string) $data['owner_email'],
                        'owner',
                        filled($data['owner_password'] ?? null) ? (string) $data['owner_password'] : null,
                    );

                    session()->flash('etic.store_credentials', [
                        'email' => $invite['staff']->email,
                        'password' => $invite['password'],
                        'url' => $store->adminUrl(),
                    ]);
                }

                $store->auditLogs()->create([
                    'staff_id' => $actor?->id,
                    'action' => 'provisioned',
                    'meta' => ['handle' => $store->handle],
                ]);

                return $store->fresh();
            });
        });
    }
}
