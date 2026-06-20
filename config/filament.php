<?php

return [
    'mfa' => [
        'enabled' => (bool) env('FILAMENT_MFA_ENABLED', true),
        'required' => (bool) env('FILAMENT_MFA_REQUIRED', false),
    ],
    
    'broadcasting' => [
        'echo' => [
            'broadcaster' => env('BROADCAST_DRIVER', 'pusher'),
            'key' => env('PUSHER_APP_KEY'),
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'wsHost' => env('PUSHER_HOST') ?: 'ws-' . env('PUSHER_APP_CLUSTER', 'mt1') . '.pusher.com',
            'wsPort' => env('PUSHER_PORT', 80),
            'wssPort' => env('PUSHER_PORT', 443),
            'encrypted' => true,
            'disableStats' => false,
            'enabledTransports' => ['ws', 'wss'],
        ],
    ],
];

