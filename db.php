<?php
$env = [];
$envPath = __DIR__ . '/.env';

if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        $separatorPos = strpos($line, '=');
        if ($separatorPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separatorPos));
        $value = trim(substr($line, $separatorPos + 1));
        if ($key !== '') {
            $env[$key] = $value;
        }
    }
}

function env_value(string $key, ?string $default = null): ?string
{
    global $env;

    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    $value = getenv($key);
    return $value !== false ? $value : $default;
}

$host = env_value('DB_HOST');
$port = env_value('DB_PORT', '5432');
$dbName = env_value('DB_NAME');
$user = env_value('DB_USER');
$pass = env_value('DB_PASS');
$sslMode = env_value('DB_SSL');
$appEnv = env_value('APP_ENV', 'production');

if ($dbName === null || $user === null) {
    http_response_code(500);
    exit('Database configuration is missing. See .env.example.');
}

$dsnParts = [
    sprintf('host=%s', $host),
    sprintf('port=%s', $port),
    sprintf('dbname=%s', $dbName),
];
if ($sslMode !== null && $sslMode !== '') {
    $dsnParts[] = sprintf('sslmode=%s', $sslMode);
}

$dsn = 'pgsql:' . implode(';', $dsnParts);

try {
    $conn = new PDO($dsn, $user, $pass ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    if ($appEnv !== 'production') {
        exit('Database connection failed: ' . $e->getMessage());
    }
    exit('Database connection failed.');
}
