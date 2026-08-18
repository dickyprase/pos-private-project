<?php

return [
    'currency' => env('APP_CURRENCY', 'IDR'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'transaction_prefix' => env('TRANSACTION_PREFIX', 'KP'),
    'monthly_revenue_target' => (int) env('MONTHLY_REVENUE_TARGET', 50000000),
];
