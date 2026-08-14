<?php
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Designer.php';
require_once __DIR__ . '/../models/DesignerAvailability.php';
require_once __DIR__ . '/../models/Salon.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class AppointmentController {

    /**
     * GET /api/appointments?action=check-availability
     * Required: designer_id, service_id, date (YYYY-MM-DD), start_time (HH:MM:SS or HH:MM)
     */
    public static function check_availability(): void {
        $designerId = (int)($_GET['designer_id'] ?? 0);
        $serviceId  = (int)($_GET['service_id']  ?? 0);
        $date       =       $_GET['date']        ?? '';
        $startIn    =       $_GET['start_time']  ?? '';

        if (!$designerId || !$serviceId || $date === '' || $startIn === '') {
            send_error(422, 'designer_id, service_id, date and start_time are required.');
        }

        $service = Service::find($serviceId);
        if (!$service) send_error(404, 'Service not found.');

        $designer = Designer::find($designerId);
        if (!$designer) send_error(404, 'Designer not found.');

        // Normalize start/end
        $startTime = strlen($startIn) === 5 ? "$startIn:00" : $startIn;
        $endTime   = self::add_minutes($startTime, (int)$service['duration']);

        // Date validity
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            send_error(422, 'Invalid date. Use YYYY-MM-DD.');
        }
        $dow = (int)$d->format('w'); // 0=Sun

        // Salon hours
        $salon = Salon::find((int)$designer['salon_id']);
        if (!$salon) send_error(404, 'Salon not found.');
        if (self::time_lt($startTime, $salon['opening_time']) || self::time_gt($endTime, $salon['closing_time'])) {
            send_error(409, 'Salon is closed at the selected time.');
        }

        // Designer weekly schedule
        $avail = DesignerAvailability::for_day($designerId, $dow);
        if (!$avail) {
            send_error(409, 'Designer does not work on this day.');
        }
        if (self::time_lt($startTime, $avail['start_time']) || self::time_gt($endTime, $avail['end_time'])) {
            send_error(409, 'Time is outside designer working hours.');
        }

        // Designer status
        if ($designer['status'] !== 'active') {
            send_error(409, 'Designer is currently unavailable.');
        }

        // Conflict check (normal booking only flags as "unavailable")
        $conflicts = Appointment::count_conflicting($designerId, $date, $startTime, $endTime);
        if ($conflicts > 0) {
            send_error(409, 'This designer is not available at the selected date and time. Please choose another date or time.');
        }

        send_ok('Available', [
            'end_time' => $endTime,
            'normal_price' => (float)$service['price'],
        ]);
    }

    /** POST /api/appointments  (customer books) */
    public static function store(): void {
        $u  = require_role('customer');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $designerId = (int)($in['designer_id'] ?? 0);
        $serviceId  = (int)($in['service_id']  ?? 0);
        $date       =       $in['date']        ?? '';
        $startTime  =       $in['start_time']  ?? '';
        $type       =       $in['appointment_type'] ?? 'normal';

        if (!$designerId || !$serviceId || $date === '' || $startTime === '') {
            send_error(422, 'designer_id, service_id, date, start_time are required.');
        }
        if (!in_array($type, ['normal','urgent'], true)) {
            send_error(422, 'Invalid appointment_type.');
        }

        $service  = Service::find($serviceId);
        $designer = Designer::find($designerId);
        if (!$service || !$designer) send_error(404, 'Invalid designer or service.');

        $startTime = strlen($startTime) === 5 ? "$startTime:00" : $startTime;
        $endTime   = self::add_minutes($startTime, (int)$service['duration']);

        // Re-run availability (defense in depth)
        if (Appointment::count_conflicting($designerId, $date, $startTime, $endTime) > 0) {
            send_error(409, 'Selected slot is no longer available.');
        }
        // Designer working hours (lightweight check)
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $dow = (int)$d->format('w');
        $avail = DesignerAvailability::for_day($designerId, $dow);
        if (!$avail) send_error(409, 'Designer not available on this day.');
        if (self::time_lt($startTime, $avail['start_time']) || self::time_gt($endTime, $avail['end_time'])) {
            send_error(409, 'Outside designer hours.');
        }

        // SERVER computes price — never trust client
        $price = (float)$service['price'];

        $id = Appointment::create([
            'customer_id'      => (int)$u['id'],
            'salon_id'         => (int)$designer['salon_id'],
            'designer_id'      => $designerId,
            'service_id'       => $serviceId,
            'appointment_date' => $date,
            'start_time'       => $startTime,
            'end_time'         => $endTime,
            'normal_price'     => $price,
            'appointment_type' => $type,
            'status'           => $type === 'urgent' ? 'urgent_pending' : 'pending',
            'notes'            => $in['notes'] ?? null,
        ]);

        send_created('Appointment booked.', Appointment::find($id));
    }

    /** GET /api/appointments?action=index */
    public static function index(): void {
        $u = require_login();
        if ($u['role'] === 'customer') {
            send_ok('OK', ['appointments' => Appointment::for_customer((int)$u['id'])]);
        }
        if ($u['role'] === 'salon_admin') {
            $salon = Salon::find_by_owner((int)$u['id']);
            if (!$salon) send_ok('OK', ['appointments' => []]);
            send_ok('OK', ['appointments' => Appointment::for_salon((int)$salon['id'])]);
        }
        if ($u['role'] === 'designer') {
            send_ok('OK', ['appointments' => Appointment::for_designer_user((int)$u['id'])]);
        }
        send_error(403, 'Forbidden.');
    }

    /** GET /api/appointments?action=show&id= */
    public static function show(): void {
        $u  = require_login();
        $id = (int)($_GET['id'] ?? 0);
        $a  = Appointment::find($id);
        if (!$a) send_error(404, 'Appointment not found.');
        if (!self::user_can_see($u, $a)) send_error(403, 'Forbidden.');
        send_ok('OK', ['appointment' => $a]);
    }

    /** Generic helper for role-based status mutations */
    private static function mutate(int $id, array $allowed, callable $authorizer): void {
        $u = require_login();
        $a = Appointment::find($id);
        if (!$a) send_error(404, 'Appointment not found.');
        if (!self::user_can_see($u, $a)) send_error(403, 'Forbidden.');
        $authorizer($u, $a);
        send_ok('Status updated.', Appointment::find($id));
    }

    public static function accept():   { self::mutate((int)($_GET['id'] ?? 0), ['pending','urgent_pending'],
        function ($u, $a) {
            if (!in_array($u['role'], ['salon_admin','designer'], true)) {
                send_error(403, 'Forbidden.');
            }
            $new = $a['appointment_type'] === 'urgent' ? 'urgent_accepted' : 'accepted';
            Appointment::update_status((int)$a['id'], $new);
        });
    }

    public static function reject():   { self::mutate((int)($_GET['id'] ?? 0), ['pending','urgent_pending'],
        function ($u, $a) {
            if (!in_array($u['role'], ['salon_admin','designer'], true)) {
                send_error(403, 'Forbidden.');
            }
            $new = $a['appointment_type'] === 'urgent' ? 'urgent_rejected' : 'rejected';
            Appointment::update_status((int)$a['id'], $new);
        });
    }

    public static function complete(): { self::mutate((int)($_GET['id'] ?? 0), ['accepted','urgent_accepted'],
        function ($u, $a) {
            if (!in_array($u['role'], ['salon_admin','designer'], true)) {
                send_error(403, 'Forbidden.');
            }
            Appointment::update_status((int)$a['id'], 'completed');
        });
    }

    public static function cancel():   { self::mutate((int)($_GET['id'] ?? 0),
        ['pending','accepted','urgent_pending','urgent_accepted'],
        function ($u, $a) {
            if ($u['role'] !== 'customer' || (int)$a['customer_id'] !== (int)$u['id']) {
                send_error(403, 'Only the customer can cancel their appointment.');
            }
            Appointment::update_status((int)$a['id'], 'cancelled');
        });
    }

    /* ---- helpers ---- */
    private static function user_can_see(array $u, array $a): bool {
        return match ($u['role']) {
            'customer'    => (int)$a['customer_id'] === (int)$u['id'],
            'salon_admin' => true,                // narrowed via for_salon() query for listing
            'designer'    => true,                // narrowed via for_designer_user() query for listing
            default       => false,
        };
    }

    private static function add_minutes(string $hms, int $mins): string {
        $t = DateTime::createFromFormat('H:i:s', $hms) ?: DateTime::createFromFormat('H:i', $hms);
        $t->modify("+{$mins} minutes");
        return $t->format('H:i:s');
    }
    private static function time_lt(string $a, string $b): bool { return strcmp($a, $b) < 0; }
    private static function time_gt(string $a, string $b): bool { return strcmp($a, $b) > 0; }
}
