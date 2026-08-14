<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';

class Designer {

    public static function all(): array {
        $sql = 'SELECT d.*, s.name AS salon_name, u.name AS user_name, u.email AS user_email
                FROM designers d
                JOIN salons s ON s.id = d.salon_id
                JOIN users   u ON u.id = d.user_id
                ORDER BY d.id DESC';
        return db()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare(
            'SELECT d.*, s.name AS salon_name, u.name AS user_name, u.email AS user_email
             FROM designers d
             JOIN salons s ON s.id = d.salon_id
             JOIN users   u ON u.id = d.user_id
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function by_salon(int $salonId): array {
        $stmt = db()->prepare(
            'SELECT d.*, u.name AS user_name, u.email AS user_email
             FROM designers d
             JOIN users u ON u.id = d.user_id
             WHERE d.salon_id = ?
             ORDER BY d.id DESC'
        );
        $stmt->execute([$salonId]);
        return $stmt->fetchAll();
    }

    public static function find_by_user(int $userId): ?array {
        $stmt = db()->prepare('SELECT * FROM designers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO designers (salon_id, user_id, specialization, description, profile_image, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['salon_id'], $d['user_id'],
            $d['specialization'] ?? null,
            $d['description']    ?? null,
            $d['profile_image']  ?? null,
            $d['status']         ?? 'active',
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $salonId, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE designers SET specialization=?, description=?, profile_image=?, status=?
             WHERE id=? AND salon_id=?'
        );
        return $stmt->execute([
            $d['specialization'] ?? null,
            $d['description']    ?? null,
            $d['profile_image']  ?? null,
            $d['status']         ?? 'active',
            $id, $salonId,
        ]);
    }

    public static function delete(int $id, int $salonId): bool {
        $stmt = db()->prepare('DELETE FROM designers WHERE id=? AND salon_id=?');
        return $stmt->execute([$id, $salonId]);
    }

    /**
     * Create a designer plus the underlying user account atomically.
     * Used by salon_admin.
     */
    public static function create_with_user(array $data): array {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if (User::email_exists($data['email'])) {
                throw new RuntimeException('Email already exists.');
            }
            $uid = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'] ?? null,
                'password' => $data['password'] ?? 'designer123',
                'role'     => 'designer',
            ]);
            $did = self::create([
                'salon_id'       => (int)$data['salon_id'],
                'user_id'        => $uid,
                'specialization' => $data['specialization'] ?? null,
                'description'    => $data['description']    ?? null,
                'status'         => $data['status']         ?? 'active',
            ]);
            $pdo->commit();
            return ['user_id' => $uid, 'designer_id' => $did];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /* ---- designer_services ---- */
    public static function set_services(int $designerId, array $serviceIds): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM designer_services WHERE designer_id = ?')
                ->execute([$designerId]);
            $stmt = $pdo->prepare(
                'INSERT INTO designer_services (designer_id, service_id) VALUES (?, ?)'
            );
            foreach ($serviceIds as $sid) {
                $stmt->execute([$designerId, (int)$sid]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function service_ids(int $designerId): array {
        $stmt = db()->prepare('SELECT service_id FROM designer_services WHERE designer_id = ?');
        $stmt->execute([$designerId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
