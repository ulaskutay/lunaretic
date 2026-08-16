<?php

namespace App\Etic\Integrations\Payments;

final class PaymentProviderCatalog
{
    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     logo: string,
     *     logo_source: string,
     *     website: string,
     *     accent: string
     * }>
     */
    public static function all(): array
    {
        return [
            self::entry(
                key: 'paytr',
                name: __('etic.filament.payments.paytr'),
                description: __('etic.filament.payments.paytr_help'),
                file: 'paytr.svg',
                source: 'https://www.paytr.com/',
                website: 'https://www.paytr.com/',
                accent: '#00A0D2',
            ),
            self::entry(
                key: 'iyzico',
                name: __('etic.filament.payments.iyzico'),
                description: __('etic.filament.payments.iyzico_help'),
                file: 'iyzico.svg',
                source: 'https://www.iyzico.com/',
                website: 'https://www.iyzico.com/',
                accent: '#1B4D8C',
            ),
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     logo: string,
     *     logo_source: string,
     *     website: string,
     *     accent: string
     * }|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $provider) {
            if ($provider['key'] === $key) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     logo: string,
     *     logo_source: string,
     *     website: string,
     *     accent: string
     * }
     */
    private static function entry(
        string $key,
        string $name,
        string $description,
        string $file,
        string $source,
        string $website,
        string $accent,
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'logo' => asset('images/payments/'.$file),
            'logo_source' => $source,
            'website' => $website,
            'accent' => $accent,
        ];
    }
}
