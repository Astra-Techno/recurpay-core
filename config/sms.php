<?php

return [
    'default_provider' => App\Services\SMS\Providers\Msg91Provider::class,

    'providers' => [
        'msg91' => [
            'api_key' => env('MSG91_API_KEY'),
            'sender_id' => env('MSG91_SENDER_ID'),
        ],
        'fast2sms' => [
            'api_key' => env('FAST2SMS_API_KEY'),
        ],
        'textlocal' => [
            'api_key' => env('TEXTLOCAL_API_KEY'),
        ]
    ]
];
