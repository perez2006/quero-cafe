<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Models\AbsenceRepository;
use App\Models\AuditLogRepository;
use App\Models\RecordRepository;
use App\Models\ScheduleRepository;
use App\Models\ScheduleResolutionRepository;
use App\Models\UserRepository;
use App\Services\AuditLogger;
use App\Services\DashboardService;
use App\Services\ScheduleSuggestionService;

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$basePath = (string) app_config('base_url', '');

if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}

$route = trim((string) $requestPath, '/');
if ($route === 'index.php') {
    $route = '';
}
if (str_ends_with($route, '.php')) {
    $route = substr($route, 0, -4);
}

$userRepository = new UserRepository();
$recordRepository = new RecordRepository();
$scheduleRepository = new ScheduleRepository();
$absenceRepository = new AbsenceRepository();
$auditLogRepository = new AuditLogRepository();
$scheduleResolutionRepository = new ScheduleResolutionRepository();
$auditLogger = new AuditLogger($auditLogRepository);
$scheduleSuggestionService = new ScheduleSuggestionService($userRepository, $scheduleRepository, $absenceRepository, $scheduleResolutionRepository);
$dashboardService = new DashboardService($recordRepository, $userRepository, $absenceRepository);

$routes = [
    '' => static fn () => (new DashboardController($recordRepository, $dashboardService, $scheduleSuggestionService, $auditLogger))->index(),
    'login' => static fn () => (new AuthController($userRepository))->login(),
    'logout' => static fn () => (new AuthController($userRepository))->logout(),
    'config' => static fn () => (new SettingsController($scheduleRepository, $recordRepository, $absenceRepository, $userRepository, $auditLogRepository, $scheduleSuggestionService, $auditLogger))->index(),
    'usuarios' => static fn () => (new UserController($userRepository, $auditLogger))->index(),
];

if (!isset($routes[$route])) {
    http_response_code(404);
    echo 'Pagina nao encontrada.';
    exit;
}

$routes[$route]();
