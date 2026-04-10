<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/helpers.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

date_default_timezone_set((string) app_config('timezone', 'America/Sao_Paulo'));
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

\App\Core\Session::start();
