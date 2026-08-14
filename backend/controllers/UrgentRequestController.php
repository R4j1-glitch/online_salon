<?php
require_once __DIR__ . '/../models/UrgentRequest.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Salon.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Designer.php';
require_once __DIR__ . '/../models/DesignerAvailability.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class UrgentRequestController {

    /**
     * POST /api/urgent-requests?action=store
     * Customer creates an urgent appointment even when slot is taken.
     * Body: { designer_id, service_id, date, start_time, extra_offer, message }
     */
    public static function store(): void {
        $u  = require_role('customer');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $designerId = (int)($in['designer_id'] ?? 0);
        $serviceId  = (int)($in['service_id']  ?? 0);
        $date       =       $in['date']        ?? '';
        $startTime  =       $in['start_time']  ?? '';
        $extra      = (float)($in['extra_offer'] ?? 0);
        $message    =       $in['message']     ?? null;

        if (!$designerId || !$serviceId || $date === '' || $startTime === '') {
            send_error(422, 'designer_id, service_id, date and start_time are required.');
        }
        if ($extra < 0) send_error(422, 'extra_offer must be non-negative.');

        $service  = Service::find($serviceId);
        $designer = Designer::find($designerId);
        if (!$service || !$designer) send_error(404, 'Invalid designer or service.');

        // Designer must at least be working that day (so the urgent request makes sense)
        $d   = DateTime::createFromFormat('Y-m-d', $date);
        $dow = (int)$d->format('w');
        $avail = DesignerAvailability::for_day($designerId, $dow);
        if (!$avail) send_error(409, 'Designer does not work on this day.');

        $start = strlen($startTime) === 5 ? "$startTime:00" : $startTime;
        $end   = (new DateTime($start))->modify("+{$service['duration']} minutes")->format('H:i:s');

        // Salon hours sanity
        $salon = Salon::find((int)$designer['salon_id']);
        if (strcmp($start, $salon['opening_time']) < 0 || strcmp($end, $salon['closing_time']) > 0) {
            send_error(409, 'Outside salon operating hours.');
        }

        $price = (float)$service['price']; // server-trusted price

        // Create the underlying appointment as urgent_pending (does NOT block the slot)
        $apptId = Appointment::create([
            'customer_id'      => (int)$u['id'],
            'salon_id'         => (int)$designer['salon_id'],
            'designer_id'      => $designerId,
            'service_id'       => $serviceId,
            'appointment_date' => $date,
            'start_time'       => $start,
            'end_time'         => $end,
            'normal_price'     => $price,
            'appointment_type' => 'urgent',
            'status'           => 'urgent_pending',
            'notes'            => null,
        ]);

        $urId = UrgentRequest::create([
            'appointment_id'       => $apptId,
            'customer_id'          => (int)$u['id'],
            'designer_id'          => $designerId,
            'original_price'       => $price,
            'customer_extra_offer' => $extra,
            'status'               => 'customer_proposed',
            'message'              => $message,
        ]);

        send_created('Urgent request submitted.', UrgentRequest::find($urId));
    }

    /** GET /api/urgent-requests?action=index  (role-aware) */
    public static function index(): void {
        $u = require_login();
        if ($u['role'] === 'customer') {
            send_ok('OK', ['urgent_requests' => UrgentRequest::for_customer((int)$u['id'])]);
        }
        if ($u['role'] === 'salon_admin') {
            $salon = Salon::find_by_owner((int)$u['id']);
            if (!$salon) send_ok('OK', ['urgent_requests' => []]);
            send_ok('OK', ['urgent_requests' => UrgentRequest::for_salon((int)$salon['id'])]);
        }
        if ($u['role'] === 'designer') {
            send_ok('OK', ['urgent_requests' => UrgentRequest::for_designer_user((int)$u['id'])]);
        }
        send_error(403, 'Forbidden.');
    }

    /** PUT /api/urgent-requests?action=counter-offer&id=  (salon admin / designer counters) */
    public static function counter_offer(): void {
        $u  = require_role('salon_admin', 'designer');
        $id = (int)($_GET['id'] ?? 0);
        $in = json_decode(file_get_contents('php://input'), true) ?? [];
        $extra = (float)($in['extra_offer'] ?? -1);
        if ($extra < 0) send_error(422, 'extra_offer is required and must be non-negative.');

        $r = UrgentRequest::find($id);
        if (!$r) send_error(404, 'Urgent request not found.');
        if (!in_array($r['status'], ['customer_proposed', 'salon_countered'], true)) {
            send_error(409, 'Counter-offer not allowed in current status.');
        }

        UrgentRequest::update_proposal($id, $extra, 'salon', 'salon_countered');
        send_ok('Counter-offer sent.', UrgentRequest::find($id));
    }

    /** PUT /api/urgent-requests?action=accept&id= (customer accepts latest salon counter) */
    public static function accept(): void {
        $u  = require_login();
        $id = (int)($_GET['id'] ?? 0);
        $r  = UrgentRequest::find($id);
        if (!$r) send_error(404, 'Urgent request not found.');

        // Only the customer can accept; and only from customer_proposed or salon_countered
        if ($u['role'] !== 'customer' || (int)$r['customer_id'] !== (int)$u['id']) {
            send_error(403, 'Only the customer can accept.');
        }
        if (!in_array($r['status'], ['customer_proposed', 'salon_countered'], true)) {
            send_error(409, 'Cannot accept in current status.');
        }

        // Final price = original + salon_extra_offer (if countered) else customer_extra_offer
        $final = (float)$r['original_price']
               + (float)((float)$r['salon_extra_offer'] > 0 ? $r['salon_extra_offer'] : $r['customer_extra_offer']);

        UrgentRequest::update_status($id, 'accepted', $final);
        Appointment::update_status((int)$r['appointment_id'], 'urgent_accepted');

        send_ok('Urgent request accepted.', UrgentRequest::find($id));
    }

    /** PUT /api/urgent-requests?action=reject&id=  (salon/designer or customer) */
    public static function reject(): void {
        $u  = require_login();
        $id = (int)($_GET['id'] ?? 0);
        $r  = UrgentRequest::find($id);
        if (!$r) send_error(404, 'Urgent request not found.');

        $isCustomer    = $u['role'] === 'customer'    && (int)$r['customer_id'] === (int)$u['id'];
        $isSalonSide   = in_array($u['role'], ['salon_admin', 'designer'], true);
        if (!$isCustomer && !$isSalonSide) send_error(403, 'Forbidden.');

        UrgentRequest::update_status($id, 'rejected');
        Appointment::update_status((int)$r['appointment_id'], 'urgent_rejected');
        send_ok('Urgent request rejected.', UrgentRequest::find($id));
    }
}
