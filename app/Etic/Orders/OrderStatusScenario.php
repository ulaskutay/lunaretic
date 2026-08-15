<?php

namespace App\Etic\Orders;

final class OrderStatusScenario
{
    public const AWAITING_PAYMENT = 'awaiting-payment';

    public const PAYMENT_OFFLINE = 'payment-offline';

    public const PAYMENT_RECEIVED = 'payment-received';

    public const PROCESSING = 'processing';

    public const DISPATCHED = 'dispatched';

    public const DELIVERED = 'delivered';

    public const CANCELLED = 'cancelled';

    /**
     * Merchant work queue: still needs action before dispatch/delivery.
     *
     * @return list<string>
     */
    public static function openStatuses(): array
    {
        return [
            self::AWAITING_PAYMENT,
            self::PAYMENT_OFFLINE,
            self::PAYMENT_RECEIVED,
            self::PROCESSING,
        ];
    }

    /**
     * Happy-path fulfilment after checkout.
     *
     * @return list<string>
     */
    public static function fulfilmentPath(): array
    {
        return [
            self::PAYMENT_RECEIVED,
            self::PROCESSING,
            self::DISPATCHED,
            self::DELIVERED,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::AWAITING_PAYMENT => [self::PAYMENT_OFFLINE, self::PAYMENT_RECEIVED, self::CANCELLED],
            self::PAYMENT_OFFLINE => [self::PAYMENT_RECEIVED, self::PROCESSING, self::CANCELLED],
            self::PAYMENT_RECEIVED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::DISPATCHED, self::CANCELLED],
            self::DISPATCHED => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return (string) (config("lunar.orders.statuses.{$status}.label") ?? $status);
    }
}
