<?php

declare(strict_types=1);

$db = require __DIR__ . '/db.php';

return [
    'adapter' => strtolower(str_replace('Pdo_', '', $db['driver'])),
    'name' => $db['database'],
    'host' => $db['hostname'],
    'port' => $db['port'],
    'user' => $db['username'],
    'pass' => $db['password'],
    'charset' => $db['charset'],
    'driver_options' => $db['driver_options'],
];
