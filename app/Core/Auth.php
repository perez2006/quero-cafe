<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UserRepository;

final class Auth
{
    public static function user(): ?array
    {
        return Session::get('auth_user');
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireUser(): array
    {
        $user = self::user();

        if ($user === null) {
            redirect_to('login');
        }

        return $user;
    }

    public static function attempt(string $username, string $password, UserRepository $users): bool
    {
        $username = mb_strtolower(trim($username), 'UTF-8');
        $password = trim($password);

        if ($username === '' || $password === '') {
            return false;
        }

        $user = $users->findByUsername($username);

        if ($user === null) {
            return false;
        }

        $storedPassword = (string) ($user['senha'] ?? '');
        $passwordMatches = password_get_info($storedPassword)['algo'] !== null
            ? password_verify($password, $storedPassword)
            : hash_equals($storedPassword, $password);

        if (!$passwordMatches) {
            [$ldapSuccess] = LdapAuthenticator::authenticate($username, $password);
            if (!$ldapSuccess) {
                return false;
            }
        }

        if (password_get_info($storedPassword)['algo'] === null) {
            $users->upgradePasswordHash($username, password_hash($password, PASSWORD_DEFAULT));
        }

        Session::put('auth_user', [
            'username' => $user['username'],
            'nome' => $user['nome'],
        ]);

        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
