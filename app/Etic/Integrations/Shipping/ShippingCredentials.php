<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;

class ShippingCredentials
{
    public const GROUP = 'shipping';

    public const KEY_ARAS = 'aras';

    public const KEY_SURAT = 'surat';

    public const KEY_MNG = 'mng';

    public const KEY_YURTICI = 'yurtici';

    public function __construct(private StoreContext $store) {}

    /**
     * @return array{
     *     enabled: bool,
     *     username: ?string,
     *     password: ?string,
     *     customer_code: ?string,
     *     test_mode: bool,
     *     default_weight_kg: float,
     *     default_piece_count: int,
     *     mark_dispatched: bool
     * }
     */
    public function aras(): array
    {
        $stored = $this->stored(self::KEY_ARAS);
        $defaults = config('etic.aras', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'username' => $this->firstFilled($stored['username'] ?? null, $defaults['username'] ?? null),
            'password' => $this->firstFilled($stored['password'] ?? null, $defaults['password'] ?? null),
            'customer_code' => $this->firstFilled($stored['customer_code'] ?? null, $defaults['customer_code'] ?? null),
            'test_mode' => (bool) ($stored['test_mode'] ?? $defaults['test_mode'] ?? true),
            'default_weight_kg' => (float) ($stored['default_weight_kg'] ?? $defaults['default_weight_kg'] ?? 1),
            'default_piece_count' => max(1, (int) ($stored['default_piece_count'] ?? $defaults['default_piece_count'] ?? 1)),
            'mark_dispatched' => (bool) ($stored['mark_dispatched'] ?? $defaults['mark_dispatched'] ?? true),
        ];
    }

    public function arasConfigured(): bool
    {
        $config = $this->aras();

        return $config['enabled']
            && filled($config['username'])
            && filled($config['password']);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     username: ?string,
     *     password: ?string,
     *     web_password: ?string,
     *     test_mode: bool,
     *     default_weight_kg: float,
     *     default_piece_count: int,
     *     mark_dispatched: bool
     * }
     */
    public function surat(): array
    {
        $stored = $this->stored(self::KEY_SURAT);
        $defaults = config('etic.surat', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'username' => $this->firstFilled($stored['username'] ?? null, $defaults['username'] ?? null),
            'password' => $this->firstFilled($stored['password'] ?? null, $defaults['password'] ?? null),
            'web_password' => $this->firstFilled($stored['web_password'] ?? null, $defaults['web_password'] ?? null),
            'test_mode' => (bool) ($stored['test_mode'] ?? $defaults['test_mode'] ?? true),
            'default_weight_kg' => (float) ($stored['default_weight_kg'] ?? $defaults['default_weight_kg'] ?? 1),
            'default_piece_count' => max(1, (int) ($stored['default_piece_count'] ?? $defaults['default_piece_count'] ?? 1)),
            'mark_dispatched' => (bool) ($stored['mark_dispatched'] ?? $defaults['mark_dispatched'] ?? true),
        ];
    }

    public function suratConfigured(): bool
    {
        $config = $this->surat();

        return $config['enabled']
            && filled($config['username'])
            && filled($config['password']);
    }

    public function suratTrackingConfigured(): bool
    {
        $config = $this->surat();

        return $this->suratConfigured() && filled($config['web_password']);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     client_id: ?string,
     *     client_secret: ?string,
     *     customer_number: ?string,
     *     password: ?string,
     *     test_mode: bool,
     *     default_city_code: int,
     *     default_district_code: int,
     *     default_weight_kg: float,
     *     default_piece_count: int,
     *     mark_dispatched: bool
     * }
     */
    public function mng(): array
    {
        $stored = $this->stored(self::KEY_MNG);
        $defaults = config('etic.mng', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'client_id' => $this->firstFilled($stored['client_id'] ?? null, $defaults['client_id'] ?? null),
            'client_secret' => $this->firstFilled($stored['client_secret'] ?? null, $defaults['client_secret'] ?? null),
            'customer_number' => $this->firstFilled($stored['customer_number'] ?? null, $defaults['customer_number'] ?? null),
            'password' => $this->firstFilled($stored['password'] ?? null, $defaults['password'] ?? null),
            'test_mode' => (bool) ($stored['test_mode'] ?? $defaults['test_mode'] ?? true),
            'default_city_code' => max(1, (int) ($stored['default_city_code'] ?? $defaults['default_city_code'] ?? 34)),
            'default_district_code' => max(1, (int) ($stored['default_district_code'] ?? $defaults['default_district_code'] ?? 100)),
            'default_weight_kg' => (float) ($stored['default_weight_kg'] ?? $defaults['default_weight_kg'] ?? 1),
            'default_piece_count' => max(1, (int) ($stored['default_piece_count'] ?? $defaults['default_piece_count'] ?? 1)),
            'mark_dispatched' => (bool) ($stored['mark_dispatched'] ?? $defaults['mark_dispatched'] ?? true),
        ];
    }

    public function mngConfigured(): bool
    {
        $config = $this->mng();

        return $config['enabled']
            && filled($config['client_id'])
            && filled($config['client_secret'])
            && filled($config['customer_number'])
            && filled($config['password']);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     username: ?string,
     *     password: ?string,
     *     test_mode: bool,
     *     default_weight_kg: float,
     *     default_piece_count: int,
     *     default_desi: float,
     *     mark_dispatched: bool
     * }
     */
    public function yurtici(): array
    {
        $stored = $this->stored(self::KEY_YURTICI);
        $defaults = config('etic.yurtici', []);

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled'] ?? false),
            'username' => $this->firstFilled($stored['username'] ?? null, $defaults['username'] ?? null),
            'password' => $this->firstFilled($stored['password'] ?? null, $defaults['password'] ?? null),
            'test_mode' => (bool) ($stored['test_mode'] ?? $defaults['test_mode'] ?? true),
            'default_weight_kg' => (float) ($stored['default_weight_kg'] ?? $defaults['default_weight_kg'] ?? 1),
            'default_piece_count' => max(1, (int) ($stored['default_piece_count'] ?? $defaults['default_piece_count'] ?? 1)),
            'default_desi' => (float) ($stored['default_desi'] ?? $defaults['default_desi'] ?? 1),
            'mark_dispatched' => (bool) ($stored['mark_dispatched'] ?? $defaults['mark_dispatched'] ?? true),
        ];
    }

    public function yurticiConfigured(): bool
    {
        $config = $this->yurtici();

        return $config['enabled']
            && filled($config['username'])
            && filled($config['password']);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveAras(array $values): void
    {
        $this->save(self::KEY_ARAS, $values, function (array $current, array $values): array {
            if (! filled($values['password'] ?? null)) {
                $values['password'] = $current['password'] ?? null;
            }

            return $this->normalizeAras($values);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveSurat(array $values): void
    {
        $this->save(self::KEY_SURAT, $values, function (array $current, array $values): array {
            foreach (['password', 'web_password'] as $secret) {
                if (! filled($values[$secret] ?? null)) {
                    $values[$secret] = $current[$secret] ?? null;
                }
            }

            return [...$this->normalizeAras($values), 'web_password' => $this->clean($values['web_password'] ?? null)];
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveMng(array $values): void
    {
        $this->save(self::KEY_MNG, $values, function (array $current, array $values): array {
            if (! filled($values['password'] ?? null)) {
                $values['password'] = $current['password'] ?? null;
            }

            if (! filled($values['client_secret'] ?? null)) {
                $values['client_secret'] = $current['client_secret'] ?? null;
            }

            return $this->normalizeMng($values);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveYurtici(array $values): void
    {
        $this->save(self::KEY_YURTICI, $values, function (array $current, array $values): array {
            if (! filled($values['password'] ?? null)) {
                $values['password'] = $current['password'] ?? null;
            }

            return $this->normalizeYurtici($values);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(?string $key = null): array
    {
        $key ??= self::KEY_ARAS;

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
    public function normalizeAras(array $values): array
    {
        return [
            'enabled' => (bool) ($values['enabled'] ?? false),
            'username' => $this->clean($values['username'] ?? null),
            'password' => $this->clean($values['password'] ?? null),
            'customer_code' => $this->clean($values['customer_code'] ?? null),
            'test_mode' => (bool) ($values['test_mode'] ?? true),
            'default_weight_kg' => max(0.1, (float) ($values['default_weight_kg'] ?? 1)),
            'default_piece_count' => max(1, (int) ($values['default_piece_count'] ?? 1)),
            'mark_dispatched' => (bool) ($values['mark_dispatched'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalizeMng(array $values): array
    {
        return [
            'enabled' => (bool) ($values['enabled'] ?? false),
            'client_id' => $this->clean($values['client_id'] ?? null),
            'client_secret' => $this->clean($values['client_secret'] ?? null),
            'customer_number' => $this->clean($values['customer_number'] ?? null),
            'password' => $this->clean($values['password'] ?? null),
            'test_mode' => (bool) ($values['test_mode'] ?? true),
            'default_city_code' => max(1, (int) ($values['default_city_code'] ?? 34)),
            'default_district_code' => max(1, (int) ($values['default_district_code'] ?? 100)),
            'default_weight_kg' => max(0.1, (float) ($values['default_weight_kg'] ?? 1)),
            'default_piece_count' => max(1, (int) ($values['default_piece_count'] ?? 1)),
            'mark_dispatched' => (bool) ($values['mark_dispatched'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalizeYurtici(array $values): array
    {
        return [
            'enabled' => (bool) ($values['enabled'] ?? false),
            'username' => $this->clean($values['username'] ?? null),
            'password' => $this->clean($values['password'] ?? null),
            'test_mode' => (bool) ($values['test_mode'] ?? true),
            'default_weight_kg' => max(0.1, (float) ($values['default_weight_kg'] ?? 1)),
            'default_piece_count' => max(1, (int) ($values['default_piece_count'] ?? 1)),
            'default_desi' => max(0.1, (float) ($values['default_desi'] ?? 1)),
            'mark_dispatched' => (bool) ($values['mark_dispatched'] ?? true),
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
