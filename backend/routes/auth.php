<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$action = $_GET['action'] ?? '';

match ($action) {
    'register' => AuthController::register(),
    'login'    => AuthController::login(),
    'logout'   => AuthController::logout(),
    'me'       => AuthController::me(),
    default    => send_error(404, 'Auth route not found.'),
};
