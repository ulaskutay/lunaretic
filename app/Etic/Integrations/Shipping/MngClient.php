<?php

namespace App\Etic\Integrations\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MngClient
{
    private const PRODUCTION_URL = 'https://api.mngkargo.com.tr';

    private const TEST_URL = 'https://testapi.mngkargo.com.tr';

    private const TOKEN_PATH = '/mngapi/api/token';

    private const CREATE_RECIPIENT_PATH = '/mngapi/api/pluscmdapi/createRecipient';

    private const CREATE_ORDER_PATH = '/mngapi/api/standardcmdapi/createOrder';

    private const CREATE_BARCODE_PATH = '/mngapi/api/barcodecmdapi/createbarcode';

    public function __construct(private ShippingCredentials $credentials) {}

    /**
     * @param  array{
     *     integration_code: string,
     *     reference_number: string,
     *     receiver_name: string,
     *     receiver_address: string,
     *     receiver_city: string,
     *     receiver_town: string,
     *     receiver_phone: string,
     *     receiver_email: string,
     *     city_code: int,
     *     district_code: int,
     *     piece_count: int,
     *     weight_kg: float,
     *     description: ?string,
     *     is_cod: bool,
     *     cod_amount: float
     * }  $shipment
     */
    public function createShipment(array $shipment): MngShipmentResult
    {
        if (! $this->credentials->mngConfigured()) {
            return new MngShipmentResult(
                success: true,
                integrationCode: $shipment['integration_code'],
                trackingNumber: 'DEV'.$shipment['integration_code'],
                message: 'MNG API bilgileri tanımlı değil; geliştirme modu.',
            );
        }

        $referenceId = mb_strtoupper($shipment['integration_code'], 'UTF-8');
        $recipient = $this->buildRecipient($shipment);

        $this->post(self::CREATE_RECIPIENT_PATH, $recipient, ignoreErrors: true);

        $orderResponse = $this->post(self::CREATE_ORDER_PATH, [
            'order' => $this->buildOrder($shipment, $referenceId),
            'recipient' => $recipient,
        ]);

        if (! $this->isSuccessfulResponse($orderResponse) && ! $this->isDuplicateOrder($orderResponse)) {
            throw new RuntimeException($this->extractErrorMessage($orderResponse) ?: 'MNG createOrder başarısız.');
        }

        $barcodeResponse = $this->post(self::CREATE_BARCODE_PATH, [
            'referenceId' => $referenceId,
            'billOfLandingId' => $shipment['reference_number'],
            'isCOD' => $shipment['is_cod'] ? 1 : 0,
            'codAmount' => $shipment['is_cod'] ? $shipment['cod_amount'] : 0,
            'printReferenceBarcodeOnError' => 0,
            'message' => '',
            'additionalContent1' => '',
            'additionalContent2' => '',
            'additionalContent3' => '',
            'additionalContent4' => '',
            'orderPieceList' => $this->buildPieceList($referenceId, $shipment),
            'packagingType' => 3,
        ], ignoreErrors: true);

        $trackingNumber = $this->extractTrackingNumber($barcodeResponse, $orderResponse, $referenceId);

        return new MngShipmentResult(
            success: true,
            integrationCode: $shipment['integration_code'],
            trackingNumber: $trackingNumber,
            message: 'MNG gönderisi oluşturuldu.',
        );
    }

    /**
     * @param  array<string, mixed>  $shipment
     * @return array<string, mixed>
     */
    private function buildRecipient(array $shipment): array
    {
        return [
            'refCustomerId' => '',
            'cityCode' => (int) $shipment['city_code'],
            'cityName' => $shipment['receiver_city'],
            'districtCode' => (int) $shipment['district_code'],
            'districtName' => $shipment['receiver_town'],
            'address' => $shipment['receiver_address'],
            'bussinessPhoneNumber' => '',
            'email' => $shipment['receiver_email'],
            'taxOffice' => 'SAHIS',
            'taxNumber' => '11111111110',
            'fullName' => $shipment['receiver_name'],
            'homePhoneNumber' => '',
            'mobilePhoneNumber' => $this->normalizePhone($shipment['receiver_phone']),
        ];
    }

    /**
     * @param  array<string, mixed>  $shipment
     * @return array<string, mixed>
     */
    private function buildOrder(array $shipment, string $referenceId): array
    {
        $description = $shipment['description'] ?? 'Sipariş';

        return [
            'referenceId' => $referenceId,
            'barcode' => $referenceId,
            'billOfLandingId' => $shipment['reference_number'],
            'isCOD' => $shipment['is_cod'] ? 1 : 0,
            'codAmount' => $shipment['is_cod'] ? $shipment['cod_amount'] : 0,
            'shipmentServiceType' => 1,
            'packagingType' => 3,
            'content' => $description,
            'smsPreference1' => 0,
            'smsPreference2' => 0,
            'smsPreference3' => 0,
            'paymentType' => 1,
            'deliveryType' => 1,
            'description' => $description,
            'marketPlaceShortCode' => '',
            'marketPlaceSaleCode' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $shipment
     * @return list<array<string, mixed>>
     */
    private function buildPieceList(string $referenceId, array $shipment): array
    {
        $pieces = [];

        for ($index = 1; $index <= (int) $shipment['piece_count']; $index++) {
            $pieces[] = [
                'barcode' => $referenceId.'_PARCA'.$index,
                'desi' => max(1, (int) round((float) $shipment['weight_kg'])),
                'kg' => max(1, (int) round((float) $shipment['weight_kg'])),
                'content' => $shipment['description'] ?? '',
            ];
        }

        return $pieces;
    }

    /**
     * @return array{status: int, body: mixed}
     */
    private function post(string $path, array $payload, bool $ignoreErrors = false): array
    {
        $config = $this->credentials->mng();
        $response = Http::withHeaders([
            'X-IBM-Client-Id' => (string) $config['client_id'],
            'X-IBM-Client-Secret' => (string) $config['client_secret'],
            'Authorization' => 'Bearer '.$this->token(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post($this->baseUrl().$path, $payload);

        $body = $response->json();

        if (! $ignoreErrors && ! $response->successful()) {
            $message = $this->extractErrorMessage(['status' => $response->status(), 'body' => $body]);

            throw new RuntimeException($message ?: 'MNG Kargo API yanıt vermedi: HTTP '.$response->status());
        }

        return [
            'status' => $response->status(),
            'body' => $body,
        ];
    }

    private function token(): string
    {
        $config = $this->credentials->mng();
        $cacheKey = 'etic.mng.jwt.'.md5(($config['test_mode'] ? 'test' : 'prod').'|'.($config['client_id'] ?? '').'|'.($config['customer_number'] ?? ''));

        return Cache::remember($cacheKey, now()->addHours(7), function () use ($config) {
            $response = Http::withHeaders([
                'X-IBM-Client-Id' => (string) $config['client_id'],
                'X-IBM-Client-Secret' => (string) $config['client_secret'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(20)
                ->post($this->baseUrl().self::TOKEN_PATH, [
                    'customerNumber' => (string) $config['customer_number'],
                    'password' => (string) $config['password'],
                    'identityType' => 1,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('MNG kimlik doğrulaması başarısız: HTTP '.$response->status());
            }

            $jwt = $response->json('jwt');

            if (! filled($jwt)) {
                throw new RuntimeException('MNG JWT alınamadı.');
            }

            return (string) $jwt;
        });
    }

    private function baseUrl(): string
    {
        return $this->credentials->mng()['test_mode'] ? self::TEST_URL : self::PRODUCTION_URL;
    }

    /**
     * @param  array{status: int, body: mixed}  $response
     */
    private function isSuccessfulResponse(array $response): bool
    {
        return $response['status'] >= 200 && $response['status'] < 300;
    }

    /**
     * @param  array{status: int, body: mixed}  $response
     */
    private function isDuplicateOrder(array $response): bool
    {
        $body = $this->unwrapBody($response['body']);

        if (! is_array($body)) {
            return false;
        }

        $error = is_array($body['error'] ?? null) ? $body['error'] : null;

        if ($error === null) {
            return false;
        }

        if (($error['Code'] ?? $error['code'] ?? null) === '3002') {
            return true;
        }

        $description = (string) ($error['Description'] ?? $error['description'] ?? '');

        return str_contains($description, 'ZATEN VAR');
    }

    /**
     * @param  array{status: int, body: mixed}  $orderResponse
     * @param  array{status: int, body: mixed}  $barcodeResponse
     */
    private function extractTrackingNumber(array $barcodeResponse, array $orderResponse, string $referenceId): string
    {
        $barcodeBody = $this->unwrapBody($barcodeResponse['body']);

        if (is_array($barcodeBody)) {
            if (filled($barcodeBody['shipmentId'] ?? null)) {
                return (string) $barcodeBody['shipmentId'];
            }

            $barcodes = $barcodeBody['barcodes'] ?? null;

            if (is_array($barcodes) && isset($barcodes[0]['barcode'])) {
                return (string) $barcodes[0]['barcode'];
            }
        }

        $orderBody = $this->unwrapBody($orderResponse['body']);

        if (is_array($orderBody) && filled($orderBody['referenceId'] ?? null)) {
            return (string) $orderBody['referenceId'];
        }

        return $referenceId;
    }

    /**
     * @param  array{status: int, body: mixed}  $response
     */
    private function extractErrorMessage(array $response): ?string
    {
        $body = $this->unwrapBody($response['body']);

        if (! is_array($body)) {
            return null;
        }

        if (is_array($body['error'] ?? null)) {
            $error = $body['error'];

            return (string) ($error['Description'] ?? $error['description'] ?? $error['message'] ?? $error['Message'] ?? null);
        }

        return (string) ($body['detail'] ?? $body['title'] ?? null) ?: null;
    }

    private function unwrapBody(mixed $body): mixed
    {
        if (! is_array($body)) {
            return $body;
        }

        if (array_is_list($body)) {
            return $body[0] ?? null;
        }

        return $body;
    }

    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone) ?? $phone;

        if (str_starts_with($cleaned, '90') && strlen($cleaned) === 12) {
            $cleaned = substr($cleaned, 2);
        }

        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 11) {
            $cleaned = substr($cleaned, 1);
        }

        return $cleaned;
    }
}
