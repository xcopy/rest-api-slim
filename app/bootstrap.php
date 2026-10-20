<?php

declare(strict_types=1);

defined('APP_BASE_PATH') or define('APP_BASE_PATH', dirname(__DIR__));
defined('DATE_FORMAT_DB') or define('DATE_FORMAT_DB', 'Y-m-d H:i:s');
defined('DATE_FORMAT_LOG') or define('DATE_FORMAT_LOG', 'Y-m-d H:i:s');

require APP_BASE_PATH . '/vendor/autoload.php';

$env = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'local');

$file = is_file(APP_BASE_PATH . "/.env.$env") ? ".env.$env" : '.env';

Dotenv\Dotenv::createUnsafeImmutable(APP_BASE_PATH, $file)->safeLoad();
