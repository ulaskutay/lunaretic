<?php

namespace App\Etic\Support;

use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;

class StoreContext
{
    public function handle(): string
    {
        return (string) config('etic.store.handle');
    }

    public function channel(): Channel
    {
        $handle = $this->handle();

        return Channel::query()->where('handle', $handle)->first()
            ?? Channel::query()->where('default', true)->firstOrFail();
    }

    public function currency(): Currency
    {
        $code = (string) config('etic.store.currency');

        return Currency::query()->where('code', $code)->first()
            ?? Currency::query()->where('default', true)->firstOrFail();
    }

    public function language(): Language
    {
        $code = (string) config('etic.store.locale');

        return Language::query()->where('code', $code)->first()
            ?? Language::query()->where('default', true)->firstOrFail();
    }

    public function primaryUrl(): string
    {
        return rtrim((string) config('etic.store.primary_url', config('app.url')), '/');
    }
}
