<?php
declare(strict_types=1);

// Simple health check for Perceptor worker
header('Content-Type: application/json');

echo json_encode([
    'ok'   => true,
    'time' => time(),
    'php'  => PHP_VERSION
]);
