<?php

namespace App\Etic\Storefront;

use App\Etic\Integrations\Marketing\TrackingDispatcher;
use Lunar\Base\Validation\CouponValidatorInterface;
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
        $price = $variant->prices->first();
        $this->tracking->record('add_to_cart', [
            'item_id' => $variant->sku,
            'quantity' => $quantity,
            'value' => TrackingDispatcher::fromMinor((int) ($price?->price?->value ?? 0) * $quantity),
            'currency' => $price?->currency?->code ?? 'TRY',
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
        $code = trim($code);

        if ($code === '' || ! $this->couponValidator()->validate($code)) {
            throw new RuntimeException(__('etic.storefront.coupon.invalid'));
        }

        $cart = $this->current();
        $cart->coupon_code = $code;
        $cart->save();
        Discounts::resetDiscounts();

        $cart = $cart->recalculate();

        $this->tracking->record('apply_coupon', [
            'coupon' => $cart->coupon_code,
            'value' => (int) ($cart->discountTotal?->value ?? 0),
        ]);

        return $cart;
    }

    public function removeCoupon(): Cart
    {
        $cart = $this->current();
        $cart->coupon_code = null;
        $cart->save();
        Discounts::resetDiscounts();

        return $cart->recalculate();
    }

    private function couponValidator(): CouponValidatorInterface
    {
        return app(config('lunar.discounts.coupon_validator'));
    }
}
