<?php

namespace App\Etic\Integrations\Marketing;

class TrackingEvent
{
    public function __construct(
        public string $name,
        public array $payload = [],
    ) {}
}
