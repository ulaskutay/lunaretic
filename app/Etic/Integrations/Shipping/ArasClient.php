<?php

namespace App\Etic\Integrations\Shipping;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class ArasClient
{
    private const PRODUCTION_URL = 'https://customerws.araskargo.com.tr/arascargoservice.asmx';

    private const TEST_URL = 'https://customerservicestest.araskargo.com.tr/arascargoservice/arascargoservice.asmx';

    public function __construct(private ShippingCredentials $credentials) {}

    /**
     * @param  array{
     *     integration_code: string,
     *     invoice_number: string,
     *     waybill_number: string,
     *     receiver_name: string,
     *     receiver_address: string,
     *     receiver_phone: string,
     *     receiver_city: string,
     *     receiver_town: string,
     *     piece_count: int,
     *     weight_kg: float,
     *     description: ?string,
     *     is_cod: bool,
     *     cod_amount: float
     * }  $shipment
     */
    public function setOrder(array $shipment): ArasShipmentResult
    {
        $config = $this->credentials->aras();

        if (! $this->credentials->arasConfigured()) {
            return new ArasShipmentResult(
                success: true,
                integrationCode: $shipment['integration_code'],
                trackingNumber: 'DEV'.$shipment['integration_code'],
                message: 'Aras API bilgileri tanımlı değil; geliştirme modu.',
                resultCode: '0',
            );
        }

        $body = $this->buildSetOrderXml($config, $shipment);
        $response = $this->post('SetOrder', $body);

        return $this->parseSetOrderResponse($response, $shipment['integration_code']);
    }

    public function track(string $integrationCode): ?array
    {
        $config = $this->credentials->aras();

        if (! $this->credentials->arasConfigured()) {
            return null;
        }

        $body = $this->buildGetCargoInfoXml($config, $integrationCode);
        $response = $this->post('GetCargoInfo', $body);

        return $this->parseTrackingResponse($response);
    }

    private function endpoint(): string
    {
        return $this->credentials->aras()['test_mode'] ? self::TEST_URL : self::PRODUCTION_URL;
    }

    private function post(string $action, string $body): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'http://tempuri.org/'.$action,
        ])
            ->timeout(30)
            ->withBody($body, 'text/xml; charset=utf-8')
            ->post($this->endpoint());

        if (! $response->successful()) {
            throw new RuntimeException('Aras Kargo API yanıt vermedi: HTTP '.$response->status());
        }

        return $response->body();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $shipment
     */
    private function buildSetOrderXml(array $config, array $shipment): string
    {
        $username = $this->xml($config['username']);
        $password = $this->xml($config['password']);
        $integrationCode = $this->xml($shipment['integration_code']);
        $invoiceNumber = $this->xml($shipment['invoice_number']);
        $waybillNumber = $this->xml($shipment['waybill_number']);
        $receiverName = $this->xml($shipment['receiver_name']);
        $receiverAddress = $this->xml($shipment['receiver_address']);
        $receiverPhone = $this->xml($shipment['receiver_phone']);
        $receiverCity = $this->xml($shipment['receiver_city']);
        $receiverTown = $this->xml($shipment['receiver_town']);
        $pieceCount = (int) $shipment['piece_count'];
        $weight = number_format((float) $shipment['weight_kg'], 2, '.', '');
        $description = $this->xml($shipment['description'] ?? '');
        $isCod = $shipment['is_cod'] ? '1' : '0';
        $codAmount = number_format((float) $shipment['cod_amount'], 2, '.', '');
        $barcode = $this->xml($integrationCode.'01');

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SetOrder xmlns="http://tempuri.org/">
      <orderInfo>
        <Order>
          <UserName>{$username}</UserName>
          <Password>{$password}</Password>
          <TradingWaybillNumber>{$waybillNumber}</TradingWaybillNumber>
          <InvoiceNumber>{$invoiceNumber}</InvoiceNumber>
          <ReceiverName>{$receiverName}</ReceiverName>
          <ReceiverAddress>{$receiverAddress}</ReceiverAddress>
          <ReceiverPhone1>{$receiverPhone}</ReceiverPhone1>
          <ReceiverCityName>{$receiverCity}</ReceiverCityName>
          <ReceiverTownName>{$receiverTown}</ReceiverTownName>
          <PieceCount>{$pieceCount}</PieceCount>
          <Weight>{$weight}</Weight>
          <IntegrationCode>{$integrationCode}</IntegrationCode>
          <Description>{$description}</Description>
          <PayorTypeCode>1</PayorTypeCode>
          <IsWorldWide>0</IsWorldWide>
          <IsCod>{$isCod}</IsCod>
          <CodAmount>{$codAmount}</CodAmount>
          <CodCollectionType>0</CodCollectionType>
          <CodBillingType>0</CodBillingType>
          <PieceDetails>
            <PieceDetail>
              <BarcodeNumber>{$barcode}</BarcodeNumber>
            </PieceDetail>
          </PieceDetails>
        </Order>
      </orderInfo>
      <userName>{$username}</userName>
      <password>{$password}</password>
    </SetOrder>
  </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildGetCargoInfoXml(array $config, string $integrationCode): string
    {
        $username = $this->xml($config['username']);
        $password = $this->xml($config['password']);
        $customerCode = $this->xml($config['customer_code'] ?? '');
        $code = $this->xml($integrationCode);

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetCargoInfo xmlns="http://tempuri.org/">
      <username>{$username}</username>
      <password>{$password}</password>
      <customerCode>{$customerCode}</customerCode>
      <integrationCode>{$code}</integrationCode>
    </GetCargoInfo>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function parseSetOrderResponse(string $xml, string $integrationCode): ArasShipmentResult
    {
        $document = $this->loadXml($xml);
        $namespaces = $document->getNamespaces(true);
        $body = $document->children($namespaces['soap'] ?? 'http://schemas.xmlsoap.org/soap/envelope/')->Body;
        $response = $body->children('http://tempuri.org/')->SetOrderResponse->SetOrderResult->OrderResultInfo ?? null;

        if ($response === null) {
            throw new RuntimeException('Aras Kargo yanıtı okunamadı.');
        }

        $resultCode = (string) ($response->ResultCode ?? '');
        $resultMessage = (string) ($response->ResultMessage ?? 'Bilinmeyen yanıt');
        $trackingNumber = $this->firstFilled(
            (string) ($response->InvoiceKey ?? ''),
            (string) ($response->OrgReceiverCustId ?? ''),
        );

        if ($trackingNumber === '') {
            $trackingNumber = $integrationCode;
        }

        return new ArasShipmentResult(
            success: $resultCode === '0',
            integrationCode: $integrationCode,
            trackingNumber: $trackingNumber ?: null,
            message: $resultMessage,
            resultCode: $resultCode,
        );
    }

    /**
     * @return array{status: ?string, tracking_number: ?string, raw: string}|null
     */
    private function parseTrackingResponse(string $xml): ?array
    {
        $document = $this->loadXml($xml);
        $namespaces = $document->getNamespaces(true);
        $body = $document->children($namespaces['soap'] ?? 'http://schemas.xmlsoap.org/soap/envelope/')->Body;
        $result = $body->children('http://tempuri.org/')->GetCargoInfoResponse->GetCargoInfoResult ?? null;

        if ($result === null) {
            return null;
        }

        $status = $this->firstFilled(
            (string) ($result->DURUM_ACIKLAMA ?? ''),
            (string) ($result->DURUM ?? ''),
        );

        $trackingNumber = $this->firstFilled(
            (string) ($result->KARGO_TAKIP_NO ?? ''),
            (string) ($result->MOK ?? ''),
        );

        return [
            'status' => $status !== '' ? $status : null,
            'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
            'raw' => $result->asXML() ?: $xml,
        ];
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new RuntimeException('Aras Kargo yanıtı geçersiz XML.');
        }

        return $document;
    }

    private function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function firstFilled(string ...$values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return '';
    }
}
