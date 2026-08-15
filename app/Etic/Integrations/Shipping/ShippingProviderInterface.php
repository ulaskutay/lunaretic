<?php

namespace App\Etic\Integrations\Shipping;

use Lunar\DataTypes\ShippingOption;
use Lunar\Models\Contracts\Cart;

interface ShippingProviderInterface
{
    /**
     * @return list<ShippingOption>
     */
    public function optionsFor(Cart $cart): array;
}
