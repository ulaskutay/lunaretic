<?php

namespace App\Etic\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lunar\Models\Channel;

class Store extends Model
{
    protected $table = 'etic_stores';

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
    ];

    protected function casts(): array
    {
        return [
            'extra_domains' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
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

        if (str_starts_with($host, 'www.')) {
            $candidates[] = substr($host, 4);
        } else {
            $candidates[] = 'www.'.$host;
        }

        $stores = static::query()->where('is_active', true)->get();

        foreach ($candidates as $candidate) {
            $match = $stores->first(fn (self $store) => $store->hosts()->contains($candidate));

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    public function hosts(): Collection
    {
        return collect([$this->primary_domain])
            ->merge($this->extra_domains ?? [])
            ->map(fn ($host) => self::normalizeHost(is_string($host) ? $host : null))
            ->filter()
            ->unique()
            ->values();
    }

    public function channel(): Channel
    {
        return Channel::query()->where('handle', $this->handle)->firstOrFail();
    }

    public function primaryUrl(): string
    {
        $domain = self::normalizeHost($this->primary_domain);

        if ($domain === '') {
            return rtrim((string) config('etic.store.primary_url', config('app.url')), '/');
        }

        $scheme = str_contains($domain, 'localhost') || str_ends_with($domain, '.test') ? 'http' : 'https';

        return $scheme.'://'.$domain;
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

    protected static function booted(): void
    {
        static::saving(function (self $store): void {
            $store->handle = Str::slug((string) $store->handle);
            $store->primary_domain = self::normalizeHost($store->primary_domain) ?: null;
            $store->extra_domains = collect($store->extra_domains ?? [])
                ->map(fn ($host) => self::normalizeHost(is_string($host) ? $host : null))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $store->theme = $store->theme ?: 'default';
        });

        static::saved(function (self $store): void {
            if ($store->is_default) {
                static::query()->where('id', '!=', $store->id)->update(['is_default' => false]);
            }

            $store->syncChannel();
        });
    }
}
