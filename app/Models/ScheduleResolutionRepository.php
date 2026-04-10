<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ScheduleResolutionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forWeek(string $weekStart): array
    {
        $stmt = $this->db->prepare(
            'SELECT week_start, day_key, period_key, resolved_name, resolved_username, source_value, created_at
             FROM schedule_resolution_cache
             WHERE week_start = ?
             ORDER BY day_key, period_key'
        );
        $stmt->execute([$weekStart]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[$row['day_key']][$row['period_key']] = $row;
        }

        return $rows;
    }

    public function replaceWeek(string $weekStart, array $resolvedSchedule, array $sourceSchedule, array $usersByName): void
    {
        $this->db->beginTransaction();

        try {
            $delete = $this->db->prepare('DELETE FROM schedule_resolution_cache WHERE week_start = ?');
            $delete->execute([$weekStart]);

            $insert = $this->db->prepare(
                'INSERT INTO schedule_resolution_cache (week_start, day_key, period_key, resolved_name, resolved_username, source_value, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($resolvedSchedule as $dayKey => $periods) {
                foreach ($periods as $periodKey => $resolvedName) {
                    $normalized = normalize_name((string) $resolvedName);
                    $user = $usersByName[$normalized] ?? null;
                    $insert->execute([
                        $weekStart,
                        $dayKey,
                        $periodKey,
                        (string) $resolvedName,
                        (string) ($user['username'] ?? ''),
                        (string) ($sourceSchedule[$dayKey][$periodKey] ?? ''),
                        date('c'),
                    ]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function recentCounts(int $weeks = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT resolved_username, COUNT(*) AS total
             FROM schedule_resolution_cache
             WHERE week_start >= ?
             AND resolved_username <> ""
             GROUP BY resolved_username'
        );
        $threshold = (new \DateTimeImmutable('monday this week'))->modify('-' . max(1, $weeks) . ' weeks')->format('Y-m-d');
        $stmt->execute([$threshold]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['resolved_username']] = (int) $row['total'];
        }

        return $counts;
    }
}
