<?php
require __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->query('SELECT 1 AS ok');
    $row = $stmt->fetch();

    echo json_encode([
        'status' => 'ok',
        'db' => (int) ($row['ok'] ?? 0),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database health check failed.',
    ]);
}
