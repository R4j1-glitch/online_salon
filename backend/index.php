<?php
/**
 * Front controller / router.
 * URL pattern: /api/<resource>/<action>?...
 *
 * Stage 1 routes: auth only.
 */
declare(strict_types=1);

// --- CORS ---
// Allow either the configured FRONTEND_URL or a same-origin request (no Origin header).
$origin = getenv('FRONTEND_URL') ?: 'http://localhost:5173';
$reqOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($reqOrigin === '' || $reqOrigin === $origin) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: $reqOrigin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/utils/response.php';

// --- Routing ---
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri  = trim($uri, '/');
$parts = explode('/', $uri);

// Drop empty / leading project folder
//   expected: parts[0] = 'api'
if (($parts[0] ?? '') !== 'api') {
    send_error(404, 'Not found.');
}

$resource = $parts[1] ?? '';

switch ($resource) {
    case 'auth':
        require_once __DIR__ . '/routes/auth.php';
        break;
    case 'salons':
        require_once __DIR__ . '/routes/salons.php';
        break;
    case 'services':
        require_once __DIR__ . '/routes/services.php';
        break;
    case 'designers':
        require_once __DIR__ . '/routes/designers.php';
        break;
    case 'appointments':
        require_once __DIR__ . '/routes/appointments.php';
        break;
    case 'urgent-requests':
        require_once __DIR__ . '/routes/urgent.php';
        break;
    default:
        send_error(404, 'Resource not found.');
}
