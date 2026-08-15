<?php

namespace App\Etic\Storefront;

use App\Etic\Integrations\Marketing\TrackingDispatcher;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\Country;
use Lunar\Models\Order;
use RuntimeException;

class CheckoutService
{
    public function __construct(private TrackingDispatcher $tracking) {}

    public function place(Cart $cart, array $payload): Order
    {
        $country = Country::query()->where('iso2', $payload['country_iso2'] ?? 'TR')->firstOrFail();

        $address = [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'line_one' => $payload['line_one'],
            'line_two' => $payload['line_two'] ?? null,
            'city' => $payload['city'],
            'state' => $payload['state'] ?? null,
            'postcode' => $payload['postcode'] ?? '34000',
            'country_id' => $country->id,
            'contact_phone' => $payload['phone'],
            'contact_email' => $payload['email'],
            'company_name' => $payload['company_name'] ?? null,
        ];

        $cart->setShippingAddress($address);
        $cart->setBillingAddress($payload['same_as_shipping'] ?? true ? $address : array_merge($address, [
            'line_one' => $payload['billing_line_one'] ?? $address['line_one'],
            'city' => $payload['billing_city'] ?? $address['city'],
            'postcode' => $payload['billing_postcode'] ?? $address['postcode'],
        ]));

        $cart->update([
            'meta' => array_merge((array) $cart->meta, [
                'notes' => $payload['notes'] ?? null,
                'email' => $payload['email'],
                'phone' => $payload['phone'],
            ]),
        ]);

        $cart->calculate();

        $options = ShippingManifest::getOptions($cart);
        $identifier = $payload['shipping'] ?? $options->first()?->getIdentifier();
        $option = $options->first(fn (ShippingOption $item) => $item->getIdentifier() === $identifier) ?? $options->first();

        if (! $option) {
            throw new RuntimeException('Kargo seçeneği bulunamadı.');
        }

        $cart->setShippingOption($option);
        $cart->calculate();

        $result = Payments::driver($payload['payment'])
            ->cart($cart)
            ->withData([
                'token' => $payload['payment_token'] ?? null,
                'status' => $payload['payment_status'] ?? null,
                'meta' => ['notes' => $payload['notes'] ?? null],
            ])
            ->authorize();

        if (! $result?->success) {
            throw new RuntimeException($result?->message ?: 'Ödeme alınamadı.');
        }

        CartSession::forget();

        $order = Order::query()->with('productLines')->findOrFail($result->orderId);
        $value = TrackingDispatcher::fromMinor((int) $order->total->value);
        $currency = $order->currency_code ?: 'TRY';

        $this->tracking->record('add_payment_info', [
            'payment_type' => $payload['payment'],
            'value' => $value,
            'currency' => $currency,
            'user' => $this->trackingUser($payload),
        ], flash: true);
        $this->tracking->record('purchase', [
            'transaction_id' => $order->reference,
            'value' => $value,
            'currency' => $currency,
            'user' => $this->trackingUser($payload),
            'items' => $order->productLines->map(fn ($line) => [
                'item_id' => $line->identifier,
                'item_name' => $line->description,
                'quantity' => $line->quantity,
                'price' => TrackingDispatcher::fromMinor((int) $line->unit_price->value),
            ])->values()->all(),
        ], flash: true);

        return $order;
    }

    /** @param  array<string, mixed>  $payload */
    private function trackingUser(array $payload): array
    {
        return [
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'first_name' => $payload['first_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'city' => $payload['city'] ?? null,
            'state' => $payload['state'] ?? null,
            'zip' => $payload['postcode'] ?? null,
            'country' => 'tr',
        ];
    }
}
