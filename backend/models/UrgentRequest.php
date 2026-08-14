<?php
require_once __DIR__ . '/../config/database.php';

class UrgentRequest {

    public static function create(array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO urgent_requests
                (appointment_id, customer_id, designer_id,
                 original_price, customer_extra_offer, salon_extra_offer, final_price,
                 status, message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['appointment_id'], $d['customer_id'], $d['designer_id'],
            $d['original_price'], $d['customer_extra_offer'] ?? 0.00,
            $d['salon_extra_offer']    ?? 0.00,
            $d['final_price']          ?? 0.00,
            $d['status']               ?? 'customer_proposed',
            $d['message']              ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare(
            'SELECT ur.*,
                    a.appointment_date, a.start_time, a.end_time,
                    u.name  AS customer_name,
                    du.name AS designer_user_name,
                    sv.name AS service_name, s.name AS salon_name
             FROM urgent_requests ur
             JOIN appointments a  ON a.id  = ur.appointment_id
             JOIN users u         ON u.id  = ur.customer_id
             JOIN designers d     ON d.id  = ur.designer_id
             JOIN users du        ON du.id = d.user_id
             JOIN services sv     ON sv.id = a.service_id
             JOIN salons s        ON s.id  = a.salon_id
             WHERE ur.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function for_customer(int $customerId): array {
        $stmt = db()->prepare(
            'SELECT ur.*, a.appointment_date, a.start_time, sv.name AS service_name, s.name AS salon_name
             FROM urgent_requests ur
             JOIN appointments a ON a.id = ur.appointment_id
             JOIN services sv ON sv.id = a.service_id
             JOIN salons s    ON s.id  = a.salon_id
             WHERE ur.customer_id = ?
             ORDER BY ur.id DESC'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function for_salon(int $salonId): array {
        $stmt = db()->prepare(
            'SELECT ur.*, a.appointment_date, a.start_time, sv.name AS service_name,
                    u.name AS customer_name, du.name AS designer_name
             FROM urgent_requests ur
             JOIN appointments a ON a.id = ur.appointment_id
             JOIN services sv ON sv.id = a.service_id
             JOIN users u    ON u.id  = ur.customer_id
             JOIN designers d ON d.id = ur.designer_id
             JOIN users du   ON du.id = d.user_id
             WHERE a.salon_id = ?
             ORDER BY ur.id DESC'
        );
        $stmt->execute([$salonId]);
        return $stmt->fetchAll();
    }

    public static function for_designer_user(int $userId): array {
        $stmt = db()->prepare(
            'SELECT ur.*, a.appointment_date, a.start_time, sv.name AS service_name,
                    u.name AS customer_name, s.name AS salon_name
             FROM urgent_requests ur
             JOIN appointments a ON a.id = ur.appointment_id
             JOIN services sv ON sv.id = a.service_id
             JOIN users u    ON u.id  = ur.customer_id
             JOIN designers d ON d.id = ur.designer_id
             JOIN salons s   ON s.id  = a.salon_id
             WHERE d.user_id = ?
             ORDER BY ur.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function update_proposal(int $id, float $extra, string $by, string $newStatus): bool {
        $col = $by === 'customer' ? 'customer_extra_offer' : 'salon_extra_offer';
        $stmt = db()->prepare(
            "UPDATE urgent_requests SET $col = ?, status = ? WHERE id = ?"
        );
        return $stmt->execute([$extra, $newStatus, $id]);
    }

    public static function update_status(int $id, string $status, ?float $final = null): bool {
        if ($final !== null) {
            $stmt = db()->prepare('UPDATE urgent_requests SET status=?, final_price=? WHERE id=?');
            return $stmt->execute([$status, $final, $id]);
        }
        $stmt = db()->prepare('UPDATE urgent_requests SET status=? WHERE id=?');
        return $stmt->execute([$status, $id]);
    }
}
