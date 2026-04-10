<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AbsenceRepository;
use App\Models\RecordRepository;
use App\Models\UserRepository;

final class DashboardService
{
    public function __construct(
        private readonly RecordRepository $records,
        private readonly UserRepository $users,
        private readonly AbsenceRepository $absences
    ) {
    }

    public function build(string $selectedMonth): array
    {
        $quantityOptions = (array) app_config('records.quantity_options', []);
        $eventTypeOptions = (array) app_config('records.event_type_options', []);
        $allRecords = $this->records->all();
        $monthRecords = array_values(array_filter(
            $allRecords,
            static fn (array $record): bool => substr((string) $record['data'], 0, 7) === $selectedMonth
        ));

        $totalsByTypeMonth = ['trouxe' => 0, 'abriu' => 0, 'acabou' => 0];
        $totalsCumulative = ['trouxe' => 0, 'abriu' => 0, 'acabou' => 0];
        $selectedMonthEnd = $selectedMonth . '-31';

        foreach ($monthRecords as $record) {
            $type = (string) $record['tipo'];
            if (isset($totalsByTypeMonth[$type])) {
                $totalsByTypeMonth[$type] += $this->gramsFromRecord($record, $quantityOptions);
            }
        }

        foreach ($allRecords as $record) {
            if ((string) $record['data'] > $selectedMonthEnd) {
                continue;
            }

            $type = (string) $record['tipo'];
            if (isset($totalsCumulative[$type])) {
                $totalsCumulative[$type] += $this->gramsFromRecord($record, $quantityOptions);
            }
        }

        return [
            'eventTypeOptions' => $eventTypeOptions,
            'quantityOptions' => $quantityOptions,
            'trouxeRecords' => array_values(array_filter($monthRecords, static fn (array $r): bool => $r['tipo'] === 'trouxe')),
            'consumoRecords' => array_values(array_filter(
                $monthRecords,
                static fn (array $r): bool => in_array($r['tipo'], ['abriu', 'acabou'], true)
            )),
            'summary' => [
                'consumidoMes' => $totalsByTypeMonth['acabou'],
                'abertoMes' => $totalsByTypeMonth['abriu'],
                'saldoAcumulado' => $totalsCumulative['trouxe'] - $totalsCumulative['acabou'],
            ],
            'forecast' => $this->buildForecast($selectedMonth, $monthRecords, $quantityOptions),
            'trend' => $this->buildTrend($selectedMonth, $allRecords, $quantityOptions),
            'ranking' => $this->buildRanking($allRecords, $quantityOptions),
            'nextBringRows' => $this->buildNextBringRows($selectedMonth, $monthRecords, $quantityOptions),
        ];
    }

    private function buildForecast(string $selectedMonth, array $monthRecords, array $quantityOptions): array
    {
        $consumed = 0;
        foreach ($monthRecords as $record) {
            if ((string) $record['tipo'] === 'acabou') {
                $consumed += $this->gramsFromRecord($record, $quantityOptions);
            }
        }

        $today = date('Y-m-d');
        $isCurrentMonth = substr($today, 0, 7) === $selectedMonth;
        $elapsedDays = $isCurrentMonth ? max(1, (int) date('j')) : (int) substr(month_end($selectedMonth), 8, 2);
        $daysInMonth = (int) substr(month_end($selectedMonth), 8, 2);
        $projected = (int) round(($consumed / max(1, $elapsedDays)) * $daysInMonth);

        return [
            'consumed_so_far' => $consumed,
            'elapsed_days' => $elapsedDays,
            'days_in_month' => $daysInMonth,
            'projected_grams' => $projected,
            'daily_average' => (int) round($consumed / max(1, $elapsedDays)),
        ];
    }

    private function buildTrend(string $selectedMonth, array $allRecords, array $quantityOptions): array
    {
        $selectedValue = $this->consumedInMonth($selectedMonth, $allRecords, $quantityOptions);
        $previousMonths = [];
        $cursor = \DateTimeImmutable::createFromFormat('Y-m-d', $selectedMonth . '-01');

        if ($cursor === false) {
            return ['direction' => 'stable', 'delta_percent' => 0, 'baseline_grams' => 0];
        }

        for ($i = 1; $i <= 3; $i++) {
            $month = $cursor->modify('-' . $i . ' month')->format('Y-m');
            $previousMonths[] = $this->consumedInMonth($month, $allRecords, $quantityOptions);
        }

        $baseline = (int) round(array_sum($previousMonths) / max(1, count($previousMonths)));
        $delta = $baseline > 0 ? (($selectedValue - $baseline) / $baseline) * 100 : 0.0;
        $direction = abs($delta) < 5 ? 'stable' : ($delta > 0 ? 'up' : 'down');

        return [
            'direction' => $direction,
            'delta_percent' => $delta,
            'baseline_grams' => $baseline,
            'current_grams' => $selectedValue,
        ];
    }

    private function consumedInMonth(string $month, array $allRecords, array $quantityOptions): int
    {
        $grams = 0;

        foreach ($allRecords as $record) {
            if (substr((string) $record['data'], 0, 7) === $month && (string) $record['tipo'] === 'acabou') {
                $grams += $this->gramsFromRecord($record, $quantityOptions);
            }
        }

        return $grams;
    }

    private function buildRanking(array $records, array $quantityOptions): array
    {
        $totals = [];
        $counts = [];

        foreach ($records as $record) {
            if ($record['tipo'] !== 'trouxe') {
                continue;
            }

            $name = (string) $record['nome'];
            $totals[$name] = ($totals[$name] ?? 0) + $this->gramsFromRecord($record, $quantityOptions);
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        $ranking = [];
        foreach ($totals as $name => $grams) {
            $ranking[] = ['nome' => $name, 'gramas' => $grams, 'registros' => $counts[$name]];
        }

        usort($ranking, static fn (array $left, array $right): int => $right['gramas'] <=> $left['gramas']);
        return $ranking;
    }

    private function buildNextBringRows(string $selectedMonth, array $records, array $quantityOptions): array
    {
        $quotaUsers = $this->users->quotaUsers();
        $monthAbsences = $this->absences->allForMonth($selectedMonth);
        $totalEffectiveUnits = 0;
        $totalBrought = 0;

        foreach ($quotaUsers as $user) {
            $totalEffectiveUnits += $this->effectiveMonthlyUnits($selectedMonth, $user, $monthAbsences);
        }

        foreach ($records as $record) {
            if ($record['tipo'] === 'trouxe') {
                $totalBrought += $this->gramsFromRecord($record, $quantityOptions);
            }
        }

        $rows = [];
        foreach ($quotaUsers as $user) {
            $historicalBrought = 0;

            foreach ($records as $record) {
                if ($record['tipo'] === 'trouxe' && normalize_name((string) $record['nome']) === normalize_name((string) $user['nome'])) {
                    $historicalBrought += $this->gramsFromRecord($record, $quantityOptions);
                }
            }

            $effectiveUnits = $this->effectiveMonthlyUnits($selectedMonth, $user, $monthAbsences);
            $target = $totalEffectiveUnits > 0 ? ($effectiveUnits / $totalEffectiveUnits) * $totalBrought : 0;
            $percent = $target > 0 ? ($historicalBrought / $target) * 100 : 100;

            $rows[] = [
                'nome' => $user['nome'],
                'atingido_percent_mes' => $percent,
                'faltante_mes_gramas' => max(0, $target - $historicalBrought),
                'dias_disponiveis' => $this->availableWorkdaysInMonth($selectedMonth, (string) $user['username'], $monthAbsences),
            ];
        }

        usort($rows, static fn (array $left, array $right): int => $left['atingido_percent_mes'] <=> $right['atingido_percent_mes']);
        return $rows;
    }

    private function effectiveMonthlyUnits(string $selectedMonth, array $user, array $monthAbsences): int
    {
        return $this->availableWorkdaysInMonth($selectedMonth, (string) $user['username'], $monthAbsences) * max(0, (int) ($user['consumo_dia'] ?? 0));
    }

    private function availableWorkdaysInMonth(string $selectedMonth, string $username, array $monthAbsences): int
    {
        $monthWorkdays = count_weekdays_between(month_start($selectedMonth), month_end($selectedMonth));
        $absentDays = 0;

        foreach ($monthAbsences as $absence) {
            if ((string) $absence['username'] !== $username) {
                continue;
            }

            $overlapStart = max(month_start($selectedMonth), (string) $absence['start_date']);
            $overlapEnd = min(month_end($selectedMonth), (string) $absence['end_date']);
            $absentDays += count_weekdays_between($overlapStart, $overlapEnd);
        }

        return max(0, $monthWorkdays - $absentDays);
    }

    private function gramsFromRecord(array $record, array $quantityOptions): int
    {
        $quantity = (string) ($record['quantidade'] ?? '');

        if (isset($quantityOptions[$quantity])) {
            return (int) $quantityOptions[$quantity];
        }

        if (preg_match('/^(\d+)g$/i', $quantity, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
