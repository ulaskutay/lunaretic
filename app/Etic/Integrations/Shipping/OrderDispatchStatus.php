<?php

namespace App\Etic\Integrations\Shipping;

use App\Etic\Orders\OrderStatusScenario;
use Lunar\Models\Order;

final class OrderDispatchStatus
{
    public static function advance(Order $order, bool $markDispatched): ?string
    {
        if (! $markDispatched) {
            return null;
        }

        $status = (string) $order->status;

        foreach ([OrderStatusScenario::PROCESSING, OrderStatusScenario::DISPATCHED] as $target) {
            if (OrderStatusScenario::canTransition($status, $target)) {
                $status = $target;
            }
        }

        return $status !== $order->status ? $status : null;
    }
}
