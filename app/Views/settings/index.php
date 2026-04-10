<div class="container">
    <a href="<?= e(base_url()) ?>" class="back-link">← Voltar ao dashboard</a>
    <header class="page-header">
        <div class="brand">
            <h1>Ajustes e configuracoes</h1>
            <p>Escala semanal, ausencias e trilha de auditoria do sistema.</p>
        </div>
        <a href="<?= e(base_url('usuarios')) ?>" class="btn-secondary">Gerenciar usuarios</a>
    </header>

    <?php if ($success): ?><div class="alert alert-success"><?= e((string) $success) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-error"><?= e((string) $message) ?></div><?php endif; ?>

    <section class="card">
        <h2 class="card-title">Escala do cafe</h2>
        <p class="section-copy">Digite o nome da pessoa ou use <strong>AUTOMATICO</strong>. Se a pessoa estiver em ferias ou afastamento, o sistema substitui automaticamente por outra pessoa de forma justa.</p>
        <form method="post" class="stack-form">
            <?php foreach ($scheduleDays as $dayKey => $dayLabel): ?>
                <h3 class="section-subtitle"><?= e($dayLabel) ?></h3>
                <div class="field-grid field-grid-2">
                    <?php foreach ($schedulePeriods as $periodKey => $periodLabel): ?>
                        <div class="form-group">
                            <label><?= e($periodLabel) ?></label>
                            <?php $currentValue = (string) ($coffeeSchedule[$dayKey][$periodKey] ?? ''); ?>
                            <select name="schedule[<?= e($dayKey) ?>][<?= e($periodKey) ?>]">
                                <option value="">Selecione</option>
                                <option value="AUTOMATICO" <?= normalize_name($currentValue) === 'automatico' ? 'selected' : '' ?>>AUTOMATICO</option>
                                <?php foreach ($users as $user): ?>
                                    <?php $userName = (string) $user['nome']; ?>
                                    <option value="<?= e($userName) ?>" <?= normalize_name($currentValue) === normalize_name($userName) ? 'selected' : '' ?>>
                                        <?= e($userName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-help">Resolvido agora: <strong><?= e((string) ($resolvedSchedule[$dayKey][$periodKey] ?? '-')) ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" name="save_coffee_schedule" class="btn-primary">Salvar escala</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card-title">Ferias e afastamentos</h2>
        <form method="post" class="stack-form">
            <input type="hidden" name="save_absence" value="1">
            <div class="field-grid field-grid-4">
                <div class="form-group">
                    <label for="absence_username">Usuario</label>
                    <select id="absence_username" name="absence_username" required>
                        <option value="">Selecione</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= e((string) $user['username']) ?>"><?= e((string) $user['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="absence_start_date">Inicio</label><input type="date" id="absence_start_date" name="absence_start_date" required></div>
                <div class="form-group"><label for="absence_end_date">Fim</label><input type="date" id="absence_end_date" name="absence_end_date" required></div>
                <div class="form-group"><label for="absence_reason">Motivo</label><input type="text" id="absence_reason" name="absence_reason" maxlength="80" placeholder="Ferias, licenca, atestado"></div>
            </div>
            <button type="submit" class="btn-primary">Registrar ausencia</button>
        </form>

        <div class="table-container space-top">
            <table>
                <thead><tr><th>Usuario</th><th>Periodo</th><th>Motivo</th><th>Registrado em</th><th>Acoes</th></tr></thead>
                <tbody>
                <?php if ($absences === []): ?>
                    <tr><td colspan="5" class="table-empty">Nenhuma ausencia registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($absences as $absence): ?>
                        <tr>
                            <td><?= e((string) ($absence['nome'] ?? $absence['username'])) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) $absence['start_date']))) ?> a <?= e(date('d/m/Y', strtotime((string) $absence['end_date']))) ?></td>
                            <td><?= e((string) $absence['reason']) ?></td>
                            <td><?= e(format_datetime((string) $absence['created_at'])) ?></td>
                            <td>
                                <form method="post" class="inline-form" onsubmit="return confirm('Remover esta ausencia?');">
                                    <input type="hidden" name="delete_absence_id" value="<?= (int) $absence['id'] ?>">
                                    <button type="submit" class="btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Historico geral</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Data</th><th>Tipo</th><th>Responsavel</th><th>Marca</th><th>Peso</th><th>Observacao</th><th>Acoes</th></tr></thead>
                <tbody>
                <?php foreach ($allRecords as $record): ?>
                    <tr>
                        <td><?= e((string) $record['data']) ?></td>
                        <td><?= e((string) $record['tipo']) ?></td>
                        <td><?= e((string) $record['nome']) ?></td>
                        <td><?= e((string) $record['cafe']) ?></td>
                        <td><?= e((string) $record['quantidade']) ?></td>
                        <td class="muted-cell"><?= e((string) ($record['observacao'] ?? '')) ?></td>
                        <td>
                            <?php if (normalize_name((string) $record['nome']) === normalize_name((string) $currentUser['nome'])): ?>
                                <form method="post" class="inline-form" onsubmit="return confirm('Deseja realmente remover este registro?');">
                                    <input type="hidden" name="delete_id" value="<?= (int) $record['id'] ?>">
                                    <button type="submit" class="btn-danger">Excluir</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-cell">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Trilha de auditoria</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Quando</th><th>Quem</th><th>Acao</th><th>Descricao</th></tr></thead>
                <tbody>
                <?php if ($auditLogs === []): ?>
                    <tr><td colspan="4" class="table-empty">Sem eventos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?= e(format_datetime((string) $log['created_at'])) ?></td>
                            <td><?= e((string) $log['actor_name']) ?></td>
                            <td><span class="badge badge-neutral"><?= e((string) $log['action_type']) ?></span></td>
                            <td><?= e((string) $log['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
