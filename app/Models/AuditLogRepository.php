<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AuditLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $payload): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (actor_username, actor_name, action_type, entity_type, entity_id, description, payload_json, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payload['actor_username'],
            $payload['actor_name'],
            $payload['action_type'],
            $payload['entity_type'],
            $payload['entity_id'],
            $payload['description'],
            $payload['payload_json'],
            $payload['created_at'],
        ]);
    }

    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
