<?php

namespace App\Etic\Integrations\Marketing;

use Illuminate\Support\Str;

class TrackingEvent
{
    public string $eventId;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $serverContext
     */
    public function __construct(
        public string $name,
        public array $payload = [],
        ?string $eventId = null,
        public array $serverContext = [],
    ) {
        $this->eventId = $eventId ?: (string) ($payload['event_id'] ?? Str::uuid());
        unset($this->payload['event_id']);
    }

    /** @return array<string, mixed> */
    public function browserPayload(): array
    {
        return [
            'event_id' => $this->eventId,
            ...array_diff_key($this->payload, ['user' => true]),
        ];
    }
}
