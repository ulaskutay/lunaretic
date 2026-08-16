<?php

namespace App\Etic\Integrations\Marketing;

class TrackingDispatcher
{
    public const FLASH_KEY = 'etic.tracking.queue';

    /** @var list<TrackingEvent> */
    private array $events = [];

    private bool $hydrated = false;

    public static function fromMinor(int $minor): float
    {
        return round($minor / 100, 2);
    }

    public function resetAndHydrate(): void
    {
        $this->events = [];
        $this->hydrated = false;
        $this->hydrateFlash();
    }

    public function record(string $name, array $payload = [], bool $flash = false): TrackingEvent
    {
        $event = new TrackingEvent($name, $payload, serverContext: $this->serverContext());

        if (request()->attributes->get('etic.theme_preview')) {
            return $event;
        }

        $this->events[] = $event;

        if ($flash) {
            $this->pushFlash($event);
        }

        $this->queueCapi($event);

        return $event;
    }

    public function flashLast(): void
    {
        $event = $this->events[array_key_last($this->events)] ?? null;

        if ($event) {
            $this->pushFlash($event);
        }
    }

    public function hydrateFlash(): void
    {
        if ($this->hydrated) {
            return;
        }

        $this->hydrated = true;

        foreach (session()->get(self::FLASH_KEY, []) as $item) {
            if (! is_array($item) || empty($item['name'])) {
                continue;
            }

            $this->events[] = new TrackingEvent(
                $item['name'],
                $item['payload'] ?? [],
                $item['event_id'] ?? null,
            );
        }
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
            ...$event->browserPayload(),
        ], $this->events);
    }

    /** @return list<array{event: string, params: array}> */
    public function ga4Commands(): array
    {
        return array_map(fn (TrackingEvent $event) => [
            'event' => $event->name,
            'params' => $event->browserPayload(),
        ], $this->events);
    }

    /** @return list<array{event: string, params: array}> */
    public function metaCommands(): array
    {
        $map = MetaConversionsClient::EVENTS + [
            'view_item_list' => 'ViewContent',
            'view_category' => 'ViewContent',
        ];

        $commands = [];

        foreach ($this->events as $event) {
            if (! isset($map[$event->name])) {
                continue;
            }

            $params = $event->browserPayload();
            $pixel = [];

            if (isset($params['value'])) {
                $pixel['value'] = $params['value'];
                $pixel['currency'] = $params['currency'] ?? 'TRY';
            }

            if (isset($params['search_term'])) {
                $pixel['search_string'] = $params['search_term'];
            }

            if (isset($params['item_id'])) {
                $pixel['content_ids'] = [$params['item_id']];
                $pixel['content_type'] = 'product';
            }

            if (isset($params['transaction_id'])) {
                $pixel['order_id'] = $params['transaction_id'];
            }

            $pixel['eventID'] = $event->eventId;

            $commands[] = [
                'event' => $map[$event->name],
                'params' => $pixel,
            ];
        }

        return $commands;
    }

    private function queueCapi(TrackingEvent $event): void
    {
        $client = app(MetaConversionsClient::class);

        if (! $client->enabled() || ! isset(MetaConversionsClient::EVENTS[$event->name])) {
            return;
        }

        if (app()->runningUnitTests()) {
            $client->send($event);

            return;
        }

        dispatch(fn () => $client->send($event))->afterResponse();
    }

    private function pushFlash(TrackingEvent $event): void
    {
        $queue = session()->get(self::FLASH_KEY, []);
        $queue[] = [
            'name' => $event->name,
            'payload' => $event->payload,
            'event_id' => $event->eventId,
        ];
        session()->flash(self::FLASH_KEY, $queue);
    }

    /** @return array<string, mixed> */
    private function serverContext(): array
    {
        $request = request();
        $fbclid = $request->query('fbclid');
        $fbc = $request->cookie('_fbc');

        if (! $fbc && filled($fbclid)) {
            $fbc = 'fb.1.'.time().'.'.$fbclid;
        }

        return array_filter([
            'event_source_url' => $request->fullUrl(),
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $fbc,
        ]);
    }
}
