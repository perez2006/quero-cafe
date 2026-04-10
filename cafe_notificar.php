<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Models\ScheduleRepository;
use App\Models\ScheduleResolutionRepository;
use App\Services\NotificationService;
use App\Services\ScheduleSuggestionService;
use App\Models\UserRepository;
use App\Models\AbsenceRepository;

$period = strtolower(trim((string) ($argv[1] ?? '')));
$scheduleRepository = new ScheduleRepository();
$scheduleSuggestions = new ScheduleSuggestionService(new UserRepository(), $scheduleRepository, new AbsenceRepository(), new ScheduleResolutionRepository());
[$success, $message] = (new NotificationService($scheduleRepository, $scheduleSuggestions))->notify($period);

$stream = $success ? STDOUT : STDERR;
fwrite($stream, sprintf("[%s] %s\n", date('c'), $message));

if (!$success) {
    exit(1);
}
