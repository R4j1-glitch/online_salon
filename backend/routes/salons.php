<?php
require_once __DIR__ . '/../controllers/SalonController.php';

$action = $_GET['action'] ?? '';
$id     = $_GET['id']     ?? null;

match (true) {
    $action === 'mine'                => SalonController::mine(),
    $action === 'index' && !$id       => SalonController::index(),
    $action === 'show'  && $id        => SalonController::show(),
    $action === 'store'               => SalonController::store(),
    $action === 'update' && $id       => SalonController::update(),
    $action === 'destroy' && $id      => SalonController::destroy(),
    default => send_error(404, 'Salon route not found.'),
};
