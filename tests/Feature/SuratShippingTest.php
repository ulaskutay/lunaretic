<?php

use App\Etic\Integrations\Shipping\ShipmentTracking;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Integrations\Shipping\SuratShipmentService;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\Support\CommerceBootstrap;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('creates a surat shipment and stores tracking on the order', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.place'), [
        'first_name' => 'Ayşe',
        'last_name' => 'Demir',
        'email' => 'ayse@example.com',
        'phone' => '5552223344',
        'line_one' => 'Bağdat Cad. 10',
        'city' => 'İstanbul',
        'state' => 'Kadıköy',
        'postcode' => '34710',
        'payment' => 'cash-in-hand',
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    app(ShippingCredentials::class)->saveSurat([
        'enabled' => true,
        'username' => '1038106246',
        'password' => '123456',
        'web_password' => '123456.Ff',
        'test_mode' => true,
        'default_weight_kg' => 1,
        'default_piece_count' => 1,
        'mark_dispatched' => true,
    ]);

    Http::fake([
        '*' => Http::response(<<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResponse xmlns="http://tempuri.org/">
      <GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult>
        <isError>false</isError>
        <Message>Gönderi kaydedildi</Message>
        <Barcode>12345678901234</Barcode>
      </GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult>
    </GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResponse>
  </soap:Body>
</soap:Envelope>
XML),
    ]);

    $result = app(SuratShipmentService::class)->createFromOrder($order);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('12345678901234');

    $order->refresh();

    expect(data_get($order->meta, 'surat.tracking_number'))->toBe('12345678901234')
        ->and($order->status)->toBe(OrderStatusScenario::DISPATCHED)
        ->and(ShipmentTracking::fromMeta((array) $order->meta)['tracking_url'])
        ->toContain('suratkargo.com.tr');
});

it('returns dev tracking when surat credentials are missing', function () {
    app(ShippingCredentials::class)->saveSurat([
        'enabled' => false,
        'username' => null,
        'password' => null,
    ]);

    $client = app(\App\Etic\Integrations\Shipping\SuratClient::class);

    $result = $client->createShipment([
        'integration_code' => '00000042',
        'reference_number' => 'ORD-42',
        'receiver_name' => 'Ayşe Demir',
        'receiver_address' => 'Test Cad. 1',
        'receiver_phone' => '5552223344',
        'receiver_email' => 'ayse@example.com',
        'receiver_city' => 'İstanbul',
        'receiver_town' => 'Kadıköy',
        'piece_count' => 1,
        'weight_kg' => 1,
        'description' => 'Test',
        'is_cod' => false,
        'cod_amount' => 0,
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('DEV00000042');
});
