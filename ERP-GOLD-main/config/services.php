<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gold_api' => [
        // Active price provider: 'goldapi' (default, per-karat SAR grams) or
        // 'twelvedata' (XAU/USD spot; karats + SAR peg computed locally).
        'provider' => env('GOLD_PRICE_PROVIDER', 'goldapi'),

        // goldapi.io
        'key' => env('GOLD_API_KEY'),
        'base_url' => env('GOLD_API_BASE_URL', 'https://www.goldapi.io'),
        'symbol' => env('GOLD_API_SYMBOL', 'XAU'),

        // twelvedata.com (free plan ~800 req/day) — much larger free quota.
        'twelvedata_key' => env('TWELVEDATA_API_KEY'),
        'twelvedata_base_url' => env('TWELVEDATA_BASE_URL', 'https://api.twelvedata.com'),

        // SAR is pegged to USD (~3.75). Used to convert USD spot to SAR when the
        // provider only returns USD. Override via env if the peg ever changes.
        'sar_per_usd' => env('GOLD_SAR_PER_USD', 3.75),
    ],

];
