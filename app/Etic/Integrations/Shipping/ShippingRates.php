<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;
use Illuminate\Support\Str;

class ShippingRates
{
    public function __construct(private StoreContext $store) {}

    /**
     * @return list<array{name: string, identifier: string, description: string, price: int, max_subtotal: int|null}>
     */
    public function all(): array
    {
        $stored = StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', 'shipping')
            ->where('key', 'table_rates')
            ->value('value');

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);

            if (is_array($decoded) && $decoded !== []) {
                return $this->normalize($decoded);
            }
        }

        return $this->normalize(config('etic.shipping.table_rates', []));
    }

    /**
     * @param  list<array<string, mixed>>  $rates
     */
    public function save(array $rates): void
    {
        StoreSetting::query()->updateOrCreate(
            [
                'channel_handle' => $this->store->handle(),
                'group' => 'shipping',
                'key' => 'table_rates',
            ],
            ['value' => json_encode($this->normalize($rates), JSON_UNESCAPED_UNICODE)],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rates
     * @return list<array{name: string, identifier: string, description: string, price_tl: float, max_subtotal_tl: float|null}>
     */
    public function toFormState(array $rates): array
    {
        return array_map(fn (array $rate) => [
            'name' => $rate['name'],
            'identifier' => $rate['identifier'],
            'description' => $rate['description'],
            'price_tl' => round($rate['price'] / 100, 2),
            'max_subtotal_tl' => $rate['max_subtotal'] === null
                ? null
                : round($rate['max_subtotal'] / 100, 2),
        ], $rates);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{name: string, identifier: string, description: string, price: int, max_subtotal: int|null}>
     */
    public function fromFormState(array $rows): array
    {
        return $this->normalize(array_map(fn (array $row) => [
            'name' => $row['name'] ?? '',
            'identifier' => $row['identifier'] ?? '',
            'description' => $row['description'] ?? '',
            'price' => $this->toMinor($row['price_tl'] ?? 0),
            'max_subtotal' => filled($row['max_subtotal_tl'] ?? null)
                ? $this->toMinor($row['max_subtotal_tl'])
                : null,
        ], $rows));
    }

    /**
     * @param  list<array<string, mixed>>  $rates
     * @return list<array{name: string, identifier: string, description: string, price: int, max_subtotal: int|null}>
     */
    private function normalize(array $rates): array
    {
        $normalized = [];

        foreach ($rates as $rate) {
            if (! is_array($rate)) {
                continue;
            }

            $name = trim((string) ($rate['name'] ?? ''));
            $identifier = Str::slug((string) ($rate['identifier'] ?? $name));

            if ($name === '' || $identifier === '') {
                continue;
            }

            $max = $rate['max_subtotal'] ?? null;

            $normalized[] = [
                'name' => $name,
                'identifier' => $identifier,
                'description' => trim((string) ($rate['description'] ?? 'Türkiye içi teslimat')),
                'price' => max(0, (int) ($rate['price'] ?? 0)),
                'max_subtotal' => $max === null || $max === '' ? null : max(0, (int) $max),
            ];
        }

        return $normalized;
    }

    private function toMinor(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
