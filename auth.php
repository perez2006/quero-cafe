<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Models\UserRepository;

function getDbConnection(): PDO
{
    return Database::connection();
}

function currentUser(): ?array
{
    return Auth::user();
}

function appUsers(): array
{
    $repository = new UserRepository();
    $users = [];

    foreach ($repository->all() as $user) {
        $users[$user['username']] = $user;
    }

    return $users;
}

function saveUsers(array $users): bool
{
    $repository = new UserRepository();

    try {
        foreach ($users as $username => $user) {
            $repository->save((string) $username, [
                'username' => (string) $username,
                'nome' => (string) ($user['nome'] ?? ''),
                'senha' => (string) ($user['senha'] ?? ''),
                'consumo_dia' => (int) ($user['consumo_dia'] ?? 0),
            ]);
        }

        return true;
    } catch (Throwable $exception) {
        error_log('Erro ao salvar usuarios: ' . $exception->getMessage());
        return false;
    }
}

function attemptLogin(string $username, string $password): bool
{
    return Auth::attempt($username, $password, new UserRepository());
}

function requireAuth(): array
{
    return Auth::requireUser();
}

function logoutUser(): void
{
    Auth::logout();
}

function appQuotaUsers(): array
{
    $users = [];
    foreach ((new UserRepository())->quotaUsers() as $user) {
        $users[$user['username']] = $user;
    }

    return $users;
}
