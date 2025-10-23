<?php

return [
    'domains' => [
        'local'      => env('GUEST_DOMAIN_LOCAL', 'guestdemo.localhost'),
        'staging'    => env('GUEST_DOMAIN_STAGING', 'guestdemo.staging.example.com'),
        // 本番はデフォルトを汎用値にし、実値は.envで切り替える
        'production' => env('GUEST_DOMAIN_PRODUCTION', 'guestdemo.example.com'),
    ],

    // config読み込み中はapp()を使わず、env()のみで分岐させる
    'protocol' => env('GUEST_PROTOCOL', env('APP_ENV', 'production') === 'local' ? 'http' : 'https'),

    'ports' => [
        'local'      => env('GUEST_PORT_LOCAL', 9000),
        'staging'    => env('GUEST_PORT_STAGING'),
        'production' => env('GUEST_PORT_PRODUCTION'),
    ],
];
