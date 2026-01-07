<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Contract
{
    public $id;
    public $number;
    public $detailed_number;
    public $modality_code;
    public $modality_name;
    public $fiscal_name_raw;
    public $exercise;
    public $legal_basis;
    public $procedure_number;
    public $supplier_id;
    public $value_total;
    public $date_start;
    public $date_end_current;
    public $description_short;
    public $description_full;
    public $type_code;
    public $rateio_code;
    public $has_renewal;
    public $max_renewals;
    public $status_phase;
    public $status_risk;
    public $next_action_text;
    public $next_action_deadline;
    public $manager_user_id;
    public $created_by_user_id;
    public $created_at;
    public $updated_at;

    public static function find($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM contracts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchObject(self::class);
    }

    public static function findByNumberAndExercise($number, $exercise)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM contracts WHERE number = :number AND exercise = :exercise");
        $stmt->execute(['number' => $number, 'exercise' => $exercise]);
        return $stmt->fetchObject(self::class);
    }

    public static function countByActionDeadlineTodayOrOverdue()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM contracts WHERE next_action_deadline <= CURDATE()");
        return $stmt->fetchColumn();
    }

    public static function countByActionDeadlineNext30Days()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM contracts WHERE next_action_deadline > CURDATE() AND next_action_deadline <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        return $stmt->fetchColumn();
    }

    public static function countByResponsible($userId)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(DISTINCT c.id)
                FROM contracts c
                JOIN contract_responsibles cr ON c.id = cr.contract_id
                WHERE cr.user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchColumn();
    }

    public static function countHighRisk()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM contracts WHERE status_risk IN ('AGIR', 'CRITICO', 'IRREGULAR')");
        return $stmt->fetchColumn();
    }

    public static function all($filters = [])
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT c.*, s.name as supplier_name
                FROM contracts c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (c.number LIKE :search OR c.detailed_number LIKE :search OR c.description_short LIKE :search OR s.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status_phase'])) {
            $sql .= " AND c.status_phase = :status_phase";
            $params['status_phase'] = $filters['status_phase'];
        }

        if (!empty($filters['only_my_contracts']) && !empty($filters['user_id'])) {
             $sql .= " AND (EXISTS (SELECT 1 FROM contract_responsibles cr WHERE cr.contract_id = c.id AND cr.user_id = :user_id) OR c.manager_user_id = :user_id)";
             $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['only_pending_action'])) {
            $sql .= " AND c.next_action_deadline IS NOT NULL AND c.next_action_deadline >= CURDATE()";
        }

        if (!empty($filters['only_risk'])) {
            $sql .= " AND c.status_risk IN ('AGIR', 'CRITICO', 'IRREGULAR')";
        }

        if (!empty($filters['status_risk'])) {
             $sql .= " AND c.status_risk = :status_risk";
             $params['status_risk'] = $filters['status_risk'];
        }

        $sql .= " ORDER BY c.date_end_current ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getQueue($limit = 10)
    {
        $db = Database::getInstance()->getConnection();
        // Priority: CRITICO/IRREGULAR first, then by deadline
        $sql = "SELECT c.*, s.name as supplier_name
                FROM contracts c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                WHERE c.next_action_deadline IS NOT NULL
                ORDER BY FIELD(status_risk, 'IRREGULAR', 'CRITICO', 'AGIR', 'PLANEJAR', 'TRANQUILO'), next_action_deadline ASC
                LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getAlerts()
    {
        $db = Database::getInstance()->getConnection();
        $alerts = [];

        // 1. Expired and still VIGENTE
        $stmt = $db->query("SELECT id, number, description_short FROM contracts WHERE date_end_current < CURDATE() AND status_phase = 'VIGENTE'");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $alerts[] = [
                'type' => 'expired_vigente',
                'message' => "Contrato {$row['number']} vencido e ainda VIGENTE",
                'contract_id' => $row['id']
            ];
        }

        // 2. No fiscal/manager
        $sql = "SELECT c.id, c.number, c.description_short
                FROM contracts c
                WHERE c.fiscal_name_raw IS NULL OR c.fiscal_name_raw = ''
                AND NOT EXISTS (SELECT 1 FROM contract_responsibles cr WHERE cr.contract_id = c.id)";
        $stmt = $db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $alerts[] = [
                'type' => 'no_fiscal',
                'message' => "Contrato {$row['number']} sem fiscal vinculado",
                'contract_id' => $row['id']
            ];
        }

        // 3. Less than 30 days
        $sql = "SELECT id, number, description_short
                FROM contracts
                WHERE date_end_current BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND status_phase IN ('VIGENTE')";
        $stmt = $db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $alerts[] = [
                'type' => 'expiring_soon',
                'message' => "Contrato {$row['number']} vence em menos de 30 dias",
                'contract_id' => $row['id']
            ];
        }

        return $alerts;
    }

    public function calculateRiskAndNextAction()
    {
        $today = new \DateTime();
        $end = new \DateTime($this->date_end_current);
        $diff = $today->diff($end);
        $daysRemaining = (int)$diff->format('%r%a');

        // Status Risk Logic
        if (in_array($this->status_phase, ['ENCERRADO', 'RESCINDIDO', 'ANULADO'])) {
            $this->status_risk = 'TRANQUILO';
        } elseif ($daysRemaining < 0 && $this->status_phase == 'VIGENTE') {
            $this->status_risk = 'IRREGULAR';
        } else {
            // Check fiscal existence (simplified check, ideal would be to check db relation too, but for initial calc we check raw field)
            if (empty($this->fiscal_name_raw) && (!isset($this->id) || count(ContractResponsible::getByContract($this->id)) == 0)) {
                $this->status_risk = 'AGIR';
            } elseif ($daysRemaining > 90) {
                $this->status_risk = 'TRANQUILO';
            } elseif ($daysRemaining > 60) {
                $this->status_risk = 'PLANEJAR';
            } elseif ($daysRemaining > 30) {
                $this->status_risk = 'AGIR';
            } else {
                $this->status_risk = 'CRITICO';
            }
        }

        // Next Action Logic
        if ($this->status_phase == 'VIGENTE') {
            if ($this->has_renewal) {
                $this->next_action_text = 'Decidir prorrogação ou encerramento';
                // Deadline e.g. 45 days before end
                $this->next_action_deadline = (clone $end)->modify('-45 days')->format('Y-m-d');
            } else {
                $this->next_action_text = 'Planejar encerramento do contrato';
                $this->next_action_deadline = (clone $end)->modify('-45 days')->format('Y-m-d'); // Default deadline
            }
        } elseif ($this->status_phase == 'EM_PRORROGACAO') {
            $this->next_action_text = 'Concluir processo de prorrogação';
             $this->next_action_deadline = $this->date_end_current;
        } elseif ($this->status_phase == 'EM_ENCERRAMENTO') {
            $this->next_action_text = 'Concluir checklist de encerramento';
             $this->next_action_deadline = $this->date_end_current;
        }
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();

        $data = [
            'number' => $this->number,
            'detailed_number' => $this->detailed_number,
            'modality_code' => $this->modality_code,
            'modality_name' => $this->modality_name,
            'fiscal_name_raw' => $this->fiscal_name_raw,
            'exercise' => $this->exercise,
            'legal_basis' => $this->legal_basis,
            'procedure_number' => $this->procedure_number,
            'supplier_id' => $this->supplier_id,
            'value_total' => $this->value_total,
            'date_start' => $this->date_start,
            'date_end_current' => $this->date_end_current,
            'description_short' => $this->description_short,
            'description_full' => $this->description_full,
            'type_code' => $this->type_code,
            'rateio_code' => $this->rateio_code,
            'has_renewal' => $this->has_renewal,
            'max_renewals' => $this->max_renewals,
            'status_phase' => $this->status_phase,
            'status_risk' => $this->status_risk,
            'next_action_text' => $this->next_action_text,
            'next_action_deadline' => $this->next_action_deadline,
            'manager_user_id' => $this->manager_user_id,
            'created_by_user_id' => $this->created_by_user_id,
        ];

        if ($this->id) {
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            $setClauseString = implode(', ', $setClause);

            $sql = "UPDATE contracts SET $setClauseString WHERE id = :id";
            $data['id'] = $this->id;

            $stmt = $db->prepare($sql);
            return $stmt->execute($data);
        } else {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO contracts ($columns) VALUES ($placeholders)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            return $result;
        }
    }
}
