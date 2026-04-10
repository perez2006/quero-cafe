<div class="container">
    <a href="<?= e(base_url()) ?>" class="back-link">← Voltar ao dashboard</a>
    <header class="page-header">
        <div class="brand">
            <h1>Gerenciar usuarios</h1>
            <p>Cadastro centralizado com senha segura e edicao sem expor credenciais.</p>
        </div>
    </header>

    <?php if ($success): ?><div class="alert alert-success"><?= e((string) $success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e((string) $error) ?></div><?php endif; ?>

    <section class="card">
        <h2 class="card-title">Novo ou editar usuario</h2>
        <form method="post" class="stack-form" id="user-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="original_username" id="original_username" value="">
            <div class="field-grid field-grid-4">
                <div class="form-group"><label for="username">Usuario</label><input type="text" name="username" id="username" required></div>
                <div class="form-group"><label for="nome">Nome exibicao</label><input type="text" name="nome" id="nome" required></div>
                <div class="form-group"><label for="senha">Senha</label><input type="password" name="senha" id="senha" placeholder="Preencha para criar ou trocar"></div>
                <div class="form-group"><label for="consumo_dia">Consumo diario</label><input type="number" name="consumo_dia" id="consumo_dia" value="1" min="0" required></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Salvar usuario</button>
                <button type="button" class="btn-secondary" onclick="resetUserForm()">Limpar</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="card-title">Usuarios cadastrados</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Usuario</th><th>Nome</th><th>Consumo</th><th>Acoes</th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e((string) $user['username']) ?></td>
                        <td><strong><?= e((string) $user['nome']) ?></strong></td>
                        <td><?= (int) $user['consumo_dia'] ?></td>
                        <td>
                            <div class="inline-actions">
                                <button
                                    type="button"
                                    class="btn-edit"
                                    onclick='editUser(<?= json_encode((string) $user["username"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, <?= json_encode((string) $user["nome"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, <?= json_encode((int) $user["consumo_dia"]) ?>)'
                                >Editar</button>
                                <form method="post" class="inline-form" onsubmit="return confirm('Deseja realmente remover este usuario?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="username_delete" value="<?= e((string) $user['username']) ?>">
                                    <button type="submit" class="btn-danger">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
function editUser(username, nome, consumo) {
    document.getElementById('original_username').value = username;
    document.getElementById('username').value = username;
    document.getElementById('nome').value = nome;
    document.getElementById('senha').value = '';
    document.getElementById('consumo_dia').value = consumo;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetUserForm() {
    document.getElementById('user-form').reset();
    document.getElementById('original_username').value = '';
    document.getElementById('consumo_dia').value = '1';
}
</script>
