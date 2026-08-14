<?php
require_once __DIR__ . '/../controllers/UrgentRequestController.php';

$action = $_GET['action'] ?? '';
$id     = $_GET['id']     ?? null;

match (true) {
    $action === 'index'                              => UrgentRequestController::index(),
    $action === 'store'                              => UrgentRequestController::store(),
    $action === 'counter-offer'  && $id              => UrgentRequestController::counter_offer(),
    $action === 'accept'         && $id              => UrgentRequestController::accept(),
    $action === 'reject'         && $id              => UrgentRequestController::reject(),
    default => send_error(404, 'Urgent route not found.'),
};
