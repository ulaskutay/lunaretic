<?php

namespace App\Etic\Storefront;

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

        $id = Cache::get($this->key($token));

        return $id ? User::query()->find($id) : null;
    }

    public function forget(?string $token): void
    {
        if (filled($token)) {
            Cache::forget($this->key((string) $token));
        }
    }

    private function key(string $token): string
    {
        return 'etic.storefront.auth.'.$token;
    }
}
