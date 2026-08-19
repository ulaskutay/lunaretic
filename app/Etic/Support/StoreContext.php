<?php

namespace App\Etic\Support;

use App\Etic\Store\Models\Store;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;

class StoreContext
{
    private ?Store $store = null;

    private bool $bound = false;

    private bool $bypassIsolation = false;

    private ?Channel $channel = null;

    private ?Currency $currency = null;

    private ?Language $language = null;

    public function bind(?Store $store): void
    {
        $this->store = $store;
        $this->bound = true;
        $this->channel = null;
        $this->currency = null;
        $this->language = null;
    }

    public function bindByHandle(string $handle): void
    {
        $this->bind($this->findStoreByHandle($handle));
    }

    public function bindFromModel(?object $model): void
    {
        if (! $model) {
            return;
        }

        if (isset($model->store_id) && $model->store_id) {
            $store = Store::query()->find($model->store_id);

            if ($store) {
                $this->bind($store);

                return;
            }
        }

        $handle = null;

        if (method_exists($model, 'channels')) {
            $handle = $model->channels()->wherePivot('enabled', true)->value('handle')
                ?? $model->channels()->value('handle');
        }

        if (! is_string($handle) || $handle === '') {
            $channelId = $model->channel_id ?? null;
            $handle = $channelId ? Channel::query()->whereKey($channelId)->value('handle') : null;
        }

        if (is_string($handle) && $handle !== '') {
            $this->bindByHandle($handle);
        }
    }

    public function isolationBypassed(): bool
    {
        return $this->bypassIsolation;
    }

    public function withoutIsolation(callable $callback): mixed
    {
        $previous = $this->bypassIsolation;
        $this->bypassIsolation = true;

        try {
            return $callback();
        } finally {
            $this->bypassIsolation = $previous;
        }
    }

    public function store(): ?Store
    {
        if ($this->bound) {
            return $this->store;
        }

        if ($this->store) {
            return $this->store;
        }

        return $this->store = $this->defaultStore();
    }

    public function handle(): string
    {
        return $this->store()?->handle ?: $this->configuredHandle();
    }

    public function name(): string
    {
        return $this->store()?->name ?: (string) config('etic.store.name');
    }

    public function theme(): string
    {
        $theme = $this->store()?->theme ?: (string) config('etic.theme', 'default');
        $path = resource_path('themes/'.$theme);

        return is_dir($path) ? $theme : 'default';
    }

    public function channel(): Channel
    {
        if ($this->channel) {
            return $this->channel;
        }

        $handle = $this->handle();
        $channel = Channel::query()->where('handle', $handle)->first();

        if ($channel) {
            return $this->channel = $channel;
        }

        if ($this->bound && $this->store) {
            throw new \RuntimeException('Mağaza kanalı bulunamadı: '.$handle);
        }

        return $this->channel = Channel::query()->where('default', true)->firstOrFail();
    }

    public function channelId(): ?int
    {
        try {
            return $this->channel()->id;
        } catch (\Throwable) {
            return null;
        }
    }

    public function currency(): Currency
    {
        if ($this->currency) {
            return $this->currency;
        }

        $code = $this->store()?->currency ?: (string) config('etic.store.currency');

        return $this->currency = Currency::query()->where('code', $code)->first()
            ?? Currency::query()->where('default', true)->firstOrFail();
    }

    public function language(): Language
    {
        if ($this->language) {
            return $this->language;
        }

        $code = $this->store()?->locale ?: (string) config('etic.store.locale');

        return $this->language = Language::query()->where('code', $code)->first()
            ?? Language::query()->where('default', true)->firstOrFail();
    }

    public function locale(): string
    {
        return $this->store()?->locale ?: (string) config('etic.store.locale', 'tr');
    }

    public function primaryUrl(): string
    {
        if ($store = $this->store()) {
            return $store->primaryUrl();
        }

        return rtrim((string) config('etic.store.primary_url', config('app.url')), '/');
    }

    public function applyRuntime(): void
    {
        $locale = $this->locale();
        app()->setLocale($locale);

        $themePath = resource_path('themes/'.$this->theme());
        View::replaceNamespace('theme', $themePath);
        Blade::anonymousComponentPath($themePath.'/components', 'theme');
    }

    private function defaultStore(): ?Store
    {
        if (! $this->storesReady()) {
            return null;
        }

        return Store::query()->where('is_default', true)->where('is_active', true)->first()
            ?? Store::query()->where('handle', $this->configuredHandle())->first()
            ?? Store::query()->where('is_active', true)->first();
    }

    private function findStoreByHandle(string $handle): ?Store
    {
        if (! $this->storesReady()) {
            return null;
        }

        return Store::query()->where('handle', $handle)->where('is_active', true)->first();
    }

    private function storesReady(): bool
    {
        try {
            return Schema::hasTable('etic_stores');
        } catch (\Throwable) {
            return false;
        }
    }

    private function configuredHandle(): string
    {
        return (string) config('etic.store.handle');
    }
}
