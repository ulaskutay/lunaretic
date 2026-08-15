<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Support\StoreContext;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Models\Contracts\Cart;
use Lunar\Models\TaxClass;

class TableRateShippingProvider implements ShippingProviderInterface
{
    public function __construct(private StoreContext $store) {}

    public function optionsFor(Cart $cart): array
    {
        $currency = $cart->currency ?? $this->store->currency();
        $subtotal = $cart->subTotal?->value ?? 0;
        $taxClass = TaxClass::getDefault();

        $options = [];

        foreach (app(ShippingRates::class)->all() as $rate) {
            $max = $rate['max_subtotal'] ?? null;

            if ($max !== null && $subtotal > $max) {
                continue;
            }

            $options[] = new ShippingOption(
                name: $rate['name'],
                description: $rate['description'] ?: 'Türkiye içi teslimat',
                identifier: $rate['identifier'],
                price: new Price((int) $rate['price'], $currency, 1),
                taxClass: $taxClass,
                option: $rate['identifier'],
            );

            break;
        }

        return $options;
    }
}
