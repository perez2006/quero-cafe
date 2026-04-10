<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLogRepository;

final class AuditLogger
{
    public function __construct(
        private readonly AuditLogRepository $logs
    ) {
    }

    public function log(array $actor, string $actionType, string $entityType, string $entityId, string $description, array $payload = []): void
    {
        $this->logs->create([
            'actor_username' => (string) ($actor['username'] ?? ''),
            'actor_name' => (string) ($actor['nome'] ?? ''),
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'payload_json' => $payload === [] ? '' : (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('c'),
        ]);
    }
}
