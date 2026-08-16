<?php

use App\Etic\Integrations\Shipping\ArasShipmentService;
use App\Etic\Integrations\Shipping\ShipmentTracking;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\Support\CommerceBootstrap;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('creates an aras shipment and stores tracking on the order', function () {
    $variant = ProductVariant::query()->first();

    $this->post(route('cart.add'), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.place'), [
        'first_name' => 'Ali',
        'last_name' => 'Yılmaz',
        'email' => 'ali@example.com',
        'phone' => '5551112233',
        'line_one' => 'Test Cad. 1',
        'city' => 'İstanbul',
        'state' => 'Kadıköy',
        'postcode' => '34710',
        'payment' => 'cash-in-hand',
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    app(ShippingCredentials::class)->saveAras([
        'enabled' => true,
        'username' => 'test-user',
        'password' => 'test-pass',
        'customer_code' => '123456',
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
    <SetOrderResponse xmlns="http://tempuri.org/">
      <SetOrderResult>
        <OrderResultInfo>
          <ResultCode>0</ResultCode>
          <ResultMessage>Gönderi kaydedildi</ResultMessage>
          <InvoiceKey>1234567801</InvoiceKey>
        </OrderResultInfo>
      </SetOrderResult>
    </SetOrderResponse>
  </soap:Body>
</soap:Envelope>
XML),
    ]);

    $result = app(ArasShipmentService::class)->createFromOrder($order);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('1234567801');

    $order->refresh();

    expect(data_get($order->meta, 'aras.tracking_number'))->toBe('1234567801')
        ->and($order->status)->toBe(OrderStatusScenario::DISPATCHED)
        ->and(ShipmentTracking::fromMeta((array) $order->meta)['tracking_url'])
        ->toContain('kargotakip.araskargo.com.tr');
});

it('returns dev tracking when aras credentials are missing', function () {
    app(ShippingCredentials::class)->saveAras([
        'enabled' => false,
        'username' => null,
        'password' => null,
    ]);

    $client = app(\App\Etic\Integrations\Shipping\ArasClient::class);

    $result = $client->setOrder([
        'integration_code' => '00000042',
        'invoice_number' => 'ORD-42',
        'waybill_number' => 'ORD-42',
        'receiver_name' => 'Ali Yılmaz',
        'receiver_address' => 'Test Cad. 1',
        'receiver_phone' => '5551112233',
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

it('persists aras credentials per store', function () {
    $credentials = app(ShippingCredentials::class);

    $credentials->saveAras([
        'enabled' => true,
        'username' => 'branch-user',
        'password' => 'branch-pass',
        'customer_code' => '999888',
        'test_mode' => false,
        'default_weight_kg' => 2.5,
        'default_piece_count' => 2,
        'mark_dispatched' => false,
    ]);

    $resolved = $credentials->aras();

    expect($resolved['enabled'])->toBeTrue()
        ->and($resolved['username'])->toBe('branch-user')
        ->and($resolved['password'])->toBe('branch-pass')
        ->and($resolved['customer_code'])->toBe('999888')
        ->and($resolved['default_weight_kg'])->toBe(2.5)
        ->and($resolved['default_piece_count'])->toBe(2)
        ->and($resolved['mark_dispatched'])->toBeFalse();
});
