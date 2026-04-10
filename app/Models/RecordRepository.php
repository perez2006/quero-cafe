<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RecordRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM registros ORDER BY data DESC, id DESC');
        return $stmt->fetchAll();
    }

    public function create(array $payload): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO registros (data, tipo, nome, cafe, quantidade, observacao) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payload['data'],
            $payload['tipo'],
            $payload['nome'],
            $payload['cafe'],
            $payload['quantidade'],
            $payload['observacao'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM registros WHERE id = ?');
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        return $record ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM registros WHERE id = ?');
        $stmt->execute([$id]);
    }
}
