<?php

namespace App\Etic\Integrations\Payments;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;

class PaymentCredentials
{
    public const GROUP = 'payments';

    public const KEY = 'iyzico';

    public function __construct(private StoreContext $store) {}

    /**
     * @return array{api_key: ?string, secret_key: ?string, base_url: string}
     */
    public function iyzico(): array
    {
        $stored = $this->stored();
        $defaults = config('etic.iyzico', []);

        return [
            'api_key' => $this->firstFilled($stored['api_key'] ?? null, $defaults['api_key'] ?? null),
            'secret_key' => $this->firstFilled($stored['secret_key'] ?? null, $defaults['secret_key'] ?? null),
            'base_url' => $this->firstFilled($stored['base_url'] ?? null, $defaults['base_url'] ?? null)
                ?: 'https://sandbox-api.iyzipay.com',
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveIyzico(array $values): void
    {
        $current = $this->stored();

        if (! filled($values['api_key'] ?? null)) {
            $values['api_key'] = $current['api_key'] ?? null;
        }

        if (! filled($values['secret_key'] ?? null)) {
            $values['secret_key'] = $current['secret_key'] ?? null;
        }

        StoreSetting::query()->updateOrCreate(
            [
                'channel_handle' => $this->store->handle(),
                'group' => self::GROUP,
                'key' => self::KEY,
            ],
            ['value' => json_encode(array_merge($current, $this->normalize($values)), JSON_UNESCAPED_UNICODE)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(): array
    {
        $raw = StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', self::GROUP)
            ->where('key', self::KEY)
            ->value('value');

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalize(array $values): array
    {
        $normalized = [
            'base_url' => $this->clean($values['base_url'] ?? null),
        ];

        if (array_key_exists('api_key', $values)) {
            $normalized['api_key'] = $this->clean($values['api_key']);
        }

        if (array_key_exists('secret_key', $values)) {
            $normalized['secret_key'] = $this->clean($values['secret_key']);
        }

        return $normalized;
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function firstFilled(mixed $preferred, mixed $fallback): ?string
    {
        $preferred = $this->clean($preferred);

        return $preferred ?? $this->clean($fallback);
    }
}
