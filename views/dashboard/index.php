<?php $title = 'Painel de Controle'; ?>
<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col-md-3">
        <a href="/contracts?only_pending_action=on" class="text-decoration-none">
            <div class="card text-white bg-danger mb-3 h-100">
                <div class="card-body">
                    <h5 class="card-title">Ações de Hoje</h5>
                    <p class="card-text display-4"><?= $stats['actions_today'] ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/contracts" class="text-decoration-none">
            <div class="card text-white bg-warning mb-3 h-100">
                <div class="card-body">
                    <h5 class="card-title">Próx. 30 dias</h5>
                    <p class="card-text display-4"><?= $stats['next_30_days'] ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/contracts?only_my_contracts=on" class="text-decoration-none">
            <div class="card text-white bg-primary mb-3 h-100">
                <div class="card-body">
                    <h5 class="card-title">Meus Contratos</h5>
                    <p class="card-text display-4"><?= $stats['my_contracts'] ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
         <a href="/contracts?only_risk=on" class="text-decoration-none">
            <div class="card text-white bg-dark mb-3 h-100">
                <div class="card-body">
                    <h5 class="card-title">Risco Elevado</h5>
                    <p class="card-text display-4"><?= $stats['high_risk'] ?></p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                Distribuição de Risco
            </div>
            <div class="card-body">
                <canvas id="riskChart" height="100"></canvas>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Minha Fila de Ações
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Risco</th>
                                <th>Nº</th>
                                <th>Objeto</th>
                                <th>Ação</th>
                                <th>Prazo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myQueue)): ?>
                                <tr><td colspan="6" class="text-center p-3">Nenhuma ação pendente na fila.</td></tr>
                            <?php else: ?>
                                <?php foreach ($myQueue as $contract): ?>
                                <tr>
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
                                    <td><?= htmlspecialchars($contract->number) ?></td>
                                    <td><small><?= htmlspecialchars($contract->description_short) ?></small></td>
                                    <td><small><?= htmlspecialchars($contract->next_action_text) ?></small></td>
                                    <td><?= date('d/m/Y', strtotime($contract->next_action_deadline)) ?></td>
                                    <td><a href="/contracts/view?id=<?= $contract->id ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                Alertas de Risco
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($alerts)): ?>
                    <li class="list-group-item text-muted">Nenhum alerta crítico.</li>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <small class="fw-bold d-block text-danger"><?= htmlspecialchars($alert['message']) ?></small>
                            </div>
                            <a href="/contracts/view?id=<?= $alert['contract_id'] ?>" class="btn btn-sm btn-light">Ver</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('riskChart').getContext('2d');
    const riskChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Tranquilo', 'Planejar', 'Agir', 'Crítico', 'Irregular'],
            datasets: [{
                label: 'Contratos por Risco',
                data: [
                    <?= \App\Models\Contract::all(['status_risk' => 'TRANQUILO']) ? count(\App\Models\Contract::all(['status_risk' => 'TRANQUILO'])) : 0 ?>,
                    <?= \App\Models\Contract::all(['status_risk' => 'PLANEJAR']) ? count(\App\Models\Contract::all(['status_risk' => 'PLANEJAR'])) : 0 ?>,
                    <?= \App\Models\Contract::all(['status_risk' => 'AGIR']) ? count(\App\Models\Contract::all(['status_risk' => 'AGIR'])) : 0 ?>,
                    <?= \App\Models\Contract::all(['status_risk' => 'CRITICO']) ? count(\App\Models\Contract::all(['status_risk' => 'CRITICO'])) : 0 ?>,
                    <?= \App\Models\Contract::all(['status_risk' => 'IRREGULAR']) ? count(\App\Models\Contract::all(['status_risk' => 'IRREGULAR'])) : 0 ?>
                ],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.2)',
                    'rgba(13, 202, 240, 0.2)',
                    'rgba(255, 193, 7, 0.2)',
                    'rgba(220, 53, 69, 0.2)',
                    'rgba(33, 37, 41, 0.2)'
                ],
                borderColor: [
                    'rgba(25, 135, 84, 1)',
                    'rgba(13, 202, 240, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(33, 37, 41, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
