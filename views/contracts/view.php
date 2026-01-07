<?php $title = 'Detalhes do Contrato'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Contrato <?= htmlspecialchars($contract->number) ?></h1>
    <div>
        <a href="/contracts/edit?id=<?= $contract->id ?>" class="btn btn-outline-primary me-2">Editar</a>
        <a href="/contracts" class="btn btn-secondary">Voltar</a>
    </div>
</div>

<!-- Header -->
<div class="card mb-4 border-start border-5 border-primary">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h2 class="h4"><?= htmlspecialchars($contract->description_short) ?></h2>
                <p class="mb-1"><strong>Fornecedor:</strong> <?= htmlspecialchars($supplier->name ?? 'N/A') ?></p>
                <p class="mb-0">
                    <span class="badge bg-secondary"><?= htmlspecialchars($contract->status_phase) ?></span>
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
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="mb-2"><strong>Vigência:</strong> <br> <?= date('d/m/Y', strtotime($contract->date_start)) ?> a <?= date('d/m/Y', strtotime($contract->date_end_current)) ?></div>
                <div class="h5 text-primary">R$ <?= number_format($contract->value_total, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Action Banner -->
<?php if ($contract->next_action_text): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
    <div>
        <strong>Ação Necessária:</strong> <?= htmlspecialchars($contract->next_action_text) ?>
        <div class="small">Prazo sugerido: <?= $contract->next_action_deadline ? date('d/m/Y', strtotime($contract->next_action_deadline)) : '-' ?></div>
    </div>
    <!-- Actions (Placeholder for future implementation) -->
    <div>
        <!-- Buttons logic would go here -->
    </div>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" id="contractTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">Dados Gerais</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="object-tab" data-bs-toggle="tab" data-bs-target="#object" type="button" role="tab">Objeto</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="responsibles-tab" data-bs-toggle="tab" data-bs-target="#responsibles" type="button" role="tab">Responsáveis</button>
  </li>
</ul>

<div class="tab-content" id="contractTabContent">
  <div class="tab-pane fade show active" id="general" role="tabpanel">
      <div class="card card-body">
          <div class="row">
              <div class="col-md-6">
                  <p><strong>Nº Detalhado:</strong> <?= htmlspecialchars($contract->detailed_number) ?></p>
                  <p><strong>Modalidade:</strong> <?= htmlspecialchars($contract->modality_name) ?> (<?= htmlspecialchars($contract->modality_code) ?>)</p>
                  <p><strong>Processo:</strong> <?= htmlspecialchars($contract->procedure_number) ?></p>
              </div>
              <div class="col-md-6">
                  <p><strong>Exercício:</strong> <?= htmlspecialchars($contract->exercise) ?></p>
                  <p><strong>Fundamento Legal:</strong> <?= htmlspecialchars($contract->legal_basis) ?></p>
                  <p><strong>Prorrogável:</strong> <?= $contract->has_renewal ? 'Sim' : 'Não' ?></p>
              </div>
          </div>
      </div>
  </div>
  <div class="tab-pane fade" id="object" role="tabpanel">
      <div class="card card-body">
          <?= nl2br(htmlspecialchars($contract->description_full)) ?>
      </div>
  </div>
  <div class="tab-pane fade" id="responsibles" role="tabpanel">
      <div class="card card-body">
          <p><strong>Fiscal (Texto):</strong> <?= htmlspecialchars($contract->fiscal_name_raw) ?></p>
          <hr>
          <h6>Usuários Vinculados</h6>
          <?php if (empty($responsibles)): ?>
              <p class="text-muted">Nenhum usuário vinculado.</p>
          <?php else: ?>
              <ul>
                  <?php foreach ($responsibles as $resp): ?>
                      <li><?= htmlspecialchars($resp->user_name) ?> - <small><?= htmlspecialchars($resp->role_in_contract) ?></small></li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
      </div>
  </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
