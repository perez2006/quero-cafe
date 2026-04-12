<main class="login-shell">
    <section class="login-card">
        <div class="login-hero">
            <img src="<?= e(asset_url('imagens/quero.png')) ?>" alt="Quero Cafe" class="login-logo">
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="stack-form">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" value="<?= e($username ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Entrar</button>
        </form>
    </section>
</main>
