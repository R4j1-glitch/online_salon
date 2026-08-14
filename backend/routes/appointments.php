<?php
require_once __DIR__ . '/../controllers/AppointmentController.php';

$action = $_GET['action'] ?? '';
$id     = $_GET['id']     ?? null;

match (true) {
    $action === 'index'                       => AppointmentController::index(),
    $action === 'show'            && $id      => AppointmentController::show(),
    $action === 'check-availability'          => AppointmentController::check_availability(),
    $action === 'store'                       => AppointmentController::store(),
    $action === 'accept'          && $id      => AppointmentController::accept(),
    $action === 'reject'          && $id      => AppointmentController::reject(),
    $action === 'cancel'          && $id      => AppointmentController::cancel(),
    $action === 'complete'        && $id      => AppointmentController::complete(),
    default => send_error(404, 'Appointment route not found.'),
};
