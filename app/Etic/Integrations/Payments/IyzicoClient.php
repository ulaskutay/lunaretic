<?php

namespace App\Etic\Integrations\Payments;

use Illuminate\Support\Facades\Http;

class IyzicoClient
{
    public function __construct(private PaymentCredentials $credentials) {}

    public function confirm(?string $token, int $orderId): bool
    {
        $iyzico = $this->credentials->iyzico();

        if (blank($iyzico['api_key'])) {
            return filled($token);
        }

        if (blank($token)) {
            return false;
        }

        $response = Http::baseUrl($iyzico['base_url'])
            ->asJson()
            ->post('/payment/iyzipos/checkoutform/auth/ecom/detail', [
                'locale' => 'tr',
                'conversationId' => (string) $orderId,
                'token' => $token,
            ]);

        return $response->successful() && ($response->json('status') === 'success');
    }

    public function refund(string $paymentId, int $amountMinor): bool
    {
        if (blank($this->credentials->iyzico()['api_key'])) {
            return true;
        }

        $response = Http::baseUrl($this->credentials->iyzico()['base_url'])
            ->asJson()
            ->post('/payment/refund', [
                'paymentTransactionId' => $paymentId,
                'price' => number_format($amountMinor / 100, 2, '.', ''),
            ]);

        return $response->successful();
    }

    public function capture(string $paymentId, int $amountMinor): bool
    {
        if (blank($this->credentials->iyzico()['api_key'])) {
            return true;
        }

        $response = Http::baseUrl($this->credentials->iyzico()['base_url'])
            ->asJson()
            ->post('/payment/iyzipos/checkoutform/auth/ecom', [
                'paymentId' => $paymentId,
                'paidPrice' => number_format($amountMinor / 100, 2, '.', ''),
            ]);

        return $response->successful();
    }
}
