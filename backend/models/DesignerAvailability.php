<?php
require_once __DIR__ . '/../config/database.php';

class DesignerAvailability {

    /** Return weekly rows for a designer. */
    public static function list(int $designerId): array {
        $stmt = db()->prepare(
            'SELECT * FROM designer_availability WHERE designer_id = ? ORDER BY day_of_week, start_time'
        );
        $stmt->execute([$designerId]);
        return $stmt->fetchAll();
    }

    /** Replace the weekly schedule wholesale for a designer. */
    public static function replace(int $designerId, array $rows): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM designer_availability WHERE designer_id = ?')
                ->execute([$designerId]);

            $ins = $pdo->prepare(
                'INSERT INTO designer_availability (designer_id, day_of_week, start_time, end_time, is_available)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($rows as $r) {
                $ins->execute([
                    $designerId,
                    (int)$r['day_of_week'],
                    $r['start_time'],
                    $r['end_time'],
                    (int)($r['is_available'] ?? 1),
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Convenience: returns the availability row for a specific weekday, if any. */
    public static function for_day(int $designerId, int $dayOfWeek): ?array {
        $stmt = db()->prepare(
            'SELECT * FROM designer_availability
             WHERE designer_id = ? AND day_of_week = ? AND is_available = 1
             ORDER BY start_time LIMIT 1'
        );
        $stmt->execute([$designerId, $dayOfWeek]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
