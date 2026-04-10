<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT username, nome, senha, consumo_dia FROM usuarios ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function quotaUsers(): array
    {
        return array_map(static function (array $user): array {
            $user['consumo_dia'] = max(0, (int) ($user['consumo_dia'] ?? 0));
            return $user;
        }, $this->all());
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT username, nome, senha, consumo_dia FROM usuarios WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function save(string $originalUsername, array $payload): void
    {
        $this->db->beginTransaction();

        try {
            $existing = $originalUsername !== '' ? $this->findByUsername($originalUsername) : null;

            if ($existing !== null) {
                $stmt = $this->db->prepare('DELETE FROM usuarios WHERE username = ?');
                $stmt->execute([$originalUsername]);
            }

            $stmt = $this->db->prepare('INSERT INTO usuarios (username, nome, senha, consumo_dia) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $payload['username'],
                $payload['nome'],
                $payload['senha'] !== '' ? password_hash($payload['senha'], PASSWORD_DEFAULT) : (string) ($existing['senha'] ?? ''),
                (int) $payload['consumo_dia'],
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete(string $username): void
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE username = ?');
        $stmt->execute([$username]);
    }

    public function upgradePasswordHash(string $username, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET senha = ? WHERE username = ?');
        $stmt->execute([$hash, $username]);
    }
}
