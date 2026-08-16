<?php

namespace App\Etic\Integrations\Payments;

use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use RuntimeException;

class PaytrClient
{
    public const DIRECT_POST_URL = 'https://www.paytr.com/odeme';

    private const IFRAME_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';

    public function __construct(private PaymentCredentials $credentials) {}

    /**
     * @param  array<string, mixed>  $checkout
     * @return array{
     *     post_url: string,
     *     fields: array<string, string|int>,
     *     order_id: int,
     *     dev?: bool,
     *     success_url?: string,
     *     callback_url?: string,
     *     merchant_oid?: string,
     *     total_amount?: string
     * }
     */
    public function directPayload(Order $order, array $checkout, string $userIp): array
    {
        $config = $this->credentials->paytr();
        $merchantOid = (string) $order->reference;
        $email = (string) ($checkout['email'] ?? '');
        $paymentAmount = number_format(((int) $order->total->value) / 100, 2, '.', '');
        $paymentType = 'card';
        $installmentCount = 0;
        $currency = (string) ($config['currency'] ?? 'TL');
        $testMode = (int) ($config['test_mode'] ?? 0);
        $non3d = (int) ($config['non_3d'] ?? 0);
        $successUrl = route('paytr.success', $order, absolute: true);
        $failUrl = route('paytr.fail', $order, absolute: true);

        if (blank($config['merchant_id'])) {
            return [
                'post_url' => self::DIRECT_POST_URL,
                'fields' => [],
                'order_id' => $order->id,
                'dev' => true,
                'success_url' => $successUrl,
                'callback_url' => route('paytr.callback', absolute: true),
                'merchant_oid' => $merchantOid,
                'total_amount' => (string) $order->total->value,
            ];
        }

        $hashStr = $config['merchant_id']
            .$userIp
            .$merchantOid
            .$email
            .$paymentAmount
            .$paymentType
            .$installmentCount
            .$currency
            .$testMode
            .$non3d;

        $paytrToken = base64_encode(hash_hmac(
            'sha256',
            $hashStr.$config['merchant_salt'],
            $config['merchant_key'],
            true,
        ));

        return [
            'post_url' => self::DIRECT_POST_URL,
            'fields' => [
                'merchant_id' => $config['merchant_id'],
                'paytr_token' => $paytrToken,
                'user_ip' => $userIp,
                'merchant_oid' => $merchantOid,
                'email' => $email,
                'payment_type' => $paymentType,
                'payment_amount' => $paymentAmount,
                'installment_count' => $installmentCount,
                'currency' => $currency,
                'test_mode' => $testMode,
                'non_3d' => $non3d,
                'merchant_ok_url' => $successUrl,
                'merchant_fail_url' => $failUrl,
                'user_name' => trim(($checkout['first_name'] ?? '').' '.($checkout['last_name'] ?? '')),
                'user_address' => (string) ($checkout['line_one'] ?? ''),
                'user_phone' => (string) ($checkout['phone'] ?? ''),
                'user_basket' => $this->encodeBasketDirect($order),
                'debug_on' => (int) ($config['debug_on'] ?? 0),
                'client_lang' => (string) ($config['lang'] ?? 'tr'),
            ],
            'order_id' => $order->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $checkout
     */
    public function iframeToken(Order $order, array $checkout, string $userIp): string
    {
        $config = $this->credentials->paytr();

        if (blank($config['merchant_id'])) {
            return 'dev-'.$order->reference;
        }

        $merchantOid = (string) $order->reference;
        $email = (string) ($checkout['email'] ?? '');
        $paymentAmount = (int) $order->total->value;
        $userBasket = $this->encodeBasketIframe($order);
        $noInstallment = (int) ($config['no_installment'] ?? 0);
        $maxInstallment = (int) ($config['max_installment'] ?? 0);
        $currency = (string) ($config['currency'] ?? 'TL');
        $testMode = (int) ($config['test_mode'] ?? 0);

        $hashStr = $config['merchant_id']
            .$userIp
            .$merchantOid
            .$email
            .$paymentAmount
            .$userBasket
            .$noInstallment
            .$maxInstallment
            .$currency
            .$testMode;

        $paytrToken = base64_encode(hash_hmac(
            'sha256',
            $hashStr.$config['merchant_salt'],
            $config['merchant_key'],
            true,
        ));

        $response = Http::asForm()
            ->timeout(20)
            ->post(self::IFRAME_TOKEN_URL, [
                'merchant_id' => $config['merchant_id'],
                'user_ip' => $userIp,
                'merchant_oid' => $merchantOid,
                'email' => $email,
                'payment_amount' => $paymentAmount,
                'paytr_token' => $paytrToken,
                'user_basket' => $userBasket,
                'debug_on' => (int) ($config['debug_on'] ?? 0),
                'no_installment' => $noInstallment,
                'max_installment' => $maxInstallment,
                'user_name' => trim(($checkout['first_name'] ?? '').' '.($checkout['last_name'] ?? '')),
                'user_address' => (string) ($checkout['line_one'] ?? ''),
                'user_phone' => (string) ($checkout['phone'] ?? ''),
                'merchant_ok_url' => route('paytr.success', $order),
                'merchant_fail_url' => route('paytr.fail', $order),
                'timeout_limit' => (int) ($config['timeout_limit'] ?? 30),
                'currency' => $currency,
                'test_mode' => $testMode,
                'lang' => (string) ($config['lang'] ?? 'tr'),
            ]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            throw new RuntimeException($response->json('reason') ?: 'PayTR token alınamadı.');
        }

        return (string) $response->json('token');
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    public function verifyCallback(array $callback): bool
    {
        $config = $this->credentials->paytr();

        if (blank($config['merchant_key'])) {
            return filled($callback['merchant_oid'] ?? null);
        }

        $merchantOid = (string) ($callback['merchant_oid'] ?? '');
        $status = (string) ($callback['status'] ?? '');
        $totalAmount = (string) ($callback['total_amount'] ?? '');
        $hash = (string) ($callback['hash'] ?? '');

        if ($merchantOid === '' || $hash === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac(
            'sha256',
            $merchantOid.$config['merchant_salt'].$status.$totalAmount,
            $config['merchant_key'],
            true,
        ));

        return hash_equals($expected, $hash);
    }

    public function iframeUrl(string $token): string
    {
        return 'https://www.paytr.com/odeme/guvenli/'.$token;
    }

    private function encodeBasketIframe(Order $order): string
    {
        return base64_encode($this->basketItemsJson($order));
    }

    private function encodeBasketDirect(Order $order): string
    {
        return htmlspecialchars($this->basketItemsJson($order), ENT_QUOTES, 'UTF-8');
    }

    private function basketItemsJson(Order $order): string
    {
        $order->loadMissing('productLines');

        $items = $order->productLines->map(function ($line) {
            $unitPrice = number_format(((int) $line->unit_price->value) / 100, 2, '.', '');

            return [
                (string) $line->description,
                $unitPrice,
                (int) $line->quantity,
            ];
        })->values()->all();

        return json_encode($items, JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
