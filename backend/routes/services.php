<?php
require_once __DIR__ . '/../controllers/ServiceController.php';

$action = $_GET['action'] ?? '';
$id     = $_GET['id']     ?? null;

match (true) {
    $action === 'index'          => ServiceController::index(),
    $action === 'store'          => ServiceController::store(),
    $action === 'update' && $id  => ServiceController::update(),
    $action === 'destroy' && $id => ServiceController::destroy(),
    default => send_error(404, 'Service route not found.'),
};
