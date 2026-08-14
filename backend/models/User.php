<?php
require_once __DIR__ . '/../config/database.php';

class User {
    public static function find_by_email(string $email): ?array {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array {
        $stmt = db()->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $hash,
            $data['role'],
        ]);
        return (int)db()->lastInsertId();
    }

    public static function email_exists(string $email): bool {
        return self::find_by_email($email) !== null;
    }
}
