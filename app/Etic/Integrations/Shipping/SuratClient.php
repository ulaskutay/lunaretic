<?php

namespace App\Etic\Integrations\Shipping;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class SuratClient
{
    private const PRODUCTION_URL = 'https://webservices.suratkargo.com.tr/services.asmx';

    private const TEST_URL = 'https://prova.suratkargo.com.tr/services.asmx';

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
     *     receiver_email: ?string,
     *     piece_count: int,
     *     weight_kg: float,
     *     description: ?string,
     *     is_cod: bool,
     *     cod_amount: float
     * }  $shipment
     */
    public function createShipment(array $shipment): SuratShipmentResult
    {
        if (! $this->credentials->suratConfigured()) {
            return new SuratShipmentResult(
                success: true,
                integrationCode: $shipment['integration_code'],
                trackingNumber: 'DEV'.$shipment['integration_code'],
                message: 'Sürat API bilgileri tanımlı değil; geliştirme modu.',
            );
        }

        $body = $this->buildCreateShipmentXml($this->credentials->surat(), $shipment);
        $response = $this->post('GonderiyiKargoyaGonderYeniSiparisBarkodOlustur', $body);

        return $this->parseCreateShipmentResponse($response, $shipment['integration_code']);
    }

    public function track(string $integrationCode): ?array
    {
        if (! $this->credentials->suratTrackingConfigured()) {
            return null;
        }

        $body = $this->buildTrackingXml($this->credentials->surat(), $integrationCode);
        $response = $this->post('KargoTakipHareketDetayliV2', $body);

        return $this->parseTrackingResponse($response);
    }

    private function endpoint(): string
    {
        return $this->credentials->surat()['test_mode'] ? self::TEST_URL : self::PRODUCTION_URL;
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
            throw new RuntimeException('Sürat Kargo API yanıt vermedi: HTTP '.$response->status());
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
        $isCod = $shipment['is_cod'];
        $codType = $isCod ? 1 : 0;
        $codAmount = $isCod ? number_format((float) $shipment['cod_amount'], 2, '.', '') : '';
        $weight = number_format((float) $shipment['weight_kg'], 2, '.', '');

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GonderiyiKargoyaGonderYeniSiparisBarkodOlustur xmlns="http://tempuri.org/">
      <KullaniciAdi>{$username}</KullaniciAdi>
      <Sifre>{$password}</Sifre>
      <Gonderi>
        <KisiKurum>{$this->xml($shipment['receiver_name'])}</KisiKurum>
        <SahisBirim></SahisBirim>
        <AliciAdresi>{$this->xml($shipment['receiver_address'])}</AliciAdresi>
        <Il>{$this->xml($shipment['receiver_city'])}</Il>
        <Ilce>{$this->xml($shipment['receiver_town'])}</Ilce>
        <TelefonEv></TelefonEv>
        <TelefonIs></TelefonIs>
        <TelefonCep>{$this->xml($shipment['receiver_phone'])}</TelefonCep>
        <Email>{$this->xml($shipment['receiver_email'] ?? '')}</Email>
        <AliciKodu></AliciKodu>
        <KargoTuru>3</KargoTuru>
        <OdemeTipi>1</OdemeTipi>
        <IrsaliyeSeriNo></IrsaliyeSeriNo>
        <IrsaliyeSiraNo></IrsaliyeSiraNo>
        <ReferansNo>{$this->xml($shipment['reference_number'])}</ReferansNo>
        <OzelKargoTakipNo>{$this->xml($shipment['integration_code'])}</OzelKargoTakipNo>
        <Adet>{$shipment['piece_count']}</Adet>
        <BirimDesi></BirimDesi>
        <BirimKg>{$weight}</BirimKg>
        <KargoIcerigi>{$this->xml($shipment['description'] ?? '')}</KargoIcerigi>
        <KapidanOdemeTahsilatTipi>{$codType}</KapidanOdemeTahsilatTipi>
        <KapidanOdemeTutari>{$codAmount}</KapidanOdemeTutari>
        <EkHizmetler></EkHizmetler>
        <TasimaSekli>0</TasimaSekli>
        <TeslimSekli>1</TeslimSekli>
        <SevkAdresi></SevkAdresi>
        <GonderiSekli>0</GonderiSekli>
        <TeslimSubeKodu></TeslimSubeKodu>
        <Pazaryerimi>0</Pazaryerimi>
        <EntegrasyonFirmasi>Etic Commerce</EntegrasyonFirmasi>
        <Iademi>false</Iademi>
        <AlimSaati></AlimSaati>
      </Gonderi>
    </GonderiyiKargoyaGonderYeniSiparisBarkodOlustur>
  </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildTrackingXml(array $config, string $integrationCode): string
    {
        $customerCode = $this->xml($config['username']);
        $webPassword = $this->xml($config['web_password']);
        $code = $this->xml($integrationCode);

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <KargoTakipHareketDetayliV2 xmlns="http://tempuri.org/">
      <CariKodu>{$customerCode}</CariKodu>
      <Sifre>{$webPassword}</Sifre>
      <WebSiparisKodu>{$code}</WebSiparisKodu>
    </KargoTakipHareketDetayliV2>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function parseCreateShipmentResponse(string $xml, string $integrationCode): SuratShipmentResult
    {
        $document = $this->loadXml($xml);
        $namespaces = $document->getNamespaces(true);
        $body = $document->children($namespaces['soap'] ?? 'http://schemas.xmlsoap.org/soap/envelope/')->Body;
        $result = $body->children('http://tempuri.org/')
            ->GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResponse
            ->GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult ?? null;

        if ($result === null) {
            throw new RuntimeException('Sürat Kargo yanıtı okunamadı.');
        }

        $isError = filter_var((string) ($result->isError ?? 'true'), FILTER_VALIDATE_BOOLEAN);
        $message = (string) ($result->Message ?? 'Bilinmeyen yanıt');
        $barcode = trim((string) ($result->Barcode ?? ''));

        if ($barcode === '') {
            $barcode = $integrationCode;
        }

        return new SuratShipmentResult(
            success: ! $isError,
            integrationCode: $integrationCode,
            trackingNumber: $barcode ?: null,
            message: $message,
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
        $result = (string) ($body->children('http://tempuri.org/')->KargoTakipHareketDetayliV2Response->KargoTakipHareketDetayliV2Result ?? '');

        if ($result === '') {
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $result))));
        $lastLine = $lines !== [] ? $lines[array_key_last($lines)] : null;
        $status = $lastLine ? trim(explode('|', $lastLine)[0]) : null;

        return [
            'status' => $status,
            'tracking_number' => null,
            'raw' => $result,
        ];
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new RuntimeException('Sürat Kargo yanıtı geçersiz XML.');
        }

        return $document;
    }

    private function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
