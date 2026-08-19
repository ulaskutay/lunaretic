<?php

namespace App\Etic\Catalog;

use App\Etic\Catalog\Models\CustomerGroup;
use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Lunar\Models\Product;

class AssignProductAvailability
{
    /**
     * @var array{enabled: bool, visible: bool, purchasable: bool, starts_at: \DateTimeInterface, ends_at: null}
     */
    private function groupPivot(): array
    {
        return [
            'enabled' => true,
            'visible' => true,
            'purchasable' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ];
    }

    public function handle(Product $product): void
    {
        $channelId = app(StoreContext::class)->channelId();

        if ($channelId && method_exists($product, 'channels')) {
            $product->channels()->sync([
                $channelId => [
                    'enabled' => true,
                    'starts_at' => now()->subMinute(),
                    'ends_at' => null,
                ],
            ]);
        }

        $group = $this->defaultGroup();

        if (! $group) {
            return;
        }

        $product->customerGroups()->syncWithoutDetaching([
            $group->id => $this->groupPivot(),
        ]);
    }

    public function backfill(): int
    {
        $updated = 0;

        foreach (Store::query()->where('is_active', true)->get() as $store) {
            $updated += app(StoreContext::class)->withoutIsolation(
                fn (): int => $this->backfillStore($store)
            );
        }

        return $updated;
    }

    private function backfillStore(Store $store): int
    {
        app(StoreContext::class)->bind($store);

        $group = $this->defaultGroup();
        $channelId = $store->channel()?->id;

        if (! $group || ! $channelId) {
            return 0;
        }

        $productIds = Product::query()
            ->withoutGlobalScopes()
            ->whereHas('channels', fn ($channels) => $channels->whereKey($channelId))
            ->pluck('id');

        $updated = 0;

        foreach ($productIds as $productId) {
            $product = Product::query()->withoutGlobalScopes()->find($productId);

            if (! $product) {
                continue;
            }

            $product->customerGroups()->syncWithoutDetaching([
                $group->id => $this->groupPivot(),
            ]);
            $updated++;
        }

        return $updated;
    }

    private function defaultGroup(): ?CustomerGroup
    {
        return CustomerGroup::query()->where('default', true)->first()
            ?? CustomerGroup::query()->where('handle', 'retail')->first();
    }
}
