<?php

namespace App\Etic\Integrations\Payments;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\PaymentTypes\AbstractPayment;

class IyzicoPaymentType extends AbstractPayment
{
    public function __construct(private IyzicoClient $client) {}

    public function authorize(): ?PaymentAuthorize
    {
        if (! $this->order) {
            $this->order = $this->cart?->draftOrder()->first() ?? $this->cart?->createOrder();
        }

        if (! $this->order) {
            $response = new PaymentAuthorize(success: false, message: 'Sipariş oluşturulamadı.', paymentType: 'iyzico');
            PaymentAttemptEvent::dispatch($response);

            return $response;
        }

        $token = $this->data['token'] ?? $this->data['payment_token'] ?? null;
        $forced = ($this->data['status'] ?? null) === 'success';

        $authorized = $forced || $this->client->confirm($token, $this->order->id);

        if ($authorized) {
            $this->order->update([
                'status' => $this->config['authorized'] ?? 'payment-received',
                'placed_at' => now(),
                'meta' => array_merge((array) $this->order->meta, [
                    'payment' => 'iyzico',
                    'iyzico_token' => $token,
                ]),
            ]);
        }

        $response = new PaymentAuthorize(
            success: $authorized,
            message: $authorized ? null : 'iyzico ödemesi doğrulanamadı.',
            orderId: $this->order->id,
            paymentType: 'iyzico',
        );

        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        return new PaymentRefund(
            success: $this->client->refund((string) $transaction->reference, $amount),
        );
    }

    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(
            success: $this->client->capture((string) $transaction->reference, (int) $amount),
        );
    }
}
