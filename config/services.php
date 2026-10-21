<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
        'channel_id' => env('YOUTUBE_CHANNEL_ID', 'UCC-UOdK8-mIjxBQm_ot1T-Q'),
        'uploads_playlist_id' => env('YOUTUBE_UPLOADS_PLAYLIST_ID', 'UUC-UOdK8-mIjxBQm_ot1T-Q'),
    ],

    'google' => [
        'tag_id' => env('GOOGLE_TAG_ID', 'G-R88SRKQ41E'),
    ],

];
