<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Log;
use PDO;

class ImportController extends Controller
{
    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $this->checkAuth();
        $this->render('import/index');
    }

    public function process()
    {
        $this->checkAuth();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->render('import/index', ['error' => 'Por favor, selecione um arquivo CSV.']);
            return;
        }

        $updateExisting = isset($_POST['update_existing']);
        $file = $_FILES['csv_file']['tmp_name'];

        // Try to detect encoding or just assume UTF-8, fallback ISO-8859-1 as per spec
        $content = file_get_contents($file);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            file_put_contents($file, $content);
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->render('import/index', ['error' => 'Erro ao abrir arquivo.']);
            return;
        }

        $header = fgetcsv($handle, 0, ';'); // Assuming semi-colon separator
        // Mapping based on index in header could be dynamic, but spec says "Fixed header"
        // Let's assume standard order or map by name if we wanted to be robust.
        // For now, I'll map based on the column names in the spec to find index.

        $columnMap = [
            'N° Contrato' => 'number',
            'Nº Detalhado do Contrato' => 'detailed_number',
            'N° Modalidade' => 'modality_code',
            'Modalidade' => 'modality_name',
            'Exercício' => 'exercise',
            'Fundamento Legal' => 'legal_basis',
            'Proc. Licitatório' => 'procedure_number',
            'CPF/CNPJ Fornecedor' => 'supplier_doc',
            'Fornecedor' => 'supplier_name',
            'Valor' => 'value_total',
            'Vigência Inicial' => 'date_start',
            'Vencimento Atual' => 'date_end_current',
            'Objeto' => 'description_full',
            'Tipo' => 'type_code',
            'Contrato de Rateio' => 'rateio_code',
            'Fiscal' => 'fiscal_name_raw'
        ];

        $indices = [];
        foreach ($columnMap as $csvName => $dbField) {
            $index = array_search($csvName, $header);
            if ($index !== false) {
                $indices[$dbField] = $index;
            }
        }

        $stats = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'errors' => []
        ];

        $rowNum = 1; // Header is 1
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rowNum++;
            $stats['total']++;

            try {
                // Helper to safely get value
                $getVal = function($field) use ($row, $indices) {
                    return isset($indices[$field]) ? trim($row[$indices[$field]]) : null;
                };

                $number = $getVal('number');
                $exercise = $getVal('exercise');

                if (!$number) {
                    $stats['errors'][] = "Linha $rowNum: N° Contrato vazio.";
                    continue;
                }

                // Supplier Logic
                $supplierDoc = preg_replace('/[^0-9]/', '', $getVal('supplier_doc'));
                $supplierName = $getVal('supplier_name');

                $supplier = null;
                if ($supplierDoc) {
                    $supplier = Supplier::findByDocument($supplierDoc);
                    if (!$supplier && $supplierName) {
                        $supplier = new Supplier();
                        $supplier->document = $supplierDoc;
                        $supplier->name = $supplierName;
                        $supplier->save();
                    }
                }

                // Contract Logic
                $contract = Contract::findByNumberAndExercise($number, $exercise);

                if ($contract && !$updateExisting) {
                     $stats['errors'][] = "Linha $rowNum: Contrato $number/$exercise já existe (duplicado).";
                     continue;
                }

                if (!$contract) {
                    $contract = new Contract();
                    $contract->created_by_user_id = $_SESSION['user_id'];
                    $isNew = true;
                } else {
                    $isNew = false;
                }

                $contract->number = $number;
                $contract->detailed_number = $getVal('detailed_number');
                $contract->modality_code = $getVal('modality_code');
                $contract->modality_name = $getVal('modality_name');
                $contract->exercise = (int)$exercise;
                $contract->legal_basis = $getVal('legal_basis');
                $contract->procedure_number = $getVal('procedure_number');
                $contract->supplier_id = $supplier ? $supplier->id : null;

                // Value parsing: "1.234,56" -> 1234.56
                $val = $getVal('value_total');
                $val = str_replace('.', '', $val); // remove thousands
                $val = str_replace(',', '.', $val); // decimal
                $contract->value_total = (float)$val;

                // Date parsing dd/mm/yyyy -> Y-m-d
                $parseDate = function($d) {
                    if (!$d) return null;
                    $dt = \DateTime::createFromFormat('d/m/Y', $d);
                    return $dt ? $dt->format('Y-m-d') : null;
                };

                $contract->date_start = $parseDate($getVal('date_start'));
                $contract->date_end_current = $parseDate($getVal('date_end_current'));
                $contract->description_full = $getVal('description_full');
                $contract->description_short = substr($contract->description_full, 0, 180);
                $contract->type_code = $getVal('type_code');
                $contract->rateio_code = $getVal('rateio_code');
                $contract->fiscal_name_raw = $getVal('fiscal_name_raw');

                if ($isNew) {
                    $contract->status_phase = 'VIGENTE';
                    $contract->has_renewal = 1;
                }

                $contract->calculateRiskAndNextAction();

                $contract->save();

                if ($isNew) $stats['imported']++;
                else $stats['updated']++;

            } catch (\Exception $e) {
                $stats['errors'][] = "Linha $rowNum: Erro - " . $e->getMessage();
            }
        }
        fclose($handle);

        Log::create($_SESSION['user_id'], 'IMPORT_CSV', "Importou CSV: {$stats['imported']} novos, {$stats['updated']} atualizados.");

        $this->render('import/index', ['stats' => $stats]);
    }
}
