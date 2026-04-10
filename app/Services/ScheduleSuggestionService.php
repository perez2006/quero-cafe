<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AbsenceRepository;
use App\Models\ScheduleRepository;
use App\Models\ScheduleResolutionRepository;
use App\Models\UserRepository;

final class ScheduleSuggestionService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly AbsenceRepository $absences,
        private readonly ScheduleResolutionRepository $resolutionCache
    ) {
    }

    public function resolveWeeklySchedule(?array $rawSchedule = null): array
    {
        $rawSchedule ??= $this->scheduleRepository->all();
        $weekStart = (new \DateTimeImmutable('monday this week'))->format('Y-m-d');
        $cached = $this->resolutionCache->forWeek($weekStart);
        $fallback = $this->buildNewWeeklyResolution($rawSchedule);

        if ($cached !== []) {
            return $this->rebuildFromCache($cached, $rawSchedule, $fallback);
        }

        $resolved = $fallback;
        [$usersByName] = $this->buildUserMaps();
        $this->resolutionCache->replaceWeek($weekStart, $resolved, $rawSchedule, $usersByName);

        return $resolved;
    }

    public function buildCurrentSuggestionCard(): array
    {
        $weekDates = weekday_dates_for_current_week();
        $dayNumber = (int) date('N');
        $period = (int) date('G') < 12 ? 'manha' : 'tarde';
        $dayKey = match ($dayNumber) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            default => '',
        };

        if ($dayKey === '' || !isset($weekDates[$dayKey])) {
            return ['label' => 'Fim de semana', 'name' => 'Sem responsavel sugerido'];
        }

        $resolved = $this->resolveWeeklySchedule();

        return [
            'label' => sprintf('%s de %s', ucfirst($period), date('d/m')),
            'name' => (string) ($resolved[$dayKey][$period] ?? 'Sem responsavel sugerido'),
        ];
    }

    public function resolvePersonFor(string $dayKey, string $period): string
    {
        $resolved = $this->resolveWeeklySchedule();

        return trim((string) ($resolved[$dayKey][$period] ?? ''));
    }

    private function buildNewWeeklyResolution(array $rawSchedule): array
    {
        [$usersByName, $usersByUsername] = $this->buildUserMaps();
        $weekDates = weekday_dates_for_current_week();
        $periods = ['manha', 'tarde'];
        $historicalCounts = $this->resolutionCache->recentCounts(8);
        $assignmentScore = [];

        foreach ($usersByUsername as $username => $user) {
            $assignmentScore[$username] = (int) ($historicalCounts[$username] ?? 0);
        }

        $resolved = [];
        foreach ($weekDates as $dayKey => $date) {
            $activeAbsences = $this->absences->activeOn($date);
            $absent = [];
            foreach ($activeAbsences as $absence) {
                $absent[(string) $absence['username']] = true;
            }

            $dailyAssigned = [];
            foreach ($periods as $period) {
                $rawValue = trim((string) ($rawSchedule[$dayKey][$period] ?? ''));
                $isAutomatic = $this->isAutomaticValue($rawValue);
                $manualUser = $usersByName[normalize_name($rawValue)] ?? null;

                if ($manualUser !== null && !isset($absent[(string) $manualUser['username']])) {
                    $resolved[$dayKey][$period] = (string) $manualUser['nome'];
                    $assignmentScore[(string) $manualUser['username']]++;
                    $dailyAssigned[(string) $manualUser['username']] = true;
                    continue;
                }

                if ($manualUser === null && !$isAutomatic && $rawValue !== '') {
                    $resolved[$dayKey][$period] = $rawValue;
                    continue;
                }

                $selected = $this->pickBestUser($usersByUsername, $assignmentScore, $absent, $dailyAssigned);
                $resolved[$dayKey][$period] = $selected['nome'] ?? '';

                if (isset($selected['username'])) {
                    $assignmentScore[(string) $selected['username']]++;
                    $dailyAssigned[(string) $selected['username']] = true;
                }
            }
        }

        return $resolved;
    }

    private function rebuildFromCache(array $cached, array $rawSchedule, array $fallback): array
    {
        [$usersByName] = $this->buildUserMaps();
        $weekDates = weekday_dates_for_current_week();
        $resolved = [];

        foreach ($weekDates as $dayKey => $date) {
            $activeAbsences = $this->absences->activeOn($date);
            $absent = [];
            foreach ($activeAbsences as $absence) {
                $absent[(string) $absence['username']] = true;
            }

            foreach (['manha', 'tarde'] as $period) {
                $rawValue = trim((string) ($rawSchedule[$dayKey][$period] ?? ''));
                $manualUser = $usersByName[normalize_name($rawValue)] ?? null;

                if ($manualUser !== null && !isset($absent[(string) $manualUser['username']])) {
                    $resolved[$dayKey][$period] = (string) $manualUser['nome'];
                    continue;
                }

                $cachedRow = $cached[$dayKey][$period] ?? null;
                if ($cachedRow !== null && !isset($absent[(string) ($cachedRow['resolved_username'] ?? '')])) {
                    $resolved[$dayKey][$period] = (string) $cachedRow['resolved_name'];
                    continue;
                }

                $resolved[$dayKey][$period] = $fallback[$dayKey][$period] ?? '';
            }
        }

        return $resolved;
    }

    private function buildUserMaps(): array
    {
        $users = $this->users->quotaUsers();
        $usersByName = [];
        $usersByUsername = [];

        foreach ($users as $user) {
            $usersByName[normalize_name((string) $user['nome'])] = $user;
            $usersByUsername[(string) $user['username']] = $user;
        }

        return [$usersByName, $usersByUsername];
    }

    private function isAutomaticValue(string $value): bool
    {
        return normalize_name($value) === 'automatico';
    }

    private function pickBestUser(array $usersByUsername, array $assignmentScore, array $absent, array $dailyAssigned): array
    {
        $selected = null;
        $selectedRatio = null;

        foreach ($usersByUsername as $username => $user) {
            if (isset($absent[$username])) {
                continue;
            }

            $quota = max(1, (int) ($user['consumo_dia'] ?? 1));
            $ratio = ($assignmentScore[$username] ?? 0) / $quota;
            $penalty = isset($dailyAssigned[$username]) ? 1000 : 0;
            $score = $ratio + $penalty;

            if ($selected === null || $score < $selectedRatio || ($score === $selectedRatio && strcasecmp((string) $user['nome'], (string) $selected['nome']) < 0)) {
                $selected = $user;
                $selectedRatio = $score;
            }
        }

        return $selected ?? [];
    }
}
