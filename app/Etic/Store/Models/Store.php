<?php

namespace App\Etic\Store\Models;

use App\Etic\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Channel;

class Store extends Model
{
    use SoftDeletes;

    protected $table = 'etic_stores';

    /**
     * @var array<int|string, bool>
     */
    private array $memberCheckCache = [];

    protected $fillable = [
        'handle',
        'name',
        'primary_domain',
        'extra_domains',
        'theme',
        'locale',
        'currency',
        'is_active',
        'is_default',
        'provisioned_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'extra_domains' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'provisioned_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public static function normalizeHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return $host;
    }

    public static function resolveByHost(?string $host): ?self
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return null;
        }

        $host = self::normalizeHost($host);

        if ($host === '') {
            return null;
        }

        $candidates = [$host];

        if (Tenancy::isLoopbackHost($host)) {
            $candidates = array_merge($candidates, Tenancy::loopbackHosts());
        }

        if (str_starts_with($host, 'www.')) {
            $candidates[] = substr($host, 4);
        } else {
            $candidates[] = 'www.'.$host;
        }

        $stores = static::query()->with('customDomains')->get();

        foreach ($candidates as $candidate) {
            $match = $stores->first(fn (self $store) => $store->hosts()->contains($candidate));

            if ($match) {
                return $match;
            }
        }

        if (Tenancy::isPlatformHost($host)) {
            return static::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        $base = Tenancy::baseDomain();

        if ($base !== '' && str_ends_with($host, '.'.$base)) {
            $handle = substr($host, 0, -strlen('.'.$base));

            return $stores->first(
                fn (self $store) => $store->handle === $handle && $store->is_active
            );
        }

        return null;
    }

    public function hosts(): Collection
    {
        return collect([$this->primary_domain])
            ->merge($this->extra_domains ?? [])
            ->merge($this->customDomains->where('status', CustomDomain::STATUS_ACTIVE)->pluck('hostname'))
            ->map(fn ($host) => self::normalizeHost(is_string($host) ? $host : null))
            ->filter()
            ->unique()
            ->values();
    }

    public function channel(): Channel
    {
        return Channel::query()->where('handle', $this->handle)->firstOrFail();
    }

    public function members(): HasMany
    {
        return $this->hasMany(StoreMember::class)->withoutGlobalScopes();
    }

    public function customDomains(): HasMany
    {
        return $this->hasMany(CustomDomain::class)->withoutGlobalScopes();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(StoreAuditLog::class);
    }

    public function isSuspended(): bool
    {
        return ! $this->is_active || $this->suspended_at !== null;
    }

    public function isCustomHost(?string $host): bool
    {
        $host = self::normalizeHost($host);
        $primary = self::normalizeHost($this->primary_domain);

        if ($host === '' || Tenancy::isLoopbackHost($host)) {
            return false;
        }

        if ($this->is_default && Tenancy::isPlatformHost($host)) {
            return false;
        }

        return $host !== $primary && $host !== 'www.'.$primary;
    }

    public function hasMember(?Staff $staff): bool
    {
        if (! $staff) {
            return false;
        }

        $staffId = $staff->getKey();

        if (array_key_exists($staffId, $this->memberCheckCache)) {
            return $this->memberCheckCache[$staffId];
        }

        return $this->memberCheckCache[$staffId] = $this->members()
            ->withoutGlobalScopes()
            ->where('staff_id', $staffId)
            ->exists();
    }

    public function primaryUrl(): string
    {
        $fallback = rtrim((string) config('etic.store.primary_url', config('app.url')), '/');
        $domain = self::normalizeHost($this->primary_domain);

        if ($domain === '') {
            return $fallback !== '' ? $fallback : '/';
        }

        $fallbackHost = self::normalizeHost((string) parse_url($fallback, PHP_URL_HOST));

        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            if ($fallbackHost !== '' && filter_var($fallbackHost, FILTER_VALIDATE_IP)) {
                return $fallback;
            }

            $scheme = parse_url($fallback, PHP_URL_SCHEME) ?: 'http';

            return $scheme.'://'.$domain;
        }

        if (Tenancy::isLoopbackHost($domain)) {
            if (app()->bound('request')) {
                $request = request();
                $requestHost = self::normalizeHost($request->getHost());

                if (Tenancy::isLoopbackHost($requestHost)) {
                    return rtrim($request->getSchemeAndHttpHost(), '/');
                }
            }

            return $fallback !== '' ? $fallback : 'http://'.$domain;
        }

        $scheme = str_ends_with($domain, '.test') ? 'http' : 'https';

        return $scheme.'://'.$domain;
    }

    public function adminUrl(string $path = '/lunar'): string
    {
        return rtrim($this->primaryUrl(), '/').'/'.ltrim($path, '/');
    }

    public function syncChannel(): Channel
    {
        $channel = Channel::query()->firstOrCreate(
            ['handle' => $this->handle],
            [
                'name' => $this->name,
                'default' => $this->is_default,
                'url' => $this->primaryUrl(),
            ]
        );

        $channel->forceFill([
            'name' => $this->name,
            'default' => $this->is_default,
            'url' => $this->primaryUrl(),
        ])->save();

        if ($this->is_default) {
            Channel::query()->where('id', '!=', $channel->id)->update(['default' => false]);
        }

        return $channel;
    }

    public function rememberHost(string $host): void
    {
        $host = self::normalizeHost($host);

        if ($host === '' || $this->hosts()->contains($host)) {
            return;
        }

        $extra = collect($this->extra_domains ?? [])->push($host)->unique()->values()->all();
        $this->forceFill(['extra_domains' => $extra])->saveQuietly();
    }

    public function forgetHost(string $host): void
    {
        $host = self::normalizeHost($host);
        $extra = collect($this->extra_domains ?? [])
            ->reject(fn ($value) => self::normalizeHost(is_string($value) ? $value : null) === $host)
            ->values()
            ->all();
        $this->forceFill(['extra_domains' => $extra])->saveQuietly();
    }

    protected static function booted(): void
    {
        static::saving(function (self $store): void {
            $store->handle = Str::slug((string) $store->handle);

            if (Tenancy::isReservedHandle($store->handle)) {
                throw ValidationException::withMessages([
                    'handle' => __('etic.tenancy.reserved_handle'),
                ]);
            }

            if (blank($store->primary_domain)) {
                $store->primary_domain = Tenancy::subdomainFor($store->handle);
            }

            $store->primary_domain = self::normalizeHost($store->primary_domain) ?: null;
            $store->extra_domains = collect($store->extra_domains ?? [])
                ->map(fn ($host) => self::normalizeHost(is_string($host) ? $host : null))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $store->theme = $store->theme ?: 'default';

            if ($store->is_active) {
                $store->suspended_at = null;
            } elseif ($store->suspended_at === null) {
                $store->suspended_at = now();
            }
        });

        static::saved(function (self $store): void {
            if ($store->is_default) {
                static::query()->where('id', '!=', $store->id)->update(['is_default' => false]);
            }

            $store->syncChannel();
        });
    }
}
