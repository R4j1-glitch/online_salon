<?php
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Salon.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class ServiceController {

    /** GET /api/services?salon_id= */
    public static function index(): void {
        $salonId = (int)($_GET['salon_id'] ?? 0);
        if ($salonId <= 0) send_error(422, 'salon_id is required.');
        send_ok('OK', ['services' => Service::by_salon($salonId)]);
    }

    /** POST /api/services  (admin only) */
    public static function store(): void {
        $u  = require_role('salon_admin');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $name  = trim($in['name']  ?? '');
        $price = (float)($in['price'] ?? 0);

        if ($name === '')        send_error(422, 'Service name is required.');
        if ($price < 0)          send_error(422, 'Price must be non-negative.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon) send_error(404, 'Create your salon first.');

        $id = Service::create([
            'salon_id'    => (int)$salon['id'],
            'name'        => $name,
            'description' => $in['description'] ?? null,
            'price'       => $price,
            'duration'    => (int)($in['duration'] ?? 30),
            'status'      => $in['status'] ?? 'active',
        ]);
        send_created('Service created.', ['service' => Service::find($id)]);
    }

    /** PUT /api/services?id=  */
    public static function update(): void {
        $u  = require_role('salon_admin');
        $id = (int)($_GET['id'] ?? 0);
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $service = Service::find($id);
        if (!$service) send_error(404, 'Service not found.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon || (int)$service['salon_id'] !== (int)$salon['id']) {
            send_error(403, 'Not your service.');
        }

        Service::update($id, (int)$salon['id'], [
            'name'        => trim($in['name']        ?? $service['name']),
            'description' => $in['description']      ?? $service['description'],
            'price'       => (float)($in['price']    ?? $service['price']),
            'duration'    => (int)  ($in['duration'] ?? $service['duration']),
            'status'      => $in['status']           ?? $service['status'],
        ]);
        send_ok('Service updated.', ['service' => Service::find($id)]);
    }

    /** DELETE /api/services?id= */
    public static function destroy(): void {
        $u  = require_role('salon_admin');
        $id = (int)($_GET['id'] ?? 0);

        $service = Service::find($id);
        if (!$service) send_error(404, 'Service not found.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon || (int)$service['salon_id'] !== (int)$salon['id']) {
            send_error(403, 'Not your service.');
        }
        Service::delete($id, (int)$salon['id']);
        send_ok('Service deleted.');
    }
}
