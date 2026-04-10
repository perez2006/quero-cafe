<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AbsenceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT ua.id, ua.username, ua.start_date, ua.end_date, ua.reason, ua.created_at, u.nome
             FROM user_absences ua
             LEFT JOIN usuarios u ON u.username = ua.username
             ORDER BY ua.start_date DESC, ua.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function activeOn(string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT ua.id, ua.username, ua.start_date, ua.end_date, ua.reason, ua.created_at, u.nome
             FROM user_absences ua
             LEFT JOIN usuarios u ON u.username = ua.username
             WHERE ua.start_date <= ? AND ua.end_date >= ?
             ORDER BY u.nome, ua.username'
        );
        $stmt->execute([$date, $date]);

        return $stmt->fetchAll();
    }

    public function allForMonth(string $month): array
    {
        $startDate = month_start($month);
        $endDate = month_end($month);
        $stmt = $this->db->prepare(
            'SELECT ua.id, ua.username, ua.start_date, ua.end_date, ua.reason, ua.created_at, u.nome
             FROM user_absences ua
             LEFT JOIN usuarios u ON u.username = ua.username
             WHERE ua.start_date <= ? AND ua.end_date >= ?
             ORDER BY ua.start_date, ua.id'
        );
        $stmt->execute([$endDate, $startDate]);

        return $stmt->fetchAll();
    }

    public function create(array $payload): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_absences (username, start_date, end_date, reason, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payload['username'],
            $payload['start_date'],
            $payload['end_date'],
            $payload['reason'],
            $payload['created_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_absences WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM user_absences WHERE id = ?');
        $stmt->execute([$id]);
    }
}
