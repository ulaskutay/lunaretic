<?php

namespace App\Etic\Integrations\Shipping;

final class YurticiShipmentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $integrationCode,
        public readonly ?string $trackingNumber,
        public readonly string $message,
    ) {}

    public function trackingUrl(): ?string
    {
        if (blank($this->trackingNumber)) {
            return null;
        }

        return ShipmentTracking::trackingUrl('yurtici', $this->trackingNumber);
    }
}
