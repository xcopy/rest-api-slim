<?php

declare(strict_types=1);

return [
    'default' => [
        'limit' => getenv('RATE_LIMIT_LIMIT') ?: 60,
        'window' => getenv('RATE_LIMIT_WINDOW') ?: 60,
    ],

    'buckets' => [
        'global' => [
            'limit' => 120,
            'window' => 60,
            'by' => ['ip'],
        ],
        // auth
        'auth.register' => [
            'limit' => 5,
            'window' => 3600,
            'by' => ['ip'],
        ],
        'auth.login.ip' => [
            'limit' => 20,
            'window' => 60,
            'by' => ['ip'],
        ],
        'auth.login.email' => [
            'limit' => 5,
            'window' => 900,
            'by' => ['email'],
        ],
        // password
        'password.forgot' => [
            'limit' => 3,
            'window' => 3600,
            'by' => ['email'],
        ],
        'password.reset' => [
            'limit' => 5,
            'window' => 3600,
            'by' => ['ip'],
        ],
        'password.change' => [
            'limit' => 5,
            'window' => 3600,
            'by' => ['user'],
        ],
        // email-verification
        'email-verification.send' => [
            'limit' => 3,
            'window' => 3600,
            'by' => ['ip', 'email'],
        ],
        'email-verification.verify' => [
            'limit' => 10,
            'window' => 3600,
            'by' => ['ip'],
        ],
    ],
];
