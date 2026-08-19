<?php

namespace App\Etic\Support;

use App\Etic\Store\Models\Store;
use Illuminate\Http\Request;

class Tenancy
{
    public static function baseDomain(): string
    {
        $base = Store::normalizeHost((string) config('etic.tenancy.base_domain'));

        if ($base === '' && app()->environment('local')) {
            return 'localhost';
        }

        return $base;
    }

    /**
     * @return list<string>
     */
    public static function reservedHandles(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($handle) => Store::normalizeHost((string) $handle),
            (array) config('etic.tenancy.reserved_handles', [])
        ))));
    }

    public static function isReservedHandle(string $handle): bool
    {
        return in_array(Store::normalizeHost($handle), self::reservedHandles(), true);
    }

    public static function subdomainFor(string $handle): ?string
    {
        $base = self::baseDomain();
        $handle = Store::normalizeHost($handle);

        if ($base === '' || $handle === '' || self::isReservedHandle($handle)) {
            return null;
        }

        return $handle.'.'.$base;
    }

    public static function isPlatformHost(?string $host): bool
    {
        $host = Store::normalizeHost($host);

        if ($host === '') {
            return false;
        }

        $base = self::baseDomain();
        $configured = collect(config('etic.tenancy.platform_hosts', []))
            ->map(fn ($value) => Store::normalizeHost(is_string($value) ? $value : null))
            ->filter()
            ->all();

        if (in_array($host, $configured, true)) {
            return true;
        }

        if ($base !== '' && in_array($host, [$base, 'www.'.$base, 'admin.'.$base], true)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function loopbackHosts(): array
    {
        return ['localhost', '127.0.0.1', '::1'];
    }

    public static function isLoopbackHost(?string $host): bool
    {
        return in_array(Store::normalizeHost($host), self::loopbackHosts(), true);
    }

    public static function isDevFallbackHost(?string $host): bool
    {
        $host = Store::normalizeHost($host);

        if ($host === '') {
            return false;
        }

        if (self::isLoopbackHost($host)) {
            return true;
        }

        return (bool) filter_var($host, FILTER_VALIDATE_IP);
    }

    public static function allowsDefaultFallback(?string $host): bool
    {
        if (self::isDevFallbackHost($host)) {
            return true;
        }

        return (bool) config('etic.tenancy.fallback_to_default');
    }

    public static function isAdminPath(Request $request): bool
    {
        return $request->is('lunar', 'lunar/*', 'livewire/*', 'platform', 'platform/*');
    }
}
