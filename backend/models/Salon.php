<?php
require_once __DIR__ . '/../config/database.php';

class Salon {

    public static function all(): array {
        $stmt = db()->query('SELECT * FROM salons ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM salons WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find_by_owner(int $ownerId): ?array {
        $stmt = db()->prepare('SELECT * FROM salons WHERE owner_id = ? LIMIT 1');
        $stmt->execute([$ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int {
        $stmt = db()->prepare(
            'INSERT INTO salons (owner_id, name, description, address, phone, opening_time, closing_time)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['owner_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['address']     ?? null,
            $data['phone']       ?? null,
            $data['opening_time']?? '09:00:00',
            $data['closing_time']?? '19:00:00',
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $data): bool {
        $stmt = db()->prepare(
            'UPDATE salons SET name=?, description=?, address=?, phone=?,
                opening_time=?, closing_time=? WHERE id=? AND owner_id=?'
        );
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['address']     ?? null,
            $data['phone']       ?? null,
            $data['opening_time']?? '09:00:00',
            $data['closing_time']?? '19:00:00',
            $id, $ownerId,
        ]);
    }

    public static function delete(int $id, int $ownerId): bool {
        $stmt = db()->prepare('DELETE FROM salons WHERE id=? AND owner_id=?');
        return $stmt->execute([$id, $ownerId]);
    }
}
