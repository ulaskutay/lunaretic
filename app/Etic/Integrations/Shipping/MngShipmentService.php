<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Orders\OrderStatusScenario;
use Lunar\Models\Order;
use RuntimeException;

class MngShipmentService
{
    public function __construct(
        private MngClient $client,
        private ShippingCredentials $credentials,
    ) {}

    public function createFromOrder(Order $order): MngShipmentResult
    {
        $order->loadMissing('shippingAddress');

        $address = $order->shippingAddress;

        if (! $address) {
            throw new RuntimeException('Siparişin teslimat adresi bulunamadı.');
        }

        $integrationCode = $this->integrationCode($order);

        if (filled(data_get($order->meta, 'mng.integration_code'))) {
            throw new RuntimeException('Bu sipariş için MNG gönderisi zaten oluşturulmuş.');
        }

        $config = $this->credentials->mng();
        $isCod = $order->status === OrderStatusScenario::PAYMENT_OFFLINE;
        $email = (string) ($address->contact_email ?? data_get($order->meta, 'email', ''));

        $result = $this->client->createShipment([
            'integration_code' => $integrationCode,
            'reference_number' => (string) $order->reference,
            'receiver_name' => trim($address->first_name.' '.$address->last_name),
            'receiver_address' => trim(collect([$address->line_one, $address->line_two])->filter()->implode(', ')),
            'receiver_phone' => (string) ($address->contact_phone ?? data_get($order->meta, 'phone', '')),
            'receiver_email' => filled($email) ? $email : 'noreply@example.com',
            'receiver_city' => (string) $address->city,
            'receiver_town' => (string) ($address->state ?: $address->city),
            'city_code' => (int) $config['default_city_code'],
            'district_code' => (int) $config['default_district_code'],
            'piece_count' => $config['default_piece_count'],
            'weight_kg' => $config['default_weight_kg'],
            'description' => 'Sipariş #'.$order->reference,
            'is_cod' => $isCod,
            'cod_amount' => $isCod ? ((int) $order->total->value) / 100 : 0,
        ]);

        if (! $result->success) {
            throw new RuntimeException($result->message ?: 'MNG Kargo gönderisi oluşturulamadı.');
        }

        $meta = array_merge((array) $order->meta, [
            'mng' => [
                'integration_code' => $result->integrationCode,
                'tracking_number' => $result->trackingNumber,
                'tracking_url' => $result->trackingUrl(),
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

    public function integrationCode(Order $order): string
    {
        return str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
    }
}
