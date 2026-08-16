<?php

namespace App\Etic\Integrations\Payments;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;

class PaymentCredentials
{
    public const GROUP = 'payments';

    public const KEY_IYZICO = 'iyzico';

    public const KEY_PAYTR = 'paytr';

    public function __construct(private StoreContext $store) {}

    /**
     * @return array{enabled: bool, api_key: ?string, secret_key: ?string, base_url: string}
     */
    public function iyzico(): array
    {
        $stored = $this->stored(self::KEY_IYZICO);
        $defaults = config('etic.iyzico', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'api_key' => $this->firstFilled($stored['api_key'] ?? null, $defaults['api_key'] ?? null),
            'secret_key' => $this->firstFilled($stored['secret_key'] ?? null, $defaults['secret_key'] ?? null),
            'base_url' => $this->firstFilled($stored['base_url'] ?? null, $defaults['base_url'] ?? null)
                ?: 'https://sandbox-api.iyzipay.com',
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     merchant_id: ?string,
     *     merchant_key: ?string,
     *     merchant_salt: ?string,
     *     test_mode: int,
     *     debug_on: int,
     *     no_installment: int,
     *     max_installment: int,
     *     currency: string,
     *     lang: string,
     *     timeout_limit: int
     * }
     */
    public function paytr(): array
    {
        $stored = $this->stored(self::KEY_PAYTR);
        $defaults = config('etic.paytr', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'merchant_id' => $this->firstFilled($stored['merchant_id'] ?? null, $defaults['merchant_id'] ?? null),
            'merchant_key' => $this->firstFilled($stored['merchant_key'] ?? null, $defaults['merchant_key'] ?? null),
            'merchant_salt' => $this->firstFilled($stored['merchant_salt'] ?? null, $defaults['merchant_salt'] ?? null),
            'test_mode' => (int) ($stored['test_mode'] ?? $defaults['test_mode'] ?? 0),
            'debug_on' => (int) ($stored['debug_on'] ?? $defaults['debug_on'] ?? 0),
            'no_installment' => (int) ($stored['no_installment'] ?? $defaults['no_installment'] ?? 0),
            'max_installment' => (int) ($stored['max_installment'] ?? $defaults['max_installment'] ?? 0),
            'currency' => (string) ($stored['currency'] ?? $defaults['currency'] ?? 'TL'),
            'lang' => (string) ($stored['lang'] ?? $defaults['lang'] ?? 'tr'),
            'timeout_limit' => (int) ($stored['timeout_limit'] ?? $defaults['timeout_limit'] ?? 30),
            'non_3d' => (int) ($stored['non_3d'] ?? $defaults['non_3d'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveIyzico(array $values): void
    {
        $this->save(self::KEY_IYZICO, $values, function (array $current, array $values): array {
            if (! filled($values['api_key'] ?? null)) {
                $values['api_key'] = $current['api_key'] ?? null;
            }

            if (! filled($values['secret_key'] ?? null)) {
                $values['secret_key'] = $current['secret_key'] ?? null;
            }

            return $this->normalize($values);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function savePaytr(array $values): void
    {
        $this->save(self::KEY_PAYTR, $values, function (array $current, array $values): array {
            foreach (['merchant_id', 'merchant_key', 'merchant_salt'] as $secret) {
                if (! filled($values[$secret] ?? null)) {
                    $values[$secret] = $current[$secret] ?? null;
                }
            }

            return $this->normalizePaytr($values);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(?string $key = null): array
    {
        $key ??= self::KEY_IYZICO;

        $raw = StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', self::GROUP)
            ->where('key', $key)
            ->value('value');

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  callable(array<string, mixed>, array<string, mixed>): array<string, mixed>  $normalize
     * @param  array<string, mixed>  $values
     */
    private function save(string $key, array $values, callable $normalize): void
    {
        $current = $this->stored($key);

        StoreSetting::query()->updateOrCreate(
            [
                'channel_handle' => $this->store->handle(),
                'group' => self::GROUP,
                'key' => $key,
            ],
            ['value' => json_encode(array_merge($current, $normalize($current, $values)), JSON_UNESCAPED_UNICODE)],
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalize(array $values): array
    {
        $normalized = [
            'enabled' => (bool) ($values['enabled'] ?? false),
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

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalizePaytr(array $values): array
    {
        return [
            'enabled' => (bool) ($values['enabled'] ?? false),
            'merchant_id' => $this->clean($values['merchant_id'] ?? null),
            'merchant_key' => $this->clean($values['merchant_key'] ?? null),
            'merchant_salt' => $this->clean($values['merchant_salt'] ?? null),
            'test_mode' => (int) ($values['test_mode'] ?? 0),
            'debug_on' => (int) ($values['debug_on'] ?? 0),
            'no_installment' => (int) ($values['no_installment'] ?? 0),
            'max_installment' => (int) ($values['max_installment'] ?? 0),
            'currency' => $this->clean($values['currency'] ?? null) ?: 'TL',
            'lang' => $this->clean($values['lang'] ?? null) ?: 'tr',
            'timeout_limit' => (int) ($values['timeout_limit'] ?? 30),
            'non_3d' => (int) ($values['non_3d'] ?? 0),
        ];
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
