<?php

namespace App\Etic\Storefront;

use App\Etic\Integrations\Marketing\TrackingDispatcher;
use Lunar\Facades\CartSession;
use Lunar\Facades\Discounts;
use Lunar\Models\Cart;
use Lunar\Models\ProductVariant;
use RuntimeException;

class CartManager
{
    public function __construct(private TrackingDispatcher $tracking) {}

    public function current(): Cart
    {
        return CartSession::manager();
    }

    public function add(int $variantId, int $quantity = 1): Cart
    {
        $variant = ProductVariant::query()->findOrFail($variantId);

        if (! $variant->canBeFulfilledAtQuantity($quantity)) {
            throw new RuntimeException('Yeterli stok yok.');
        }

        $cart = $this->current()->add($variant, $quantity);
        $this->tracking->record('add_to_cart', [
            'item_id' => $variant->sku,
            'quantity' => $quantity,
        ]);

        return $cart->calculate();
    }

    public function updateLine(int $lineId, int $quantity): Cart
    {
        $cart = $this->current();
        $line = $cart->lines->firstWhere('id', $lineId);

        if (! $line) {
            throw new RuntimeException('Sepet satırı bulunamadı.');
        }

        if ($quantity < 1) {
            return $this->removeLine($lineId);
        }

        if (! $line->purchasable->canBeFulfilledAtQuantity($quantity)) {
            throw new RuntimeException('Yeterli stok yok.');
        }

        $cart->updateLine($lineId, $quantity);

        return $cart->calculate();
    }

    public function removeLine(int $lineId): Cart
    {
        $cart = $this->current();
        $cart->remove($lineId);

        return $cart->calculate();
    }

    public function applyCoupon(string $code): Cart
    {
        $cart = $this->current();
        $cart->coupon_code = $code;
        $cart->save();
        Discounts::resetDiscounts();

        return $cart->recalculate();
    }
}
