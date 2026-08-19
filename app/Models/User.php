<?php

namespace App\Models;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lunar\Base\LunarUser as LunarUserInterface;
use Lunar\Base\Traits\LunarUser;

#[Fillable(['name', 'email', 'password', 'store_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements LunarUserInterface
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LunarUser, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function storefrontOrders()
    {
        $channelId = app(StoreContext::class)->channelId();

        return $this->orders()->when($channelId, fn ($query) => $query->where('channel_id', $channelId));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('etic_store', function (Builder $query): void {
            $context = app(StoreContext::class);

            if ($context->isolationBypassed()) {
                return;
            }

            $storeId = $context->store()?->id;

            if ($storeId) {
                $query->where('store_id', $storeId);
            }
        });

        static::creating(function (self $user): void {
            if (blank($user->store_id)) {
                $user->store_id = app(StoreContext::class)->store()?->id;
            }
        });
    }
}
