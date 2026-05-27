<?php
require __DIR__ . '/db.php';

echo 'Database connection test';

$stmt = $conn->query('SELECT NOW() AS server_time');
$row = $stmt->fetch();

echo '<pre>Connected. Server time: ' . htmlspecialchars((string) ($row['server_time'] ?? ''), ENT_QUOTES, 'UTF-8') . '</pre>';
?>