<?php

namespace App\Etic\Theme;

use App\Etic\Store\Models\StoreSetting;
use App\Etic\Support\StoreContext;

class ThemeSettings
{
    public const GROUP = 'theme';

    public const KEY = 'values';

    public function __construct(
        private StoreContext $store,
        private ThemeRegistry $themes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolved(?string $handle = null): array
    {
        $handle ??= $this->store->theme();
        $manifest = $this->themes->getOrDefault($handle);

        return array_merge(
            $manifest->defaults(),
            $this->legacy(),
            $this->stored($handle),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->resolved()[$key] ?? $default;

        return $value === '' || $value === null ? $default : $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $handle = $this->store->theme();
        $defaults = $this->themes->getOrDefault($handle)->defaults();
        $allowed = array_keys($defaults);
        $clean = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];

            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            if (is_bool($defaults[$key] ?? null)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
            }

            $clean[$key] = is_string($value) ? trim($value) : $value;
        }

        StoreSetting::query()->updateOrCreate(
            [
                'channel_handle' => $this->store->handle(),
                'group' => self::GROUP,
                'key' => $this->storageKey($handle),
            ],
            ['value' => json_encode($clean, JSON_UNESCAPED_UNICODE)],
        );
    }

    public function clear(?string $handle = null): void
    {
        $handle ??= $this->store->theme();

        StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', self::GROUP)
            ->where('key', $this->storageKey($handle))
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(?string $handle = null): array
    {
        $handle ??= $this->store->theme();
        $scoped = $this->decode($this->raw($this->storageKey($handle)));

        if ($scoped !== []) {
            return $scoped;
        }

        if ($handle === 'default') {
            return $this->decode($this->raw(self::KEY));
        }

        return [];
    }

    public function storageKey(string $handle): string
    {
        return self::KEY.'.'.$handle;
    }

    /**
     * @return array<string, mixed>
     */
    private function legacy(): array
    {
        $rows = StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', self::GROUP)
            ->where('key', '!=', self::KEY)
            ->where('key', 'not like', self::KEY.'.%')
            ->get(['key', 'value']);

        $legacy = [];

        foreach ($rows as $row) {
            $legacy[$row->key] = $row->value;
        }

        if (isset($legacy['primary_color']) && ! isset($legacy['color_primary'])) {
            $legacy['color_primary'] = $legacy['primary_color'];
        }

        return $legacy;
    }

    private function raw(string $key): mixed
    {
        return StoreSetting::query()
            ->where('channel_handle', $this->store->handle())
            ->where('group', self::GROUP)
            ->where('key', $key)
            ->value('value');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $raw): array
    {
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
