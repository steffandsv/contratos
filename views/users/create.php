<?php $title = isset($user) ? 'Editar Usuário' : 'Novo Usuário'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= $title ?></h1>
    <a href="/users" class="btn btn-secondary">Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form action="<?= isset($user) ? '/users/update' : '/users/store' ?>" method="POST">
            <?php if (isset($user)): ?>
                <input type="hidden" name="id" value="<?= $user->id ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Nome Completo</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $user->name ?? '' ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $user->email ?? '' ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha <?= isset($user) ? '(Deixe em branco para manter a atual)' : '' ?></label>
                <input type="password" class="form-control" id="password" name="password" <?= isset($user) ? '' : 'required' ?>>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Perfil</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="ADMIN" <?= (isset($user) && $user->role === 'ADMIN') ? 'selected' : '' ?>>Administrador</option>
                    <option value="GESTOR_CENTRAL" <?= (isset($user) && $user->role === 'GESTOR_CENTRAL') ? 'selected' : '' ?>>Gestor Central</option>
                    <option value="FISCAL" <?= (isset($user) && $user->role === 'FISCAL') ? 'selected' : '' ?>>Fiscal</option>
                    <option value="VISUALIZADOR" <?= (isset($user) && $user->role === 'VISUALIZADOR') ? 'selected' : '' ?>>Visualizador</option>
                </select>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (!isset($user) || $user->is_active) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Ativo</label>
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
