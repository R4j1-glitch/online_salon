<?php
require_once __DIR__ . '/../models/Salon.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class SalonController {

    /** GET /api/salons */
    public static function index(): void {
        send_ok('OK', ['salons' => Salon::all()]);
    }

    /** GET /api/salons/{id} */
    public static function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $salon = Salon::find($id);
        if (!$salon) send_error(404, 'Salon not found.');
        send_ok('OK', ['salon' => $salon]);
    }

    /** POST /api/salons */
    public static function store(): void {
        $u = require_role('salon_admin');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($in['name'] ?? '');
        if ($name === '') send_error(422, 'Salon name is required.');

        // A salon_admin should typically have one salon; create or update theirs
        $existing = Salon::find_by_owner((int)$u['id']);
        if ($existing) {
            Salon::update((int)$existing['id'], (int)$u['id'], [
                'name' => $name,
                'description' => $in['description'] ?? null,
                'address'     => $in['address']     ?? null,
                'phone'       => $in['phone']       ?? null,
                'opening_time'=> $in['opening_time']?? '09:00:00',
                'closing_time'=> $in['closing_time']?? '19:00:00',
            ]);
            send_ok('Salon updated.', ['salon' => Salon::find((int)$existing['id'])]);
        }
        $id = Salon::create([
            'owner_id'     => (int)$u['id'],
            'name'         => $name,
            'description'  => $in['description'] ?? null,
            'address'      => $in['address']     ?? null,
            'phone'        => $in['phone']       ?? null,
            'opening_time' => $in['opening_time']?? '09:00:00',
            'closing_time' => $in['closing_time']?? '19:00:00',
        ]);
        send_created('Salon created.', ['salon' => Salon::find($id)]);
    }

    /** PUT /api/salons/{id} */
    public static function update(): void {
        $u    = require_role('salon_admin');
        $id   = (int)($_GET['id'] ?? 0);
        $in   = json_decode(file_get_contents('php://input'), true) ?? [];

        $salon = Salon::find($id);
        if (!$salon)                       send_error(404, 'Salon not found.');
        if ((int)$salon['owner_id'] !== (int)$u['id']) send_error(403, 'Not your salon.');

        Salon::update($id, (int)$u['id'], [
            'name' => trim($in['name'] ?? $salon['name']),
            'description' => $in['description'] ?? $salon['description'],
            'address'     => $in['address']     ?? $salon['address'],
            'phone'       => $in['phone']       ?? $salon['phone'],
            'opening_time'=> $in['opening_time']?? $salon['opening_time'],
            'closing_time'=> $in['closing_time']?? $salon['closing_time'],
        ]);
        send_ok('Salon updated.', ['salon' => Salon::find($id)]);
    }

    /** DELETE /api/salons/{id} */
    public static function destroy(): void {
        $u  = require_role('salon_admin');
        $id = (int)($_GET['id'] ?? 0);
        $salon = Salon::find($id);
        if (!$salon)                       send_error(404, 'Salon not found.');
        if ((int)$salon['owner_id'] !== (int)$u['id']) send_error(403, 'Not your salon.');

        Salon::delete($id, (int)$u['id']);
        send_ok('Salon deleted.');
    }

    /** GET /api/salons/mine  (admin convenience) */
    public static function mine(): void {
        $u = require_role('salon_admin');
        $salon = Salon::find_by_owner((int)$u['id']);
        send_ok('OK', ['salon' => $salon]);
    }
}
