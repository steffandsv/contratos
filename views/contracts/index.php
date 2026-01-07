<?php $title = 'Contratos'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Contratos</h1>
    <div>
        <a href="/import" class="btn btn-outline-secondary me-2">Importar CSV</a>
        <a href="/contracts/create" class="btn btn-primary">Novo Contrato</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/contracts" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status_phase">
                    <option value="">Todas as fases</option>
                    <option value="VIGENTE" <?= $filters['status_phase'] == 'VIGENTE' ? 'selected' : '' ?>>Vigente</option>
                    <option value="EM_PRORROGACAO" <?= $filters['status_phase'] == 'EM_PRORROGACAO' ? 'selected' : '' ?>>Em Prorrogação</option>
                    <option value="ENCERRADO" <?= $filters['status_phase'] == 'ENCERRADO' ? 'selected' : '' ?>>Encerrado</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="only_my_contracts" id="only_my_contracts" <?= $filters['only_my_contracts'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="only_my_contracts">Meus contratos</label>
                </div>
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="only_pending_action" id="only_pending_action" <?= $filters['only_pending_action'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="only_pending_action">Ação pendente</label>
                </div>
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="only_risk" id="only_risk" <?= $filters['only_risk'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="only_risk">Em risco</label>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover bg-white shadow-sm rounded">
        <thead>
            <tr>
                <th>Nº</th>
                <th>Objeto</th>
                <th>Fornecedor</th>
                <th>Fase</th>
                <th>Risco</th>
                <th>Próxima Ação</th>
                <th>Prazo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contracts as $contract): ?>
            <tr>
                <td><?= htmlspecialchars($contract->number) ?></td>
                <td><small><?= htmlspecialchars($contract->description_short) ?></small></td>
                <td><?= htmlspecialchars($contract->supplier_name) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($contract->status_phase) ?></span></td>
                <td>
                    <?php
                    $riskColor = match($contract->status_risk) {
                        'TRANQUILO' => 'success',
                        'PLANEJAR' => 'info',
                        'AGIR' => 'warning',
                        'CRITICO', 'IRREGULAR' => 'danger',
                        default => 'secondary'
                    };
                    ?>
                    <span class="badge bg-<?= $riskColor ?>"><?= htmlspecialchars($contract->status_risk) ?></span>
                </td>
                <td><small><?= htmlspecialchars($contract->next_action_text) ?></small></td>
                <td><?= $contract->next_action_deadline ? date('d/m/Y', strtotime($contract->next_action_deadline)) : '-' ?></td>
                <td>
                    <a href="/contracts/view?id=<?= $contract->id ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
