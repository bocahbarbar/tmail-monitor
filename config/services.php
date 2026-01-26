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
    'mailtm' => [
        'bearer_token' => env('MAILTM_BEARER_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NjkzNTI4NTEsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoicmhlQHBvd2Vyc2NyZXdzLmNvbSIsImlkIjoiNjhjMjZmZDJlNzJlOWEyOTMxMDNmZjk1IiwibWVyY3VyZSI6eyJzdWJzY3JpYmUiOlsiL2FjY291bnRzLzY4YzI2ZmQyZTcyZTlhMjkzMTAzZmY5NSJdfX0.I1mbPe1xqRBAlvLKashDY-_sWKgtCSlGWo7-PRMJQtu39OmGK0NX91GyqBDFcjvqBigktHeCQqjIAtS-nYDP4Q'),
    ],

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

];