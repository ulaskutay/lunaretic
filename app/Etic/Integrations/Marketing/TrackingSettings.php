<?php

namespace App\Etic\Integrations\Marketing;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;

class TrackingSettings
{
    public const GROUP = 'tracking';

    public const KEY = 'ids';

    public function __construct(private StoreContext $store) {}

    /**
     * @return array{
     *     ga4_measurement_id: ?string,
     *     gtm_container_id: ?string,
     *     meta_pixel_id: ?string,
     *     search_console_verification: ?string,
     *     merchant_feed_enabled: bool,
     *     meta_capi_enabled: bool,
     *     meta_capi_token: ?string,
     *     meta_test_event_code: ?string
     * }
     */
    public function resolved(): array
    {
        $stored = $this->stored();
        $defaults = config('etic.tracking', []);

        return [
            'ga4_measurement_id' => $this->firstFilled($stored['ga4_measurement_id'] ?? null, $defaults['ga4_measurement_id'] ?? null),
            'gtm_container_id' => $this->firstFilled($stored['gtm_container_id'] ?? null, $defaults['gtm_container_id'] ?? null),
            'meta_pixel_id' => $this->firstFilled($stored['meta_pixel_id'] ?? null, $defaults['meta_pixel_id'] ?? null),
            'search_console_verification' => $this->firstFilled($stored['search_console_verification'] ?? null, $defaults['search_console_verification'] ?? null),
            'merchant_feed_enabled' => array_key_exists('merchant_feed_enabled', $stored)
                ? (bool) $stored['merchant_feed_enabled']
                : (bool) ($defaults['merchant_feed_enabled'] ?? true),
            'meta_capi_enabled' => array_key_exists('meta_capi_enabled', $stored)
                ? (bool) $stored['meta_capi_enabled']
                : (bool) ($defaults['meta_capi_enabled'] ?? false),
            'meta_capi_token' => $this->firstFilled($stored['meta_capi_token'] ?? null, $defaults['meta_capi_token'] ?? null),
            'meta_test_event_code' => $this->firstFilled($stored['meta_test_event_code'] ?? null, $defaults['meta_test_event_code'] ?? null),
        ];
    }

    public function get(string $key): mixed
    {
        return $this->resolved()[$key] ?? null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $current = $this->stored();

        if (! filled($values['meta_capi_token'] ?? null)) {
            $values['meta_capi_token'] = $current['meta_capi_token'] ?? null;
        }

        $normalized = $this->normalize($values);

        StoreSetting::query()->updateOrCreate(
            [
                'channel_handle' => $this->store->handle(),
                'group' => self::GROUP,
                'key' => self::KEY,
            ],
            ['value' => json_encode(array_merge($current, $normalized), JSON_UNESCAPED_UNICODE)],
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
            'ga4_measurement_id' => $this->clean($values['ga4_measurement_id'] ?? null),
            'gtm_container_id' => $this->clean($values['gtm_container_id'] ?? null),
            'meta_pixel_id' => $this->clean($values['meta_pixel_id'] ?? null),
            'search_console_verification' => $this->clean($values['search_console_verification'] ?? null),
            'merchant_feed_enabled' => (bool) ($values['merchant_feed_enabled'] ?? true),
            'meta_capi_enabled' => (bool) ($values['meta_capi_enabled'] ?? false),
            'meta_test_event_code' => $this->clean($values['meta_test_event_code'] ?? null),
        ];

        if (array_key_exists('meta_capi_token', $values)) {
            $normalized['meta_capi_token'] = $this->clean($values['meta_capi_token']);
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
