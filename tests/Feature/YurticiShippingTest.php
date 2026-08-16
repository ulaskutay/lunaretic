<?php

use App\Etic\Integrations\Shipping\ShipmentTracking;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Integrations\Shipping\YurticiClient;
use App\Etic\Integrations\Shipping\YurticiShipmentService;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\Support\CommerceBootstrap;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('creates a yurtici shipment and stores tracking on the order', function () {
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

    app(ShippingCredentials::class)->saveYurtici([
        'enabled' => true,
        'username' => 'YKTEST',
        'password' => 'YK',
        'test_mode' => true,
        'default_weight_kg' => 1,
        'default_desi' => 1,
        'default_piece_count' => 1,
        'mark_dispatched' => true,
    ]);

    Http::fake([
        '*' => Http::response(<<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Body>
    <createShipmentResponse xmlns="http://yurticikargo.com.tr/ShippingOrderDispatcherServices">
      <ShippingOrderResultVO>
        <outFlag>0</outFlag>
        <outResult>Success</outResult>
        <jobId>12345</jobId>
        <shippingOrderDetailVO>
          <cargoKey>00000001</cargoKey>
          <invoiceKey>ORD-001</invoiceKey>
          <operationCode>0</operationCode>
          <operationMessage>İşlem başarılı</operationMessage>
        </shippingOrderDetailVO>
      </ShippingOrderResultVO>
    </createShipmentResponse>
  </soapenv:Body>
</soapenv:Envelope>
XML),
    ]);

    $result = app(YurticiShipmentService::class)->createFromOrder($order);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('00000001');

    $order->refresh();

    expect(data_get($order->meta, 'yurtici.tracking_number'))->toBe('00000001')
        ->and($order->status)->toBe(OrderStatusScenario::DISPATCHED)
        ->and(ShipmentTracking::fromMeta((array) $order->meta)['tracking_url'])
        ->toContain('yurticikargo.com');
});

it('returns dev tracking when yurtici credentials are missing', function () {
    app(ShippingCredentials::class)->saveYurtici([
        'enabled' => false,
        'username' => null,
        'password' => null,
    ]);

    $client = app(YurticiClient::class);

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
