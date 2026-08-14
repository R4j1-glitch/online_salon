<?php
require_once __DIR__ . '/../config/database.php';

class Appointment {

    public static function create(array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO appointments
                (customer_id, salon_id, designer_id, service_id,
                 appointment_date, start_time, end_time, normal_price,
                 appointment_type, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['customer_id'], $d['salon_id'], $d['designer_id'], $d['service_id'],
            $d['appointment_date'], $d['start_time'], $d['end_time'], $d['normal_price'],
            $d['appointment_type'] ?? 'normal',
            $d['status']          ?? 'pending',
            $d['notes']           ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare(
            'SELECT a.*,
                    s.name   AS salon_name,
                    sv.name  AS service_name, sv.duration AS service_duration,
                    u.name   AS customer_name, u.email AS customer_email,
                    du.name  AS designer_user_name, du.email AS designer_user_email
             FROM appointments a
             JOIN salons   s  ON s.id  = a.salon_id
             JOIN services sv ON sv.id = a.service_id
             JOIN users    u  ON u.id  = a.customer_id
             JOIN designers d ON d.id = a.designer_id
             JOIN users    du ON du.id = d.user_id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Customer view */
    public static function for_customer(int $customerId): array {
        $stmt = db()->prepare(
            'SELECT a.*, s.name AS salon_name, sv.name AS service_name,
                    du.name AS designer_name
             FROM appointments a
             JOIN salons s ON s.id = a.salon_id
             JOIN services sv ON sv.id = a.service_id
             JOIN designers d ON d.id = a.designer_id
             JOIN users du ON du.id = d.user_id
             WHERE a.customer_id = ?
             ORDER BY a.appointment_date DESC, a.start_time DESC'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    /** Salon admin: appointments for the admin's salon(s) */
    public static function for_salon(int $salonId): array {
        $stmt = db()->prepare(
            'SELECT a.*, sv.name AS service_name,
                    u.name AS customer_name, du.name AS designer_name
             FROM appointments a
             JOIN services sv ON sv.id = a.service_id
             JOIN users u ON u.id = a.customer_id
             JOIN designers d ON d.id = a.designer_id
             JOIN users du ON du.id = d.user_id
             WHERE a.salon_id = ?
             ORDER BY a.appointment_date DESC, a.start_time DESC'
        );
        $stmt->execute([$salonId]);
        return $stmt->fetchAll();
    }

    /** Designer self-view */
    public static function for_designer_user(int $userId): array {
        $stmt = db()->prepare(
            'SELECT a.*, sv.name AS service_name, s.name AS salon_name,
                    u.name AS customer_name
             FROM appointments a
             JOIN services sv ON sv.id = a.service_id
             JOIN salons s   ON s.id  = a.salon_id
             JOIN users u    ON u.id  = a.customer_id
             JOIN designers d ON d.id = a.designer_id
             WHERE d.user_id = ?
             ORDER BY a.appointment_date DESC, a.start_time DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function update_status(int $id, string $status): bool {
        $stmt = db()->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    /** Returns the number of *blocking* appointments that overlap the slot. */
    public static function count_conflicting(
        int $designerId, string $date, string $startTime, string $endTime, ?int $exceptId = null
    ): int {
        $sql = 'SELECT COUNT(*) FROM appointments
                WHERE designer_id = ?
                  AND appointment_date = ?
                  AND status IN ("pending","accepted","urgent_pending","urgent_accepted")
                  AND start_time < ?
                  AND end_time   > ?';
        $args = [$designerId, $date, $endTime, $startTime];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $args[] = $exceptId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }
}
