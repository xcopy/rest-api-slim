<?php

declare(strict_types=1);

defined('APP_BASE_PATH') or define('APP_BASE_PATH', dirname(__DIR__));
defined('DATE_FORMAT_DB') or define('DATE_FORMAT_DB', 'Y-m-d H:i:s');
defined('DATE_FORMAT_LOG') or define('DATE_FORMAT_LOG', 'Y-m-d H:i:s');

require APP_BASE_PATH . '/vendor/autoload.php';

$env = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'production');

$file = match ($env) {
    'local' => '.env.local',
    'testing' => '.env.testing',
    'production' => '.env',
    default => throw new RuntimeException("Unknown environment: $env"),
};

// Make the resolved environment visible to the rest of the app, even if the file doesn't define it
putenv("APP_ENV=$env");
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $env;

Dotenv\Dotenv::createUnsafeImmutable(APP_BASE_PATH, $file)->safeLoad();
