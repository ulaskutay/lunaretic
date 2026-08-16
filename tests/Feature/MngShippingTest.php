<?php

use App\Etic\Integrations\Shipping\MngClient;
use App\Etic\Integrations\Shipping\MngShipmentService;
use App\Etic\Integrations\Shipping\ShipmentTracking;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Orders\OrderStatusScenario;
use App\Etic\Support\CommerceBootstrap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
    Cache::flush();
});

it('creates an mng shipment and stores tracking on the order', function () {
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

    app(ShippingCredentials::class)->saveMng([
        'enabled' => true,
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'customer_number' => '123456',
        'password' => 'secret',
        'default_city_code' => 34,
        'default_district_code' => 100,
        'test_mode' => true,
        'default_weight_kg' => 1,
        'default_piece_count' => 1,
        'mark_dispatched' => true,
    ]);

    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, '/mngapi/api/token')) {
            return Http::response(['jwt' => 'test-jwt-token']);
        }

        if (str_contains($url, '/createRecipient')) {
            return Http::response(['shipperBranchCode' => '001']);
        }

        if (str_contains($url, '/createOrder')) {
            return Http::response([['referenceId' => '00000001']]);
        }

        if (str_contains($url, '/createbarcode')) {
            return Http::response([['shipmentId' => 'MNG123456789']]);
        }

        return Http::response([], 404);
    });

    $result = app(MngShipmentService::class)->createFromOrder($order);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('MNG123456789');

    $order->refresh();

    expect(data_get($order->meta, 'mng.tracking_number'))->toBe('MNG123456789')
        ->and($order->status)->toBe(OrderStatusScenario::DISPATCHED)
        ->and(ShipmentTracking::fromMeta((array) $order->meta)['tracking_url'])
        ->toContain('mngkargo.com.tr');
});

it('returns dev tracking when mng credentials are missing', function () {
    app(ShippingCredentials::class)->saveMng([
        'enabled' => false,
        'client_id' => null,
        'client_secret' => null,
        'customer_number' => null,
        'password' => null,
    ]);

    $client = app(MngClient::class);

    $result = $client->createShipment([
        'integration_code' => '00000042',
        'reference_number' => 'ORD-42',
        'receiver_name' => 'Ayşe Demir',
        'receiver_address' => 'Test Cad. 1',
        'receiver_phone' => '5552223344',
        'receiver_email' => 'ayse@example.com',
        'receiver_city' => 'İstanbul',
        'receiver_town' => 'Kadıköy',
        'city_code' => 34,
        'district_code' => 100,
        'piece_count' => 1,
        'weight_kg' => 1,
        'description' => 'Test',
        'is_cod' => false,
        'cod_amount' => 0,
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->trackingNumber)->toBe('DEV00000042');
});
