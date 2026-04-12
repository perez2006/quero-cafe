<?php
$stockBalance = (int) $summary['saldoAcumulado'];
$stockCapacity = 2000;
$stockPercent = max(0, min(100, (int) round(($stockBalance / $stockCapacity) * 100)));
$stockCriticalLimit = 500;
$isStockCritical = $stockBalance <= $stockCriticalLimit;
$toastMessage = (string) ($success ?? '');
$lastRecordType = (string) ($lastRecordType ?? '');
$lastRecordPerson = (string) ($lastRecordPerson ?? '');
if ($toastMessage !== '') {
    if ($lastRecordType === 'trouxe') {
        $toastMessage = $lastRecordPerson !== ''
            ? sprintf('%s trouxe cafe. Ainda ha esperanca.', $lastRecordPerson)
            : 'Cafe registrado. A firma continua de pe.';
    } elseif ($lastRecordType === 'abriu') {
        $toastMessage = 'Cafe aberto. Agora vai.';
    } elseif ($lastRecordType === 'acabou') {
        $toastMessage = 'Estoque atualizado. O caos foi adiado.';
    }
}
?>
<div class="container dashboard-shell<?= $isStockCritical ? ' stock-is-critical' : '' ?>"
     data-toast-message="<?= e($toastMessage) ?>"
     data-record-type="<?= e($lastRecordType) ?>"
     data-record-person="<?= e($lastRecordPerson) ?>">
    <header class="page-header">
        <div class="brand">
            <div class="brand-kicker">
                <div class="dashboard-cup" aria-hidden="true">
                    <span class="cup-steam steam-one"></span>
                    <span class="cup-steam steam-two"></span>
                    <span class="cup-steam steam-three"></span>
                    <span class="cup-bowl"></span>
                </div>
                <span>plantao cafeinado</span>
            </div>
            <h1>Quero Cafe</h1>
            <p>Acompanhamento de consumo • <strong><?= e($selectedMonthLabel) ?></strong></p>
        </div>
        <div class="user-nav">
            <div class="user-info">
                <strong><?= e((string) ($currentUser['nome'] ?? '-')) ?></strong><br>
                <span>Usuario autenticado</span>
            </div>
            <a class="btn-logout" href="<?= e(base_url('logout')) ?>">Sair</a>
        </div>
    </header>

    <?php if ($success): ?><div class="alert alert-success"><?= e((string) $success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>

    <section class="stats-grid stats-grid-5">
        <div class="stat-card"><span class="stat-label">Consumido</span><span class="stat-value"><?= e(format_weight((int) $summary['consumidoMes'])) ?></span></div>
        <div class="stat-card"><span class="stat-label">Aberto</span><span class="stat-value"><?= e(format_weight((int) $summary['abertoMes'])) ?></span></div>
        <div class="stat-card stock-card<?= $isStockCritical ? ' stock-critical' : '' ?>" style="--stock-level: <?= $stockPercent ?>%;">
            <div class="stock-card-head">
                <span class="stat-label">Saldo em estoque</span>
                <span class="stock-pill"><?= $stockPercent ?>%</span>
            </div>
            <span class="stat-value accent"><?= e(format_weight($stockBalance)) ?></span>
            <div class="coffee-gauge" aria-hidden="true">
                <div class="coffee-gauge-fill"></div>
                <div class="coffee-gauge-shine"></div>
            </div>
            <span class="stat-note"><?= $isStockCritical ? 'Estoque baixo. O setor entra em colapso em breve.' : 'Garrafa sob controle. O caos foi adiado.' ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Previsao mensal</span>
            <span class="stat-value"><?= e(format_weight((int) $forecast['projected_grams'])) ?></span>
            <span class="stat-note">Media diaria: <?= e(format_weight((int) $forecast['daily_average'])) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Tendencia</span>
            <span class="stat-value"><?= e($trend['direction'] === 'up' ? 'Alta' : ($trend['direction'] === 'down' ? 'Queda' : 'Estavel')) ?></span>
            <span class="stat-note"><?= number_format((float) $trend['delta_percent'], 1, ',', '.') ?>% vs media recente</span>
        </div>
    </section>

    <div class="toolbar"><a href="<?= e(base_url('config')) ?>" class="btn-secondary">Ajustes e configuracoes</a></div>

    <div class="main-grid">
        <div class="main-content">
            <section class="card">
                <h2 class="card-title">Novo registro</h2>
                <form method="post" class="stack-form">
                    <div class="field-grid field-grid-2">
                        <div class="form-group">
                            <label for="tipo">Evento</label>
                            <select id="tipo" name="tipo" required>
                                <?php foreach ($eventTypeOptions as $typeKey => $typeLabel): ?>
                                    <option value="<?= e((string) $typeKey) ?>"><?= e((string) $typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Quem</label><input type="text" value="<?= e((string) ($currentUser['nome'] ?? '')) ?>" readonly></div>
                    </div>

                    <div class="field-grid field-grid-3">
                        <div class="form-group"><label for="data">Data</label><input type="date" id="data" name="data" required value="<?= e(date('Y-m-d')) ?>"></div>
                        <div class="form-group">
                            <label for="quantidade">Peso</label>
                            <select id="quantidade" name="quantidade" required>
                                <?php foreach ($quantityOptions as $label => $grams): ?>
                                    <option value="<?= e((string) $label) ?>"><?= e((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="cafe">Cafe</label><input type="text" id="cafe" name="cafe" maxlength="80" required placeholder="Marca ou tipo"></div>
                    </div>

                    <div class="form-group"><label for="observacao">Observacoes</label><textarea id="observacao" name="observacao" maxlength="140" rows="3"></textarea></div>
                    <button class="btn-primary cursor-coffee" type="submit">Salvar registro</button>
                </form>
            </section>

            <div class="filter-bar">
                <form method="get">
                    <label for="mes">Mes de referencia</label>
                    <input type="month" id="mes" name="mes" value="<?= e($selectedMonth) ?>">
                    <button class="btn-secondary" type="submit">Filtrar</button>
                </form>
            </div>

            <section class="card">
                <h2 class="card-title">Entradas de cafe</h2>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Data</th><th>Quem trouxe</th><th>Marca</th><th>Peso</th><th>Observacao</th></tr></thead>
                        <tbody>
                        <?php if ($trouxeRecords === []): ?>
                            <tr><td colspan="5" class="table-empty">Nenhuma entrada registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($trouxeRecords as $entry): ?>
                                <tr>
                                    <td><?= e(date('d/m/y', strtotime((string) $entry['data']))) ?></td>
                                    <td><strong><?= e((string) $entry['nome']) ?></strong></td>
                                    <td><?= e((string) $entry['cafe']) ?></td>
                                    <td><?= e((string) $entry['quantidade']) ?></td>
                                    <td class="muted-cell"><?= e((string) $entry['observacao']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Historico de uso</h2>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Data</th><th>Tipo</th><th>Responsavel</th><th>Marca</th></tr></thead>
                        <tbody>
                        <?php if ($consumoRecords === []): ?>
                            <tr><td colspan="4" class="table-empty">Nenhum uso registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($consumoRecords as $entry): ?>
                                <?php $type = (string) ($entry['tipo'] ?? 'trouxe'); ?>
                                <tr>
                                    <td><?= e(date('d/m/y', strtotime((string) $entry['data']))) ?></td>
                                    <td><span class="badge badge-<?= e($type) ?>"><?= e((string) ($eventTypeOptions[$type] ?? $type)) ?></span></td>
                                    <td><strong><?= e((string) ($entry['nome'] ?? '-')) ?></strong></td>
                                    <td><?= e((string) ($entry['cafe'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="sidebar">
            <section class="card">
                <h2 class="card-title">Proximo responsavel</h2>
                <div class="feature-callout">
                    <strong><?= e((string) $currentSuggestion['name']) ?></strong>
                    <span><?= e((string) $currentSuggestion['label']) ?></span>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Ranking geral</h2>
                <div class="table-container">
                    <table><tbody>
                    <?php if ($ranking === []): ?>
                        <tr><td colspan="3" class="table-empty">Sem dados.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($ranking, 0, 5) as $index => $row): ?>
                            <tr><td class="ranking-order"><?= $index + 1 ?></td><td><?= e((string) $row['nome']) ?></td><td class="text-right"><?= (int) $row['gramas'] ?>g</td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody></table>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Proximos a trazer</h2>
                <?php if ($nextBringRows === []): ?>
                    <p class="table-empty">Sem sugestoes.</p>
                <?php else: ?>
                    <?php foreach (array_slice($nextBringRows, 0, 5) as $row): ?>
                        <?php $percent = min(100, (float) $row['atingido_percent_mes']); $barClass = $percent < 50 ? 'progress-low' : ($percent < 100 ? 'progress-mid' : 'progress-high'); ?>
                        <div class="progress-item">
                            <div class="progress-head"><strong><?= e((string) $row['nome']) ?></strong><span><?= number_format((float) $row['atingido_percent_mes'], 0) ?>%</span></div>
                            <div class="progress-container"><div class="progress-bar <?= e($barClass) ?>" style="width: <?= $percent ?>%;"></div></div>
                            <div class="progress-meta">Faltam <?= (int) round((float) $row['faltante_mes_gramas']) ?>g.</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="card" style="background: var(--primary); color: white;">
                <!-- <h2 class="card-title" style="color: white; font-size: 1rem;">Dica</h2> -->
                <p style="font-size: 0.85rem; opacity: 0.9; margin: 0;">
                    QUERO CAFE!<br>
                    QUERO CAFE!<br>
                    QUERO CAFE!<br>
                    EEEEE!<br>
                    Isso aqui e uma porcaria, que nao vale merda nenhuma!<br>
                    Desculpe.
                </p>
            </section>
        </aside>
    </div>
</div>
