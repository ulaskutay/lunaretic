<?php

namespace App\Etic\Integrations\Shipping;

use Closure;
use Lunar\Base\ShippingModifier;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Contracts\Cart;

class TableRateShippingModifier extends ShippingModifier
{
    public function handle(Cart $cart, Closure $next)
    {
        foreach (app(ShippingProviderInterface::class)->optionsFor($cart) as $option) {
            ShippingManifest::addOption($option);
        }

        return $next($cart);
    }
}
