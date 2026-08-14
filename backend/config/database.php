<?php
/**
 * Loads .env values into PHP environment.
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return; // fall back to system env
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim($v);
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}
loadEnv(__DIR__ . '/../.env');

/**
 * Returns a configured PDO instance (singleton).
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'salon_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$name;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        // Show real error during development so you can debug.
        // In production, replace with a generic message.
        echo json_encode([
            'success' => false,
            'message' => 'DB connection failed: ' . $e->getMessage(),
            'hint'    => 'Check backend/.env DB_HOST/DB_USER/DB_PASSWORD and that salon_db exists.',
        ]);
        exit;
    }
    return $pdo;
}
