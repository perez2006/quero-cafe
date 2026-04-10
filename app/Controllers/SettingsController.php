<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AbsenceRepository;
use App\Models\AuditLogRepository;
use App\Models\RecordRepository;
use App\Models\ScheduleRepository;
use App\Models\UserRepository;
use App\Services\AuditLogger;
use App\Services\ScheduleSuggestionService;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly RecordRepository $recordRepository,
        private readonly AbsenceRepository $absenceRepository,
        private readonly UserRepository $userRepository,
        private readonly AuditLogRepository $auditLogs,
        private readonly ScheduleSuggestionService $scheduleSuggestions,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function index(): void
    {
        $currentUser = Auth::requireUser();
        $message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coffee_schedule'])) {
            try {
                $postedSchedule = is_array($_POST['schedule'] ?? null) ? $_POST['schedule'] : [];
                $this->scheduleRepository->replaceAll($postedSchedule);
                $this->auditLogger->log($currentUser, 'update', 'schedule', 'weekly', 'Escala do cafe salva manualmente.', $postedSchedule);
                Session::flash('success', 'Escala do cafe salva com sucesso.');
                $this->redirect('config');
            } catch (\Throwable $exception) {
                error_log('Erro ao salvar escala do cafe: ' . $exception->getMessage());
                $message = 'Erro interno ao salvar a escala.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
            $recordId = (int) $_POST['delete_id'];
            $record = $this->recordRepository->findById($recordId);

            if ($record !== null && normalize_name((string) $record['nome']) === normalize_name((string) $currentUser['nome'])) {
                $this->recordRepository->delete($recordId);
                $this->auditLogger->log($currentUser, 'delete', 'record', (string) $recordId, 'Registro removido pelo autor.', $record);
                Session::flash('success', 'Registro removido com sucesso.');
                $this->redirect('config');
            }

            $message = 'Voce so pode remover registros criados por voce.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_absence'])) {
            $message = $this->saveAbsence($currentUser);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_absence_id'])) {
            $absenceId = (int) $_POST['delete_absence_id'];
            $absence = $this->absenceRepository->findById($absenceId);

            if ($absence !== null) {
                $this->absenceRepository->delete($absenceId);
                $this->auditLogger->log($currentUser, 'delete', 'absence', (string) $absenceId, 'Periodo de ausencia removido.', $absence);
                Session::flash('success', 'Ausencia removida com sucesso.');
                $this->redirect('config');
            }
        }

        $this->render('settings/index', [
            'title' => 'Configuracoes',
            'currentUser' => $currentUser,
            'message' => $message,
            'success' => Session::pullFlash('success'),
            'coffeeSchedule' => $this->scheduleRepository->all(),
            'allRecords' => $this->recordRepository->all(),
            'absences' => $this->absenceRepository->all(),
            'auditLogs' => $this->auditLogs->recent(30),
            'users' => $this->userRepository->all(),
            'resolvedSchedule' => $this->scheduleSuggestions->resolveWeeklySchedule(),
            'scheduleDays' => [
                'monday' => 'Segunda-feira',
                'tuesday' => 'Terca-feira',
                'wednesday' => 'Quarta-feira',
                'thursday' => 'Quinta-feira',
                'friday' => 'Sexta-feira',
            ],
            'schedulePeriods' => ['manha' => 'Manha', 'tarde' => 'Tarde'],
        ]);
    }

    private function saveAbsence(array $currentUser): ?string
    {
        $username = mb_strtolower(trim((string) ($_POST['absence_username'] ?? '')), 'UTF-8');
        $startDate = trim((string) ($_POST['absence_start_date'] ?? ''));
        $endDate = trim((string) ($_POST['absence_end_date'] ?? ''));
        $reason = trim((string) ($_POST['absence_reason'] ?? ''));

        if ($username === '' || !is_valid_date($startDate) || !is_valid_date($endDate)) {
            return 'Preencha usuario e periodo de ausencia com datas validas.';
        }

        if ($endDate < $startDate) {
            return 'A data final da ausencia nao pode ser anterior a data inicial.';
        }

        try {
            $absenceId = $this->absenceRepository->create([
                'username' => $username,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'created_at' => date('c'),
            ]);

            $this->auditLogger->log(
                $currentUser,
                'create',
                'absence',
                (string) $absenceId,
                sprintf('Ausencia registrada para %s.', $username),
                ['username' => $username, 'start_date' => $startDate, 'end_date' => $endDate, 'reason' => $reason]
            );

            Session::flash('success', 'Ausencia registrada com sucesso.');
            $this->redirect('config');
        } catch (\Throwable $exception) {
            error_log('Erro ao salvar ausencia: ' . $exception->getMessage());
            return 'Erro interno ao salvar ausencia.';
        }
    }
}
