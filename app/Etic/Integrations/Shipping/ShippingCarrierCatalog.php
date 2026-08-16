<?php

namespace App\Etic\Integrations\Shipping;

final class ShippingCarrierCatalog
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
                key: 'aras',
                name: __('etic.filament.shipping.aras'),
                description: __('etic.filament.shipping.aras_help'),
                file: 'aras.svg',
                source: 'https://www.araskargo.com.tr/aras-nav-logo.svg',
                website: 'https://www.araskargo.com.tr/',
                accent: '#E30613',
            ),
            self::entry(
                key: 'surat',
                name: __('etic.filament.shipping.surat'),
                description: __('etic.filament.shipping.surat_help'),
                file: 'surat.png',
                source: 'https://www.suratkargo.com.tr/assets/images/logo.png',
                website: 'https://www.suratkargo.com.tr/',
                accent: '#003DA5',
            ),
            self::entry(
                key: 'mng',
                name: __('etic.filament.shipping.mng'),
                description: __('etic.filament.shipping.mng_help'),
                file: 'mng.svg',
                source: 'https://www.mngkargo.com.tr/ (MNG_Cargo_logo.svg)',
                website: 'https://www.mngkargo.com.tr/',
                accent: '#D40511',
            ),
            self::entry(
                key: 'yurtici',
                name: __('etic.filament.shipping.yurtici'),
                description: __('etic.filament.shipping.yurtici_help'),
                file: 'yurtici.svg',
                source: 'https://www.yurticikargo.com/web_files/yurtici-kargo/assets/img/logo.svg',
                website: 'https://www.yurticikargo.com/',
                accent: '#004B9B',
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
        foreach (self::all() as $carrier) {
            if ($carrier['key'] === $key) {
                return $carrier;
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
            'logo' => asset('images/shipping/'.$file),
            'logo_source' => $source,
            'website' => $website,
            'accent' => $accent,
        ];
    }
}
