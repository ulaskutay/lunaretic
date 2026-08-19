<?php

namespace App\Etic\Storefront;

use App\Etic\Support\StoreContext;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontAuth
{
    public function issue(User $user): string
    {
        $token = Str::random(48);
        Cache::put($this->key($token), $user->id, now()->addDays(14));

        return $token;
    }

    public function user(?string $token): ?User
    {
        if (! filled($token)) {
            return null;
        }

        $id = Cache::get($this->key((string) $token));
        $user = $id ? User::query()->find($id) : null;
        $storeId = app(StoreContext::class)->store()?->id;

        if ($user && $storeId && (int) $user->store_id !== (int) $storeId) {
            return null;
        }

        return $user;
    }

    public function forget(?string $token): void
    {
        if (filled($token)) {
            Cache::forget($this->key((string) $token));
        }
    }

    private function key(string $token): string
    {
        $storeId = app(StoreContext::class)->store()?->id ?: '0';

        return 'etic.storefront.auth.'.$storeId.'.'.$token;
    }
}
