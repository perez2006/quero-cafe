<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\RecordRepository;
use App\Services\AuditLogger;
use App\Services\DashboardService;
use App\Services\ScheduleSuggestionService;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly RecordRepository $records,
        private readonly DashboardService $dashboardService,
        private readonly ScheduleSuggestionService $scheduleSuggestions,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function index(): void
    {
        $currentUser = Auth::requireUser();
        $selectedMonth = $this->resolveMonth();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = [
                'tipo' => trim((string) ($_POST['tipo'] ?? '')),
                'data' => trim((string) ($_POST['data'] ?? '')),
                'quantidade' => trim((string) ($_POST['quantidade'] ?? '')),
                'cafe' => trim((string) ($_POST['cafe'] ?? '')),
                'observacao' => trim((string) ($_POST['observacao'] ?? '')),
                'nome' => (string) ($currentUser['nome'] ?? ''),
            ];

            $errors = $this->validateRecord($payload);

            if ($errors === []) {
                $recordId = $this->records->create($payload);
                $this->auditLogger->log(
                    $currentUser,
                    'create',
                    'record',
                    (string) $recordId,
                    sprintf('Registro criado por %s em %s.', $payload['nome'], $payload['data']),
                    $payload
                );
                Session::flash('success', 'Registro salvo com sucesso.');
                $this->redirect('?mes=' . rawurlencode(substr($payload['data'], 0, 7)));
            }
        }

        $dashboard = $this->dashboardService->build($selectedMonth);
        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'currentUser' => $currentUser,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => month_label($selectedMonth),
            'success' => Session::pullFlash('success'),
            'errors' => $errors,
            'currentSuggestion' => $this->scheduleSuggestions->buildCurrentSuggestionCard(),
        ] + $dashboard);
    }

    private function resolveMonth(): string
    {
        $month = trim((string) ($_GET['mes'] ?? ''));
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    private function validateRecord(array $payload): array
    {
        $errors = [];
        $eventTypeOptions = (array) app_config('records.event_type_options', []);
        $quantityOptions = (array) app_config('records.quantity_options', []);

        if (!isset($eventTypeOptions[$payload['tipo']])) {
            $errors[] = 'Selecione um tipo de registro valido.';
        }
        if (!is_valid_date($payload['data'])) {
            $errors[] = 'Informe uma data valida.';
        }
        if (!isset($quantityOptions[$payload['quantidade']])) {
            $errors[] = 'Selecione a quantidade de cafe.';
        }
        if ($payload['cafe'] === '' || mb_strlen($payload['cafe'], 'UTF-8') > 80) {
            $errors[] = 'O campo de cafe deve ter entre 1 e 80 caracteres.';
        }
        if (mb_strlen($payload['observacao'], 'UTF-8') > 140) {
            $errors[] = 'A observacao deve ter no maximo 140 caracteres.';
        }

        return $errors;
    }
}
