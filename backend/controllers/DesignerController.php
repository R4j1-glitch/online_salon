<?php
require_once __DIR__ . '/../models/Designer.php';
require_once __DIR__ . '/../models/DesignerAvailability.php';
require_once __DIR__ . '/../models/Salon.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class DesignerController {

    /** GET /api/designers?action=index */
    public static function index(): void {
        send_ok('OK', ['designers' => Designer::all()]);
    }

    /** GET /api/designers?action=show&id= */
    public static function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $d = Designer::find($id);
        if (!$d) send_error(404, 'Designer not found.');
        $d['services'] = Designer::service_ids($id);
        $d['availability'] = DesignerAvailability::list($id);
        send_ok('OK', ['designer' => $d]);
    }

    /** GET /api/designers?action=by-salon&salon_id= */
    public static function by_salon(): void {
        $sid = (int)($_GET['salon_id'] ?? 0);
        if ($sid <= 0) send_error(422, 'salon_id is required.');
        send_ok('OK', ['designers' => Designer::by_salon($sid)]);
    }

    /** POST /api/designers?action=store  (salon_admin creates designer + user) */
    public static function store(): void {
        $u  = require_role('salon_admin');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $name  = trim($in['name']  ?? '');
        $email = trim($in['email'] ?? '');
        if ($name === '' || $email === '') send_error(422, 'Name and email are required.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon) send_error(404, 'Create your salon first.');

        try {
            $r = Designer::create_with_user([
                'salon_id'       => (int)$salon['id'],
                'name'           => $name,
                'email'          => $email,
                'phone'          => $in['phone'] ?? null,
                'password'       => $in['password'] ?? 'designer123',
                'specialization' => $in['specialization'] ?? null,
                'description'    => $in['description']    ?? null,
                'status'         => $in['status']         ?? 'active',
            ]);
        } catch (RuntimeException $re) {
            send_error(409, $re->getMessage());
        }

        // Optional initial service mapping
        if (!empty($in['service_ids']) && is_array($in['service_ids'])) {
            Designer::set_services($r['designer_id'], $in['service_ids']);
        }

        send_created('Designer created.', Designer::find($r['designer_id']));
    }

    /** PUT /api/designers?action=update&id= */
    public static function update(): void {
        $u  = require_role('salon_admin');
        $id = (int)($_GET['id'] ?? 0);
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $d = Designer::find($id);
        if (!$d) send_error(404, 'Designer not found.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon || (int)$d['salon_id'] !== (int)$salon['id']) {
            send_error(403, 'Not your designer.');
        }

        Designer::update($id, (int)$salon['id'], [
            'specialization' => $in['specialization'] ?? $d['specialization'],
            'description'    => $in['description']    ?? $d['description'],
            'profile_image'  => $in['profile_image']  ?? $d['profile_image'],
            'status'         => $in['status']         ?? $d['status'],
        ]);
        if (isset($in['service_ids']) && is_array($in['service_ids'])) {
            Designer::set_services($id, $in['service_ids']);
        }
        send_ok('Designer updated.', Designer::find($id));
    }

    /** DELETE /api/designers?action=destroy&id= */
    public static function destroy(): void {
        $u  = require_role('salon_admin');
        $id = (int)($_GET['id'] ?? 0);

        $d = Designer::find($id);
        if (!$d) send_error(404, 'Designer not found.');

        $salon = Salon::find_by_owner((int)$u['id']);
        if (!$salon || (int)$d['salon_id'] !== (int)$salon['id']) {
            send_error(403, 'Not your designer.');
        }
        Designer::delete($id, (int)$salon['id']);
        send_ok('Designer deleted.');
    }

    /** GET /api/designers?action=mine  (designer self-view) */
    public static function mine(): void {
        $u = require_role('designer');
        $d = Designer::find_by_user((int)$u['id']);
        if (!$d) send_error(404, 'Designer profile not found.');
        $d['availability'] = DesignerAvailability::list((int)$d['id']);
        $d['services']     = Designer::service_ids((int)$d['id']);
        send_ok('OK', ['designer' => $d]);
    }

    /** PUT /api/designers?action=availability  (designer updates own schedule) */
    public static function save_availability(): void {
        $u  = require_role('designer');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $d = Designer::find_by_user((int)$u['id']);
        if (!$d) send_error(404, 'Designer profile not found.');

        $rows = $in['schedule'] ?? null;
        if (!is_array($rows)) send_error(422, 'schedule array is required.');

        // basic validation
        foreach ($rows as $r) {
            if (!isset($r['day_of_week'], $r['start_time'], $r['end_time'])) {
                send_error(422, 'Each schedule row needs day_of_week, start_time, end_time.');
            }
            if ((int)$r['day_of_week'] < 0 || (int)$r['day_of_week'] > 6) {
                send_error(422, 'day_of_week must be 0-6.');
            }
        }

        DesignerAvailability::replace((int)$d['id'], $rows);
        send_ok('Availability updated.', DesignerAvailability::list((int)$d['id']));
    }
}
