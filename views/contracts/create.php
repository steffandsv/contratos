<?php $title = isset($contract) ? 'Editar Contrato' : 'Novo Contrato'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= $title ?></h1>
    <a href="/contracts" class="btn btn-secondary">Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= isset($contract) ? '/contracts/update' : '/contracts/store' ?>" method="POST">
            <?php if (isset($contract)): ?>
                <input type="hidden" name="id" value="<?= $contract->id ?>">
            <?php endif; ?>

            <h5 class="mb-3">1. Identificação</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Nº Contrato</label>
                    <input type="text" class="form-control" name="number" value="<?= htmlspecialchars($contract->number ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nº Detalhado</label>
                    <input type="text" class="form-control" name="detailed_number" value="<?= htmlspecialchars($contract->detailed_number ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exercício</label>
                    <input type="number" class="form-control" name="exercise" value="<?= htmlspecialchars($contract->exercise ?? date('Y')) ?>">
                </div>
                 <div class="col-md-4">
                    <label class="form-label">Processo Licitatório</label>
                    <input type="text" class="form-control" name="procedure_number" value="<?= htmlspecialchars($contract->procedure_number ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                     <label class="form-label">Modalidade (Nome)</label>
                    <input type="text" class="form-control" name="modality_name" value="<?= htmlspecialchars($contract->modality_name ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nº Modalidade</label>
                    <input type="text" class="form-control" name="modality_code" value="<?= htmlspecialchars($contract->modality_code ?? '') ?>">
                </div>
            </div>

            <h5 class="mb-3">2. Fornecedor</h5>
            <div class="mb-4">
                <label class="form-label">Fornecedor</label>
                <select class="form-select" name="supplier_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier->id ?>" <?= (isset($contract) && $contract->supplier_id == $supplier->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($supplier->name) ?> (<?= htmlspecialchars($supplier->document) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Se o fornecedor não estiver na lista, use a importação ou cadastre-o separadamente.</div>
            </div>

            <h5 class="mb-3">3. Vigência e Valores</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="date_start" value="<?= $contract->date_start ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim Atual</label>
                    <input type="date" class="form-control" name="date_end_current" value="<?= $contract->date_end_current ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor Total</label>
                    <input type="text" class="form-control" name="value_total" value="<?= isset($contract) ? number_format($contract->value_total, 2, ',', '.') : '' ?>" placeholder="0,00">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="has_renewal" value="1" <?= (!isset($contract) || $contract->has_renewal) ? 'checked' : '' ?>>
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
                                <option value="<?= $u->id ?>" <?= (isset($contract) && $contract->manager_user_id == $u->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u->name) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                     </select>
                </div>
                <div class="col-md-4">
                     <label class="form-label">Vincular Fiscais (Usuários)</label>
                     <select class="form-select" name="responsibles[]" multiple size="3">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u->id ?>" <?= (isset($responsibleIds) && in_array($u->id, $responsibleIds)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u->name) ?> (<?= $u->role ?>)
                            </option>
                        <?php endforeach; ?>
                     </select>
                     <div class="form-text">Segure Ctrl para selecionar vários.</div>
                </div>
                 <div class="col-md-4">
                    <label class="form-label">Fiscal (Nome Texto - opcional)</label>
                    <input type="text" class="form-control" name="fiscal_name_raw" value="<?= htmlspecialchars($contract->fiscal_name_raw ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Objeto Completo</label>
                <textarea class="form-control" name="description_full" rows="4"><?= htmlspecialchars($contract->description_full ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Salvar Contrato</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
