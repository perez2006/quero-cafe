<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;
    private static bool $schemaEnsured = false;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $databasePath = self::resolveDatabasePath();
            $dsn = 'sqlite:' . $databasePath;
            self::$connection = new PDO($dsn);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        if (!self::$schemaEnsured) {
            self::ensureSchema(self::$connection);
            self::$schemaEnsured = true;
        }

        return self::$connection;
    }

    private static function resolveDatabasePath(): string
    {
        $configuredPath = (string) app_config('database.path');
        $legacyPath = (string) app_config('database.legacy_path', '');
        $directory = dirname($configuredPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0770, true);
        }

        if (!is_file($configuredPath) && $legacyPath !== '' && is_file($legacyPath)) {
            copy($legacyPath, $configuredPath);
        }

        return $configuredPath;
    }

    private static function ensureSchema(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS usuarios (
                username TEXT PRIMARY KEY,
                nome TEXT NOT NULL,
                senha TEXT NOT NULL,
                consumo_dia INTEGER DEFAULT 1
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS registros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                data TEXT NOT NULL,
                tipo TEXT NOT NULL,
                nome TEXT NOT NULL,
                cafe TEXT NOT NULL,
                quantidade TEXT NOT NULL,
                observacao TEXT DEFAULT ""
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS escala (
                dia TEXT PRIMARY KEY,
                manha TEXT,
                tarde TEXT
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS user_absences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                reason TEXT NOT NULL DEFAULT "",
                created_at TEXT NOT NULL
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_username TEXT NOT NULL,
                actor_name TEXT NOT NULL,
                action_type TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL DEFAULT "",
                description TEXT NOT NULL,
                payload_json TEXT NOT NULL DEFAULT "",
                created_at TEXT NOT NULL
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS schedule_resolution_cache (
                week_start TEXT NOT NULL,
                day_key TEXT NOT NULL,
                period_key TEXT NOT NULL,
                resolved_name TEXT NOT NULL,
                resolved_username TEXT NOT NULL DEFAULT "",
                source_value TEXT NOT NULL DEFAULT "",
                created_at TEXT NOT NULL,
                PRIMARY KEY (week_start, day_key, period_key)
            )'
        );

        $db->exec('CREATE INDEX IF NOT EXISTS idx_user_absences_username_dates ON user_absences (username, start_date, end_date)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs (entity_type, entity_id, created_at DESC)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs (created_at DESC)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_schedule_resolution_cache_week ON schedule_resolution_cache (week_start)');
    }
}
