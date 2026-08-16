<?php

namespace App\Etic\Integrations\Shipping;

final class ArasShipmentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $integrationCode,
        public readonly ?string $trackingNumber,
        public readonly string $message,
        public readonly string $resultCode,
    ) {}

    public function trackingUrl(): ?string
    {
        if (blank($this->trackingNumber)) {
            return null;
        }

        return 'https://kargotakip.araskargo.com.tr/?code='.urlencode($this->trackingNumber);
    }
}
