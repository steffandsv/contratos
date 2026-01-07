<?php $title = 'Novo Contrato'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Novo Contrato</h1>
    <a href="/contracts" class="btn btn-secondary">Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="/contracts/store" method="POST">
            <h5 class="mb-3">1. Identificação</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Nº Contrato</label>
                    <input type="text" class="form-control" name="number" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nº Detalhado</label>
                    <input type="text" class="form-control" name="detailed_number">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exercício</label>
                    <input type="number" class="form-control" name="exercise" value="<?= date('Y') ?>">
                </div>
                 <div class="col-md-4">
                    <label class="form-label">Processo Licitatório</label>
                    <input type="text" class="form-control" name="procedure_number">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                     <label class="form-label">Modalidade (Nome)</label>
                    <input type="text" class="form-control" name="modality_name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nº Modalidade</label>
                    <input type="text" class="form-control" name="modality_code">
                </div>
            </div>

            <h5 class="mb-3">2. Fornecedor</h5>
            <div class="mb-4">
                <label class="form-label">Fornecedor</label>
                <select class="form-select" name="supplier_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier->id ?>"><?= htmlspecialchars($supplier->name) ?> (<?= htmlspecialchars($supplier->document) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Se o fornecedor não estiver na lista, use a importação ou cadastre-o separadamente.</div>
            </div>

            <h5 class="mb-3">3. Vigência e Valores</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="date_start">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim Atual</label>
                    <input type="date" class="form-control" name="date_end_current">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor Total</label>
                    <input type="text" class="form-control" name="value_total" placeholder="0,00">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="has_renewal" value="1" checked>
                        <label class="form-check-label">Admite Prorrogação</label>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">4. Responsáveis e Objeto</h5>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                     <label class="form-label">Gestor Central (Usuário)</label>
                     <select class="form-select" name="manager_user_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($users as $u): ?>
                            <?php if ($u->role == 'GESTOR_CENTRAL' || $u->role == 'ADMIN'): ?>
                                <option value="<?= $u->id ?>"><?= htmlspecialchars($u->name) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                     </select>
                </div>
                <div class="col-md-4">
                     <label class="form-label">Vincular Fiscais (Usuários)</label>
                     <select class="form-select" name="responsibles[]" multiple size="3">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u->id ?>"><?= htmlspecialchars($u->name) ?> (<?= $u->role ?>)</option>
                        <?php endforeach; ?>
                     </select>
                     <div class="form-text">Segure Ctrl para selecionar vários.</div>
                </div>
                 <div class="col-md-4">
                    <label class="form-label">Fiscal (Nome Texto - opcional)</label>
                    <input type="text" class="form-control" name="fiscal_name_raw">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Objeto Completo</label>
                <textarea class="form-control" name="description_full" rows="4"></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Salvar Contrato</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
