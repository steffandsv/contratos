<?php $title = 'Importar Contratos'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Importação de CSV</h1>
    <a href="/contracts" class="btn btn-secondary">Voltar</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($stats)): ?>
            <div class="alert alert-success">
                <h4 class="alert-heading">Processamento Concluído!</h4>
                <p>Total processado: <?= $stats['total'] ?></p>
                <hr>
                <p class="mb-0">Importados: <strong><?= $stats['imported'] ?></strong> | Atualizados: <strong><?= $stats['updated'] ?></strong></p>
            </div>
            <?php if (!empty($stats['errors'])): ?>
                <div class="card mt-3 border-danger">
                    <div class="card-header bg-danger text-white">Erros Encontrados</div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stats['errors'] as $err): ?>
                            <li class="list-group-item text-danger"><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="/import/process" method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="csv_file" class="form-label">Arquivo CSV</label>
                <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                <div class="form-text">
                    O arquivo deve seguir o modelo padrão, separado por ponto-e-vírgula (;).
                    <br>Codificação: UTF-8 ou ISO-8859-1.
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="update_existing" name="update_existing" value="1">
                <label class="form-check-label" for="update_existing">Atualizar contratos existentes (mesmo Nº + Exercício)</label>
            </div>

            <button type="submit" class="btn btn-primary">Importar</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Layout Esperado
    </div>
    <div class="card-body">
        <p class="small text-muted">A primeira linha deve conter exatamente os cabeçalhos abaixo:</p>
        <code class="d-block p-2 bg-light border rounded">
            N° Contrato;Nº Detalhado do Contrato;N° Modalidade;Modalidade;Exercício;Fundamento Legal;Proc. Licitatório;CPF/CNPJ Fornecedor;Fornecedor;Valor;Vigência Inicial;Vencimento Atual;Objeto;Tipo;Contrato de Rateio;Fiscal
        </code>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
