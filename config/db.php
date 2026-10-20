<?php

declare(strict_types=1);

$driver = getenv('DB_DRIVER') ?: 'Pdo_Mysql';

return [
    'driver' => $driver,
    'database' => getenv('DB_DATABASE') ?: 'rest_api_example',
    'hostname' => getenv('DB_HOSTNAME') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'driver_options' => $driver === 'Pdo_Mysql'
        ? [PDO::MYSQL_ATTR_FOUND_ROWS => true]
        : [],
];
