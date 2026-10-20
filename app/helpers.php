<?php

declare(strict_types=1);

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $baseDir = APP_BASE_PATH . '/config';
        $segments = explode('.', $key);

        // Find the longest prefix that maps to an existing file
        for ($i = count($segments); $i > 0; $i--) {
            $file = $baseDir . '/' . implode('/', array_slice($segments, 0, $i)) . '.php';

            if (! is_file($file)) {
                continue;
            }

            $data = $cache[$file] ??= require $file;
            $rest = array_slice($segments, $i);

            // Walk the remaining segments into the array
            foreach ($rest as $segment) {
                if (! is_array($data) || ! array_key_exists($segment, $data)) {
                    return $default;
                }

                $data = $data[$segment];
            }

            return $data;
        }

        return $default;
    }
}

if (!function_exists('parseDateTime')) {
    function parseDateTime(string $datetime, string $timezone = 'UTC'): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, new DateTimeZone($timezone));
    }
}

if (!function_exists('now')) {
    function now(bool $format = false, string $timezone = 'UTC'): DateTimeImmutable|string
    {
        $now = parseDateTime('now', $timezone);

        return $format ? $now->format(DATE_FORMAT_DB) : $now;
    }
}

if (!function_exists('generateToken')) {
    function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('hashToken')) {
    function hashToken(string $value): string
    {
        return hash('sha256', $value);
    }
}

if (!function_exists('hashPassword')) {
    function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('checkPassword')) {
    function checkPassword(string $password, string $hash): bool
    {
        if ($password === '' || $hash === '') {
            throw new InvalidArgumentException('Password or/and hash cannot be empty');
        }

        return password_verify($password, $hash);
    }
}

if (!function_exists('humanize')) {
    function humanize(string $value): string
    {
        $value = preg_replace('/(?<=\w)([A-Z])/', '_$1', $value);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return ucfirst(strtolower($value));
    }
}
