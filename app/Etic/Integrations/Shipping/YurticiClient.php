<?php

namespace App\Etic\Integrations\Shipping;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class YurticiClient
{
    private const PRODUCTION_URL = 'https://ws.yurticikargo.com.tr/KOPSWebServices/ShippingOrderDispatcherServices';

    private const TEST_URL = 'http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices';

    public function __construct(private ShippingCredentials $credentials) {}

    /**
     * @param  array{
     *     integration_code: string,
     *     reference_number: string,
     *     receiver_name: string,
     *     receiver_address: string,
     *     receiver_phone: string,
     *     receiver_email: string,
     *     receiver_city: string,
     *     receiver_town: string,
     *     piece_count: int,
     *     weight_kg: float,
     *     description: ?string,
     *     is_cod: bool,
     *     cod_amount: float
     * }  $shipment
     */
    public function createShipment(array $shipment): YurticiShipmentResult
    {
        if (! $this->credentials->yurticiConfigured()) {
            return new YurticiShipmentResult(
                success: true,
                integrationCode: $shipment['integration_code'],
                trackingNumber: 'DEV'.$shipment['integration_code'],
                message: 'Yurtiçi API bilgileri tanımlı değil; geliştirme modu.',
            );
        }

        $config = $this->credentials->yurtici();
        $body = $this->buildCreateShipmentXml($config, $shipment);
        $response = $this->post($body);

        return $this->parseCreateShipmentResponse($response, $shipment['integration_code']);
    }

    private function endpoint(): string
    {
        return $this->credentials->yurtici()['test_mode'] ? self::TEST_URL : self::PRODUCTION_URL;
    }

    private function post(string $body): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'createShipment',
        ])
            ->timeout(30)
            ->withBody($body, 'text/xml; charset=utf-8')
            ->post($this->endpoint());

        if (! $response->successful()) {
            throw new RuntimeException('Yurtiçi Kargo API yanıt vermedi: HTTP '.$response->status());
        }

        return $response->body();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $shipment
     */
    private function buildCreateShipmentXml(array $config, array $shipment): string
    {
        $username = $this->xml($config['username']);
        $password = $this->xml($config['password']);
        $cargoKey = $this->xml(mb_substr($shipment['integration_code'], 0, 20));
        $invoiceKey = $this->xml(mb_substr($shipment['reference_number'], 0, 20));
        $receiverName = $this->xml($shipment['receiver_name']);
        $receiverAddress = $this->xml($shipment['receiver_address']);
        $receiverPhone = $this->xml($shipment['receiver_phone']);
        $receiverEmail = $this->xml($shipment['receiver_email']);
        $cityName = $this->xml($shipment['receiver_city']);
        $townName = $this->xml($shipment['receiver_town']);
        $pieceCount = (int) $shipment['piece_count'];
        $weight = number_format((float) $shipment['weight_kg'], 1, '.', '');
        $desi = number_format(max((float) $shipment['weight_kg'], (float) ($config['default_desi'] ?? 1)), 1, '.', '');
        $description = $this->xml($shipment['description'] ?? '');
        $codAmount = $shipment['is_cod'] ? number_format((float) $shipment['cod_amount'], 2, '.', '') : '';
        $codCollectionType = $shipment['is_cod'] ? '0' : '';

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://yurticikargo.com.tr/ShippingOrderDispatcherServices">
  <soapenv:Header/>
  <soapenv:Body>
    <ship:createShipment>
      <wsUserName>{$username}</wsUserName>
      <wsPassword>{$password}</wsPassword>
      <userLanguage>TR</userLanguage>
      <ShippingOrderVO>
        <cargoKey>{$cargoKey}</cargoKey>
        <invoiceKey>{$invoiceKey}</invoiceKey>
        <receiverCustName>{$receiverName}</receiverCustName>
        <receiverAddress>{$receiverAddress}</receiverAddress>
        <receiverPhone1>{$receiverPhone}</receiverPhone1>
        <emailAddress>{$receiverEmail}</emailAddress>
        <cityName>{$cityName}</cityName>
        <townName>{$townName}</townName>
        <desi>{$desi}</desi>
        <kg>{$weight}</kg>
        <cargoCount>{$pieceCount}</cargoCount>
        <description>{$description}</description>
        <ttInvoiceAmount>{$codAmount}</ttInvoiceAmount>
        <ttCollectionType>{$codCollectionType}</ttCollectionType>
      </ShippingOrderVO>
    </ship:createShipment>
  </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function parseCreateShipmentResponse(string $xml, string $integrationCode): YurticiShipmentResult
    {
        $document = new SimpleXMLElement($xml);
        $document->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $document->xpath('//soap:Body/*');

        if ($body === false || $body === []) {
            throw new RuntimeException('Yurtiçi Kargo yanıtı okunamadı.');
        }

        $result = $body[0]->ShippingOrderResultVO ?? $body[0]->children()->ShippingOrderResultVO ?? null;

        if ($result === null) {
            throw new RuntimeException('Yurtiçi Kargo yanıtı beklenen formatta değil.');
        }

        $outFlag = (string) ($result->outFlag ?? '');
        $message = (string) ($result->outResult ?? '');

        if ($outFlag !== '0') {
            $detail = $result->shippingOrderDetailVO ?? null;
            $operationMessage = $detail ? (string) ($detail->operationMessage ?? '') : '';

            throw new RuntimeException(trim($message.' '.$operationMessage) ?: 'Yurtiçi Kargo gönderisi oluşturulamadı.');
        }

        $detail = $result->shippingOrderDetailVO ?? null;
        $trackingNumber = $detail ? (string) ($detail->cargoKey ?? $integrationCode) : $integrationCode;

        return new YurticiShipmentResult(
            success: true,
            integrationCode: $integrationCode,
            trackingNumber: $trackingNumber ?: $integrationCode,
            message: $message ?: 'Yurtiçi gönderisi oluşturuldu.',
        );
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
