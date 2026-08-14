<?php
require_once __DIR__ . '/../controllers/DesignerController.php';

$action = $_GET['action'] ?? '';
$id     = $_GET['id']     ?? null;

match (true) {
    $action === 'index'                                    => DesignerController::index(),
    $action === 'show'           && $id                    => DesignerController::show(),
    $action === 'by-salon'                                => DesignerController::by_salon(),
    $action === 'store'                                    => DesignerController::store(),
    $action === 'update'          && $id                   => DesignerController::update(),
    $action === 'destroy'         && $id                   => DesignerController::destroy(),
    $action === 'mine'                                     => DesignerController::mine(),
    $action === 'availability'                             => DesignerController::save_availability(),
    default => send_error(404, 'Designer route not found.'),
};
