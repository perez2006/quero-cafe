<?php
declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../Config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_name(string $name): string
{
    return mb_strtolower((string) preg_replace('/\s+/', ' ', trim($name)), 'UTF-8');
}

function is_valid_date(string $date): bool
{
    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);

    return $dateTime !== false && $dateTime->format('Y-m-d') === $date;
}

function format_weight(int $grams): string
{
    if ($grams >= 1000) {
        return number_format($grams / 1000, 2, ',', '.') . ' kg';
    }

    return $grams . ' g';
}

function month_label(string $month): string
{
    $monthNames = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Marco',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro',
    ];

    return ($monthNames[substr($month, 5, 2)] ?? $month) . ' / ' . substr($month, 0, 4);
}

function month_start(string $month): string
{
    return $month . '-01';
}

function month_end(string $month): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');

    return $date === false ? $month . '-31' : $date->modify('last day of this month')->format('Y-m-d');
}

function weekday_dates_for_current_week(): array
{
    $start = new DateTimeImmutable('monday this week');
    $days = [];

    for ($index = 0; $index < 5; $index++) {
        $date = $start->modify('+' . $index . ' day');
        $days[$date->format('N')] = $date->format('Y-m-d');
    }

    return [
        'monday' => $days[1] ?? $start->format('Y-m-d'),
        'tuesday' => $days[2] ?? $start->modify('+1 day')->format('Y-m-d'),
        'wednesday' => $days[3] ?? $start->modify('+2 day')->format('Y-m-d'),
        'thursday' => $days[4] ?? $start->modify('+3 day')->format('Y-m-d'),
        'friday' => $days[5] ?? $start->modify('+4 day')->format('Y-m-d'),
    ];
}

function format_datetime(string $value): string
{
    try {
        return (new DateTimeImmutable($value))->format('d/m/Y H:i');
    } catch (Throwable) {
        return $value;
    }
}

function count_weekdays_between(string $startDate, string $endDate): int
{
    try {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
    } catch (Throwable) {
        return 0;
    }

    if ($end < $start) {
        return 0;
    }

    $count = 0;
    for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 day')) {
        $dayNumber = (int) $cursor->format('N');
        if ($dayNumber <= 5) {
            $count++;
        }
    }

    return $count;
}

function base_url(string $path = ''): string
{
    $baseUrl = (string) app_config('base_url', '');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $baseUrl === '' ? '/' : $baseUrl . '/';
    }

    return ($baseUrl === '' ? '' : $baseUrl) . '/' . $trimmedPath;
}

function redirect_to(string $path = ''): never
{
    header('Location: ' . base_url($path));
    exit;
}

function asset_url(string $path): string
{
    $url = base_url($path);
    $fullPath = rtrim((string) app_config('root_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));

    if (is_file($fullPath)) {
        return $url . '?v=' . filemtime($fullPath);
    }

    return $url;
}
