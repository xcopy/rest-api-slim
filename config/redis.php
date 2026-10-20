<?php

declare(strict_types=1);

return [
    'parameters' => [
        'scheme' => getenv('REDIS_SCHEME') ?: 'tcp',
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('REDIS_PORT') ?: 6379),
        'timeout' => (float) getenv('REDIS_TIMEOUT') ?: 1,
        'read_write_timeout' => (float) getenv('REDIS_READ_WRITE_TIMEOUT') ?: 1,
        'database' => (int) getenv('REDIS_DATABASE') ?: 0,
        'username' => getenv('REDIS_USERNAME'),
        'password' => getenv('REDIS_PASSWORD'),
    ],
    'options' => [
        'prefix' => getenv('REDIS_PREFIX') ?: 'rest-api-example:',
    ],
];
