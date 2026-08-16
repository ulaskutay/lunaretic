<?php

return [

    'default' => env('PAYMENTS_TYPE', 'cash-in-hand'),

    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'payment-offline',
        ],
        'iyzico' => [
            'driver' => 'iyzico',
            'authorized' => 'payment-received',
        ],
        'paytr' => [
            'driver' => 'paytr',
            'authorized' => 'payment-received',
        ],
    ],

];
