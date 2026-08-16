<?php

namespace App\Etic\Integrations\Payments\Http\Controllers;

use App\Etic\Integrations\Payments\PaytrClient;
use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CheckoutPayload;
use App\Etic\Storefront\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Lunar\Models\Order;
use RuntimeException;

class PaytrController extends Controller
{
    public function token(
        Request $request,
        CartManager $carts,
        CheckoutService $checkout,
        PaytrClient $client,
    ): JsonResponse {
        $data = $request->validate(CheckoutPayload::paytrRules());

        try {
            $order = $checkout->createPendingOrder($carts->current(), $data);
            $payload = $client->directPayload($order, $data, $request->ip() ?: '127.0.0.1');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function callback(Request $request, CheckoutService $checkout, PaytrClient $client): Response
    {
        $payload = $request->all();

        if (! $client->verifyCallback($payload)) {
            return response('PAYTR notification failed: bad hash', 400);
        }

        $order = Order::query()->where('reference', $payload['merchant_oid'] ?? null)->first();

        if (! $order) {
            return response('PAYTR notification failed: order not found', 404);
        }

        if (($payload['status'] ?? null) === 'success') {
            if ($order->status !== 'payment-received') {
                $checkout->finalizePaytr($order, $payload);
            }
        } elseif ($order->status === 'awaiting-payment') {
            $order->update(['status' => 'cancelled']);
        }

        return response('OK');
    }

    public function success(Order $order): View
    {
        $order->loadMissing([
            'productLines.purchasable.product',
            'shippingAddress',
            'billingAddress',
        ]);

        return view('theme::pages.success', ['order' => $order]);
    }

    public function fail(Order $order): View
    {
        return view('theme::pages.checkout-paytr-fail', ['order' => $order]);
    }
}
