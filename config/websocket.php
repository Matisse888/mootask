<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | MooTask uses WebSocket for real-time instant messaging.
    | LaravelS is required for WebSocket support.
    |
    */

    'server' => [
        'host' => env('LARAVEL_SWS_HTTP_HOST', '0.0.0.0'),
        'port' => env('LARAVEL_SWS_HTTP_PORT', 5200),
        'listens' => [
            'http' => '0.0.0.0:5200',
        ],
        'enable' => true,
    ],

    'websocket' => [
        'enable' => true,
        'handler' => App\WebSocket\WebSocketHandler::class,
    ],

    'processes' => [
        // Customize processes
    ],
];
