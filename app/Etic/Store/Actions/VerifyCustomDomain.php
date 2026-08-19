<?php

namespace App\Etic\Store\Actions;

use App\Etic\Store\CloudflareCustomHostnames;
use App\Etic\Store\Models\CustomDomain;
use App\Etic\Store\Models\Store;
use App\Etic\Support\DnsResolver;
use App\Etic\Support\Tenancy;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VerifyCustomDomain
{
    public function __construct(
        private DnsResolver $dns,
        private CloudflareCustomHostnames $cloudflare,
    ) {}

    public function handle(CustomDomain $domain): CustomDomain
    {
        $domain->forceFill(['status' => CustomDomain::STATUS_VERIFYING])->save();

        $store = $domain->store()->firstOrFail();
        $target = $this->cnameTarget($store);
        $host = $domain->hostname;

        $verified = $this->matchesCname($host, $target)
            && (
                $this->matchesTxt($host, $domain->txtRecord())
                || $this->matchesTxt($domain->txtLookupHost(), $domain->txtRecord())
            );

        if (! $verified) {
            $domain->forceFill(['status' => CustomDomain::STATUS_FAILED])->save();

            throw new RuntimeException(__('etic.tenancy.domain.verify_failed', [
                'target' => $target,
                'txt' => $domain->txtLookupHost(),
            ]));
        }

        $domain->forceFill([
            'status' => CustomDomain::STATUS_ACTIVE,
            'verified_at' => now(),
            'ssl_status' => 'cloudflare',
        ])->save();

        $store->rememberHost($host);

        return $domain->fresh();
    }

    public function createPending(int $storeId, string $hostname): CustomDomain
    {
        $hostname = $this->normalizeHostname($hostname);
        $this->assertConnectable($storeId, $hostname);

        $domain = CustomDomain::query()->create([
            'store_id' => $storeId,
            'hostname' => $hostname,
            'status' => CustomDomain::STATUS_PENDING,
            'verification_token' => Str::lower(Str::random(32)),
            'ssl_status' => 'pending',
        ]);

        try {
            $this->cloudflare->register($hostname);
        } catch (RuntimeException $e) {
            $domain->delete();

            throw $e;
        }

        return $domain;
    }

    public function cnameTarget(?Store $store = null): string
    {
        $configured = Store::normalizeHost((string) config('etic.tenancy.cname_target'));

        if ($configured !== '') {
            return $configured;
        }

        $base = Tenancy::baseDomain();

        if ($base !== '') {
            return 'cname.'.$base;
        }

        if ($store) {
            return Store::normalizeHost($store->primary_domain)
                ?: (string) Tenancy::subdomainFor($store->handle);
        }

        return '';
    }

    public function forget(CustomDomain $domain): void
    {
        $domain->store?->forgetHost($domain->hostname);
        $this->cloudflare->unregister($domain->hostname);
        $domain->delete();
    }

    public function normalizeHostname(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        $hostname = preg_replace('#^https?://#', '', $hostname) ?? $hostname;
        $hostname = explode('/', $hostname)[0];
        $hostname = explode('?', $hostname)[0];

        return Store::normalizeHost($hostname);
    }

    private function assertConnectable(int $storeId, string $hostname): void
    {
        if ($hostname === '' || ! str_contains($hostname, '.') || filter_var($hostname, FILTER_VALIDATE_IP)) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.invalid'),
            ]);
        }

        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $hostname)) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.invalid'),
            ]);
        }

        if (Tenancy::isPlatformHost($hostname) || Tenancy::isLoopbackHost($hostname)) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.reserved'),
            ]);
        }

        $base = Tenancy::baseDomain();

        if ($base !== '' && ($hostname === $base || str_ends_with($hostname, '.'.$base))) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.platform_subdomain'),
            ]);
        }

        $max = max(1, (int) config('etic.tenancy.max_custom_domains', 3));
        $used = CustomDomain::query()->withoutGlobalScopes()->where('store_id', $storeId)->count();

        if ($used >= $max) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.limit', ['max' => $max]),
            ]);
        }

        $existing = CustomDomain::query()->withoutGlobalScopes()->where('hostname', $hostname)->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'hostname' => (int) $existing->store_id === $storeId
                    ? __('etic.tenancy.domain.already_connected')
                    : __('etic.tenancy.domain.taken'),
            ]);
        }

        $takenByAnotherStore = Store::query()
            ->with('customDomains')
            ->where('id', '!=', $storeId)
            ->get()
            ->contains(fn (Store $store) => $store->hosts()->contains($hostname));

        if ($takenByAnotherStore) {
            throw ValidationException::withMessages([
                'hostname' => __('etic.tenancy.domain.taken'),
            ]);
        }
    }

    private function matchesCname(string $host, string $target): bool
    {
        if ($target === '') {
            return false;
        }

        $expected = rtrim($target, '.');

        return collect($this->dns->cname($host))
            ->map(fn (string $record) => rtrim(Store::normalizeHost($record), '.'))
            ->contains($expected);
    }

    private function matchesTxt(string $host, string $expected): bool
    {
        return collect($this->dns->txt($host))
            ->contains(fn (string $record) => str_contains($record, $expected));
    }
}
