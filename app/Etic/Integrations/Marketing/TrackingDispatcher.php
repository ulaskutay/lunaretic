<?php

namespace App\Etic\Integrations\Marketing;

class TrackingDispatcher
{
    /** @var list<TrackingEvent> */
    private array $events = [];

    public function record(string $name, array $payload = []): TrackingEvent
    {
        $event = new TrackingEvent($name, $payload);
        $this->events[] = $event;

        return $event;
    }

    /** @return list<TrackingEvent> */
    public function events(): array
    {
        return $this->events;
    }

    public function dataLayer(): array
    {
        return array_map(fn (TrackingEvent $event) => [
            'event' => $event->name,
            ...$event->payload,
        ], $this->events);
    }
}
