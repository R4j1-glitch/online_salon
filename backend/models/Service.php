<?php
require_once __DIR__ . '/../config/database.php';

class Service {

    public static function by_salon(int $salonId): array {
        $stmt = db()->prepare(
            'SELECT * FROM services WHERE salon_id = ? ORDER BY id DESC'
        );
        $stmt->execute([$salonId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO services (salon_id, name, description, price, duration, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['salon_id'], $d['name'], $d['description'] ?? null,
            $d['price'], $d['duration'] ?? 30, $d['status'] ?? 'active',
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $salonId, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE services SET name=?, description=?, price=?, duration=?, status=?
             WHERE id=? AND salon_id=?'
        );
        return $stmt->execute([
            $d['name'], $d['description'] ?? null,
            $d['price'], $d['duration'] ?? 30, $d['status'] ?? 'active',
            $id, $salonId,
        ]);
    }

    public static function delete(int $id, int $salonId): bool {
        $stmt = db()->prepare('DELETE FROM services WHERE id=? AND salon_id=?');
        return $stmt->execute([$id, $salonId]);
    }

    /** Verify the salon_id actually belongs to the given owner. */
    public static function salon_owned_by(int $salonId, int $ownerId): bool {
        $stmt = db()->prepare('SELECT 1 FROM salons WHERE id=? AND owner_id=?');
        $stmt->execute([$salonId, $ownerId]);
        return (bool)$stmt->fetchColumn();
    }
}
