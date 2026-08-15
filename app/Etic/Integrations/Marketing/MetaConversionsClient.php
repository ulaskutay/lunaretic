<?php

namespace App\Etic\Integrations\Marketing;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaConversionsClient
{
    public const EVENTS = [
        'view_item' => 'ViewContent',
        'add_to_cart' => 'AddToCart',
        'begin_checkout' => 'InitiateCheckout',
        'add_payment_info' => 'AddPaymentInfo',
        'purchase' => 'Purchase',
        'search' => 'Search',
    ];

    public function __construct(private TrackingSettings $settings) {}

    public function enabled(): bool
    {
        $config = $this->settings->resolved();

        return (bool) ($config['meta_capi_enabled'] ?? false)
            && filled($config['meta_pixel_id'] ?? null)
            && filled($config['meta_capi_token'] ?? null);
    }

    public function send(TrackingEvent $event): bool
    {
        if (! $this->enabled() || ! isset(self::EVENTS[$event->name])) {
            return false;
        }

        $config = $this->settings->resolved();
        $pixelId = $config['meta_pixel_id'];
        $version = (string) config('etic.tracking.meta_graph_version', 'v21.0');
        $url = sprintf('https://graph.facebook.com/%s/%s/events', $version, $pixelId);

        $body = [
            'data' => [$this->payload($event)],
            'access_token' => $config['meta_capi_token'],
        ];

        if (filled($config['meta_test_event_code'] ?? null)) {
            $body['test_event_code'] = $config['meta_test_event_code'];
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(5)
                ->post($url, $body);

            if ($response->failed()) {
                Log::warning('Meta CAPI rejected the event', [
                    'event' => $event->name,
                    'status' => $response->status(),
                    'error' => $response->json('error.message') ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Meta CAPI request failed', [
                'event' => $event->name,
                'message' => $e instanceof RequestException ? $e->getMessage() : $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<string, mixed> */
    public function payload(TrackingEvent $event): array
    {
        $custom = [];

        if (isset($event->payload['value'])) {
            $custom['value'] = (float) $event->payload['value'];
            $custom['currency'] = $event->payload['currency'] ?? 'TRY';
        }

        if (isset($event->payload['item_id'])) {
            $custom['content_ids'] = [(string) $event->payload['item_id']];
            $custom['content_type'] = 'product';
            $custom['contents'] = [[
                'id' => (string) $event->payload['item_id'],
                'quantity' => (int) ($event->payload['quantity'] ?? 1),
            ]];
        }

        if (isset($event->payload['items']) && is_array($event->payload['items'])) {
            $custom['content_ids'] = array_values(array_filter(array_map(
                fn (mixed $item) => is_array($item) ? (string) ($item['item_id'] ?? '') : '',
                $event->payload['items'],
            )));
            $custom['content_type'] = 'product';
            $custom['contents'] = array_values(array_map(function (array $item) {
                $row = [
                    'id' => (string) ($item['item_id'] ?? ''),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ];

                if (isset($item['price'])) {
                    $row['item_price'] = (float) $item['price'];
                }

                return $row;
            }, $event->payload['items']));
            $custom['num_items'] = array_sum(array_map(
                fn (array $item) => (int) ($item['quantity'] ?? 1),
                $event->payload['items'],
            ));
        }

        if (isset($event->payload['search_term'])) {
            $custom['search_string'] = (string) $event->payload['search_term'];
        }

        if (isset($event->payload['transaction_id'])) {
            $custom['order_id'] = (string) $event->payload['transaction_id'];
        }

        return array_filter([
            'event_name' => self::EVENTS[$event->name],
            'event_time' => time(),
            'event_id' => $event->eventId,
            'event_source_url' => $event->serverContext['event_source_url'] ?? null,
            'action_source' => 'website',
            'user_data' => $this->userData($event),
            'custom_data' => $custom === [] ? null : $custom,
        ], fn (mixed $value) => $value !== null && $value !== []);
    }

    /** @return array<string, mixed> */
    public function userData(TrackingEvent $event): array
    {
        $user = is_array($event->payload['user'] ?? null) ? $event->payload['user'] : [];
        $context = $event->serverContext;

        return array_filter([
            'em' => $this->hashedList($this->normalizeEmail($user['email'] ?? null)),
            'ph' => $this->hashedList($this->normalizePhone($user['phone'] ?? null)),
            'fn' => $this->hashedList($this->normalizeName($user['first_name'] ?? null)),
            'ln' => $this->hashedList($this->normalizeName($user['last_name'] ?? null)),
            'ct' => $this->hashedList($this->normalizeName($user['city'] ?? null)),
            'st' => $this->hashedList($this->normalizeName($user['state'] ?? null)),
            'zp' => $this->hashedList($this->normalizeName($user['zip'] ?? null)),
            'country' => $this->hashedList($this->normalizeName($user['country'] ?? 'tr')),
            'client_ip_address' => $context['client_ip_address'] ?? null,
            'client_user_agent' => $context['client_user_agent'] ?? null,
            'fbp' => $context['fbp'] ?? null,
            'fbc' => $context['fbc'] ?? null,
        ], fn (mixed $value) => $value !== null && $value !== []);
    }

    public static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /** @return list<string>|null */
    private function hashedList(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return [self::hash($value)];
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = strtolower(trim((string) $value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        return $digits;
    }

    private function normalizeName(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
