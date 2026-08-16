<?php

namespace App\Etic\Storefront\Http\Api;

use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CheckoutPayload;
use App\Etic\Storefront\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Order;

class CartApiController
{
    public function __construct(
        private StorefrontPresenter $present,
        private CartManager $carts,
    ) {}

    public function show(): JsonResponse
    {
        return $this->cartResponse($this->carts->current()->calculate());
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->carts->add((int) $data['variant_id'], (int) $data['quantity']);

        return $this->cartResponse($cart);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = $this->carts->updateLine((int) $data['line_id'], (int) $data['quantity']);

        return $this->cartResponse($cart);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer'],
        ]);

        return $this->cartResponse($this->carts->removeLine((int) $data['line_id']));
    }

    public function coupon(Request $request): JsonResponse
    {
        $code = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => __('etic.storefront.coupon.required'),
        ])['code'];

        return $this->cartResponse($this->carts->applyCoupon($code));
    }

    public function removeCoupon(): JsonResponse
    {
        return $this->cartResponse($this->carts->removeCoupon());
    }

    public function checkout(): JsonResponse
    {
        $cart = $this->carts->current()->calculate();

        return response()->json([
            'data' => $this->present->cart($cart),
            'shipping_options' => ShippingManifest::getOptions($cart)
                ->map(fn ($option) => $this->present->shippingOption($option))
                ->values()
                ->all(),
            'events' => $this->present->trackingEvents(),
        ]);
    }

    public function place(Request $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate(CheckoutPayload::rules());

        $order = $checkout->place($this->carts->current(), $data);

        return response()->json([
            'data' => $this->present->order($order),
            'events' => $this->present->trackingEvents(),
        ], 201);
    }

    public function order(Order $order): JsonResponse
    {
        $order->loadMissing([
            'productLines.purchasable.product',
            'shippingAddress',
            'billingAddress',
        ]);

        return response()->json(['data' => $this->present->order($order)]);
    }

    private function cartResponse($cart): JsonResponse
    {
        return response()->json([
            'data' => $this->present->cart($cart),
            'events' => $this->present->trackingEvents(),
        ])->header(CartManager::TOKEN_HEADER, (string) $this->carts->token($cart));
    }
}
