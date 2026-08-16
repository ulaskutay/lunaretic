<?php

namespace App\Etic\Integrations\Shipping;

final class ShipmentTracking
{
    /**
     * @return array{
     *     carrier: string,
     *     carrier_label: string,
     *     integration_code: ?string,
     *     tracking_number: string,
     *     tracking_url: ?string,
     *     status: ?string,
     *     shipped_at: ?string
     * }|null
     */
    public static function fromMeta(?array $meta): ?array
    {
        foreach (['mng', 'yurtici', 'surat', 'aras'] as $carrier) {
            $tracking = self::fromCarrierMeta($carrier, $meta);

            if ($tracking !== null) {
                return $tracking;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     carrier: string,
     *     carrier_label: string,
     *     integration_code: ?string,
     *     tracking_number: string,
     *     tracking_url: ?string,
     *     status: ?string,
     *     shipped_at: ?string
     * }|null
     */
    private static function fromCarrierMeta(string $carrier, ?array $meta): ?array
    {
        $data = data_get($meta, $carrier);

        if (! is_array($data) || blank($data['tracking_number'] ?? null)) {
            return null;
        }

        return [
            'carrier' => $carrier,
            'carrier_label' => self::label($carrier),
            'integration_code' => $data['integration_code'] ?? null,
            'tracking_number' => (string) $data['tracking_number'],
            'tracking_url' => $data['tracking_url'] ?? null,
            'status' => $data['last_status'] ?? null,
            'shipped_at' => $data['shipped_at'] ?? null,
        ];
    }

    public static function label(string $carrier): string
    {
        return match ($carrier) {
            'mng' => 'MNG Kargo',
            'yurtici' => 'Yurtiçi Kargo',
            'surat' => 'Sürat Kargo',
            'aras' => 'Aras Kargo',
            default => ucfirst($carrier),
        };
    }

    public static function trackingUrl(string $carrier, string $trackingNumber): string
    {
        return match ($carrier) {
            'mng' => 'https://www.mngkargo.com.tr/tr/gonderi-takip?shipmentId='.urlencode($trackingNumber),
            'yurtici' => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code='.urlencode($trackingNumber),
            'surat' => 'https://www.suratkargo.com.tr/kargotakip/?kargotakipno='.urlencode($trackingNumber),
            'aras' => 'https://kargotakip.araskargo.com.tr/?code='.urlencode($trackingNumber),
            default => '',
        };
    }
}
