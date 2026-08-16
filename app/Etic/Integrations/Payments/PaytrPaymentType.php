<?php

namespace App\Etic\Integrations\Payments;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Order;
use Lunar\PaymentTypes\AbstractPayment;

class PaytrPaymentType extends AbstractPayment
{
    public function __construct(private PaytrClient $client) {}

    public function authorize(): ?PaymentAuthorize
    {
        if (! $this->order) {
            $merchantOid = $this->data['merchant_oid'] ?? null;
            $this->order = $merchantOid
                ? Order::query()->where('reference', $merchantOid)->first()
                : null;
        }

        if (! $this->order) {
            $response = new PaymentAuthorize(success: false, message: 'Sipariş bulunamadı.', paymentType: 'paytr');
            PaymentAttemptEvent::dispatch($response);

            return $response;
        }

        $verified = $this->client->verifyCallback($this->data);
        $success = $verified && ($this->data['status'] ?? null) === 'success';

        if ($success && $this->order->status !== ($this->config['authorized'] ?? 'payment-received')) {
            $this->order->update([
                'status' => $this->config['authorized'] ?? 'payment-received',
                'placed_at' => $this->order->placed_at ?? now(),
                'meta' => array_merge((array) $this->order->meta, [
                    'payment' => 'paytr',
                    'paytr_merchant_oid' => $this->data['merchant_oid'] ?? null,
                    'paytr_total_amount' => $this->data['total_amount'] ?? null,
                ]),
            ]);
        }

        $response = new PaymentAuthorize(
            success: $success,
            message: $success ? null : 'PayTR ödemesi doğrulanamadı.',
            orderId: $this->order->id,
            paymentType: 'paytr',
        );

        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        return new PaymentRefund(success: false);
    }

    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(success: false);
    }
}
