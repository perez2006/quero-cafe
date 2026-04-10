<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduleRepository;

final class NotificationService
{
    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly ScheduleSuggestionService $scheduleSuggestions
    ) {
    }

    public function notify(string $requestedPeriod = ''): array
    {
        $webhookUrl = trim((string) app_config('notifications.webhook_url', ''));
        if ($webhookUrl === '') {
            error_log('Webhook de notificacao nao configurado.');
            return [false, 'Webhook de notificacao nao configurado.'];
        }

        $days = [
            1 => ['key' => 'monday', 'label' => 'segunda-feira'],
            2 => ['key' => 'tuesday', 'label' => 'terca-feira'],
            3 => ['key' => 'wednesday', 'label' => 'quarta-feira'],
            4 => ['key' => 'thursday', 'label' => 'quinta-feira'],
            5 => ['key' => 'friday', 'label' => 'sexta-feira'],
        ];
        $periods = ['manha' => 'manha', 'tarde' => 'tarde'];

        $period = strtolower(trim($requestedPeriod));
        if (!isset($periods[$period])) {
            $period = (int) date('G') < 12 ? 'manha' : 'tarde';
        }

        $dayNumber = (int) date('N');
        if (!isset($days[$dayNumber])) {
            return [true, 'Sem envio: fim de semana.'];
        }

        $day = $days[$dayNumber];
        $person = $this->scheduleSuggestions->resolvePersonFor($day['key'], $period);

        if ($person === '') {
            return [true, sprintf('Sem envio: nenhum responsavel cadastrado para %s/%s.', $day['key'], $period)];
        }

        $message = sprintf('Cafe - %s, e sua vez de fazer o cafe da %s (%s).', $person, $periods[$period], $day['label']);
        $payload = json_encode(['text' => $message], JSON_UNESCAPED_UNICODE);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($webhookUrl, false, $context);
        if ($response === false) {
            error_log(sprintf('Falha ao enviar webhook de notificacao para %s.', $day['key']));
            return [false, 'Falha ao enviar webhook.'];
        }

        return [true, sprintf('Aviso enviado para %s: %s', $period, $person)];
    }
}
