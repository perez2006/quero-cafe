<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ScheduleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $schedule = [];
        $stmt = $this->db->query('SELECT dia, manha, tarde FROM escala');

        foreach ($stmt->fetchAll() as $row) {
            $schedule[$row['dia']] = ['manha' => $row['manha'], 'tarde' => $row['tarde']];
        }

        return $schedule;
    }

    public function replaceAll(array $schedule): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $this->db->beginTransaction();

        try {
            $this->db->exec('DELETE FROM escala');
            $stmt = $this->db->prepare('INSERT INTO escala (dia, manha, tarde) VALUES (?, ?, ?)');

            foreach ($days as $day) {
                $row = $schedule[$day] ?? [];
                $stmt->execute([$day, trim((string) ($row['manha'] ?? '')), trim((string) ($row['tarde'] ?? ''))]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function forDay(string $dayKey): ?array
    {
        $stmt = $this->db->prepare('SELECT dia, manha, tarde FROM escala WHERE dia = ?');
        $stmt->execute([$dayKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
