<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Store & Platform Affiliate Information
    |--------------------------------------------------------------------------
    |
    | Configure affiliate tracking tokens and query parameters for each store.
    | When set, these parameters are automatically appended to store URLs.
    |
    | You can hardcode your affiliate credentials here or configure them
    | via environment variables (.env).
    |
    */

    'apple' => [
        // Apple Services Performance Partners campaign/partner tokens
        'pt' => env('APPLE_AFFILIATE_PT', ''),
        'ct' => env('APPLE_AFFILIATE_CT', ''),
        'at' => env('APPLE_AFFILIATE_AT', ''),
        // Any custom hardcoded query parameters to append to Apple App Store URLs
        'custom_params' => [
            // 'at' => 'YOUR_APPLE_TOKEN',
        ],
    ],

    'google' => [
        // Google Play referrer parameters (e.g. 'utm_source=ctc&utm_medium=catalog')
        'referrer' => env('GOOGLE_PLAY_AFFILIATE_REFERRER', ''),
        // Any custom hardcoded query parameters to append to Google Play URLs
        'custom_params' => [
            // 'referrer' => '...',
        ],
    ],

    'steam' => [
        // Steam referral / affiliate parameters or partner tags
        'custom_params' => [
            // 'utm_source' => 'catalogingthecryptic',
        ],
    ],

];
