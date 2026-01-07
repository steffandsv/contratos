<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contract;
use App\Models\ContractResponsible;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Log;
use App\Models\Setting;

class ContractController extends Controller
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

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status_phase' => $_GET['status_phase'] ?? '',
            'only_my_contracts' => isset($_GET['only_my_contracts']),
            'only_pending_action' => isset($_GET['only_pending_action']),
            'only_risk' => isset($_GET['only_risk']),
            'user_id' => $_SESSION['user_id']
        ];

        $contracts = Contract::all($filters);

        $this->render('contracts/index', ['contracts' => $contracts, 'filters' => $filters]);
    }

    public function create()
    {
        $this->checkAuth();
        $suppliers = Supplier::all();
        $users = User::all();
        $this->render('contracts/create', ['suppliers' => $suppliers, 'users' => $users]);
    }

    public function store()
    {
        $this->checkAuth();

        $contract = new Contract();
        // Populate fields
        $contract->number = $_POST['number'];
        $contract->detailed_number = $_POST['detailed_number'];
        $contract->modality_code = $_POST['modality_code'];
        $contract->modality_name = $_POST['modality_name'];
        $contract->fiscal_name_raw = $_POST['fiscal_name_raw'];
        $contract->exercise = $_POST['exercise'];
        $contract->legal_basis = $_POST['legal_basis'];
        $contract->procedure_number = $_POST['procedure_number'];
        $contract->supplier_id = $_POST['supplier_id'] ?: null;
        // Handle supplier creation if new name provided? (Omitted for simplicity, assuming existing supplier or created via import)

        $contract->value_total = str_replace(',', '.', str_replace('.', '', $_POST['value_total'])); // Remove thousands sep, fix decimal
        $contract->date_start = $_POST['date_start'];
        $contract->date_end_current = $_POST['date_end_current'];
        $contract->description_full = $_POST['description_full'];
        $contract->description_short = substr($_POST['description_full'], 0, 180);
        $contract->has_renewal = isset($_POST['has_renewal']) ? 1 : 0;
        $contract->max_renewals = $_POST['max_renewals'] ?: null;
        $contract->status_phase = 'VIGENTE';
        $contract->created_by_user_id = $_SESSION['user_id'];
        $contract->manager_user_id = $_POST['manager_user_id'] ?: null;

        $contract->calculateRiskAndNextAction();

        if ($contract->save()) {
            // Handle Responsibles
            if (!empty($_POST['responsibles']) && is_array($_POST['responsibles'])) {
                foreach ($_POST['responsibles'] as $userId) {
                     $resp = new ContractResponsible();
                     $resp->contract_id = $contract->id;
                     $resp->user_id = $userId;
                     $resp->role_in_contract = 'FISCAL';
                     $resp->save();
                }
            }

            Log::create($_SESSION['user_id'], 'CREATE_CONTRACT', "Criou contrato {$contract->number}");
            $this->redirect('/contracts');
        } else {
            // Handle error
             $this->redirect('/contracts/create');
        }
    }

    public function view()
    {
        $this->checkAuth();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/contracts');
        }
        $contract = Contract::find($id);
        if (!$contract) {
            $this->redirect('/contracts');
        }

        $supplier = Supplier::find($contract->supplier_id);
        $responsibles = ContractResponsible::getByContract($id);

        $this->render('contracts/view', [
            'contract' => $contract,
            'supplier' => $supplier,
            'responsibles' => $responsibles
        ]);
    }

    public function edit()
    {
        $this->checkAuth();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/contracts');
        }
        $contract = Contract::find($id);
        if (!$contract) {
            $this->redirect('/contracts');
        }

        $suppliers = Supplier::all();
        $users = User::all();
        // Pre-fetch responsibles to select in edit form
        $responsibles = ContractResponsible::getByContract($id);
        $responsibleIds = array_map(function($r) { return $r->user_id; }, $responsibles);

        $this->render('contracts/create', [
            'contract' => $contract,
            'suppliers' => $suppliers,
            'users' => $users,
            'responsibleIds' => $responsibleIds
        ]);
    }

    public function update()
    {
        $this->checkAuth();
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $this->redirect('/contracts');
        }
        $contract = Contract::find($id);
        if (!$contract) {
            $this->redirect('/contracts');
        }

        $contract->number = $_POST['number'];
        $contract->detailed_number = $_POST['detailed_number'];
        $contract->modality_code = $_POST['modality_code'];
        $contract->modality_name = $_POST['modality_name'];
        $contract->fiscal_name_raw = $_POST['fiscal_name_raw'];
        $contract->exercise = $_POST['exercise'];
        $contract->legal_basis = $_POST['legal_basis'];
        $contract->procedure_number = $_POST['procedure_number'];
        $contract->supplier_id = $_POST['supplier_id'] ?: null;

        $contract->value_total = str_replace(',', '.', str_replace('.', '', $_POST['value_total']));
        $contract->date_start = $_POST['date_start'];
        $contract->date_end_current = $_POST['date_end_current'];
        $contract->description_full = $_POST['description_full'];
        $contract->description_short = substr($_POST['description_full'], 0, 180);
        $contract->has_renewal = isset($_POST['has_renewal']) ? 1 : 0;
        $contract->max_renewals = $_POST['max_renewals'] ?: null;
        $contract->manager_user_id = $_POST['manager_user_id'] ?: null;

        $contract->calculateRiskAndNextAction();

        if ($contract->save()) {
             // Handle Responsibles - Simple strategy: delete all and recreate
             // Not efficient but works for small number
             $db = \App\Core\Database::getInstance()->getConnection();
             $stmt = $db->prepare("DELETE FROM contract_responsibles WHERE contract_id = :id");
             $stmt->execute(['id' => $contract->id]);

            if (!empty($_POST['responsibles']) && is_array($_POST['responsibles'])) {
                foreach ($_POST['responsibles'] as $userId) {
                     $resp = new ContractResponsible();
                     $resp->contract_id = $contract->id;
                     $resp->user_id = $userId;
                     $resp->role_in_contract = 'FISCAL';
                     $resp->save();
                }
            }

            Log::create($_SESSION['user_id'], 'UPDATE_CONTRACT', "Atualizou contrato {$contract->number}");
            $this->redirect('/contracts/view?id=' . $contract->id);
        } else {
             $this->redirect('/contracts/edit?id=' . $contract->id);
        }
    }

}
