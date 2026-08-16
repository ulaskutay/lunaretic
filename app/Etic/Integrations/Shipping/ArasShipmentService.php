<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Orders\OrderStatusScenario;
use Lunar\Models\Order;
use RuntimeException;

class ArasShipmentService
{
    public function __construct(
        private ArasClient $client,
        private ShippingCredentials $credentials,
    ) {}

    public function createFromOrder(Order $order): ArasShipmentResult
    {
        $order->loadMissing('shippingAddress');

        $address = $order->shippingAddress;

        if (! $address) {
            throw new RuntimeException('Siparişin teslimat adresi bulunamadı.');
        }

        $integrationCode = $this->integrationCode($order);

        if (filled(data_get($order->meta, 'aras.integration_code'))) {
            throw new RuntimeException('Bu sipariş için Aras gönderisi zaten oluşturulmuş.');
        }

        $config = $this->credentials->aras();
        $isCod = $order->status === OrderStatusScenario::PAYMENT_OFFLINE;

        $result = $this->client->setOrder([
            'integration_code' => $integrationCode,
            'invoice_number' => (string) $order->reference,
            'waybill_number' => (string) $order->reference,
            'receiver_name' => trim($address->first_name.' '.$address->last_name),
            'receiver_address' => trim(collect([$address->line_one, $address->line_two])->filter()->implode(', ')),
            'receiver_phone' => (string) ($address->contact_phone ?? data_get($order->meta, 'phone', '')),
            'receiver_city' => (string) $address->city,
            'receiver_town' => (string) ($address->state ?: $address->city),
            'piece_count' => $config['default_piece_count'],
            'weight_kg' => $config['default_weight_kg'],
            'description' => 'Sipariş #'.$order->reference,
            'is_cod' => $isCod,
            'cod_amount' => $isCod ? ((int) $order->total->value) / 100 : 0,
        ]);

        if (! $result->success) {
            throw new RuntimeException($result->message ?: 'Aras Kargo gönderisi oluşturulamadı.');
        }

        $meta = array_merge((array) $order->meta, [
            'aras' => [
                'integration_code' => $result->integrationCode,
                'tracking_number' => $result->trackingNumber,
                'tracking_url' => $result->trackingUrl(),
                'result_code' => $result->resultCode,
                'result_message' => $result->message,
                'shipped_at' => now()->toIso8601String(),
            ],
        ]);

        $updates = ['meta' => $meta];

        if ($status = OrderDispatchStatus::advance($order, $config['mark_dispatched'])) {
            $updates['status'] = $status;
        }

        $order->update($updates);

        return $result;
    }

    public function refreshTracking(Order $order): ?array
    {
        $integrationCode = data_get($order->meta, 'aras.integration_code');

        if (! filled($integrationCode)) {
            return null;
        }

        $tracking = $this->client->track((string) $integrationCode);

        if ($tracking === null) {
            return null;
        }

        $meta = (array) $order->meta;
        $aras = (array) ($meta['aras'] ?? []);
        $aras['last_status'] = $tracking['status'] ?? null;

        if (filled($tracking['tracking_number'] ?? null)) {
            $aras['tracking_number'] = $tracking['tracking_number'];
            $aras['tracking_url'] = ShipmentTracking::trackingUrl('aras', (string) $tracking['tracking_number']);
        }

        $meta['aras'] = $aras;
        $order->update(['meta' => $meta]);

        return $tracking;
    }

    public function integrationCode(Order $order): string
    {
        return str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
    }

    public static function trackingFromMeta(?array $meta): ?array
    {
        return ShipmentTracking::fromMeta($meta);
    }
}
