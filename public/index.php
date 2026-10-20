<?php

declare(strict_types=1);

try {
    $app = require dirname(__DIR__) . '/app/app.php';
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => $e->getMessage()]);
}
