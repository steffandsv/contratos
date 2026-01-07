<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ContractResponsible
{
    public $id;
    public $contract_id;
    public $user_id;
    public $role_in_contract;
    public $created_at;

    public static function getByContract($contractId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT cr.*, u.name as user_name FROM contract_responsibles cr JOIN users u ON cr.user_id = u.id WHERE cr.contract_id = :contract_id");
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        if ($this->id) {
            // Usually not updated, only inserted or deleted
            return true;
        } else {
            $stmt = $db->prepare("INSERT INTO contract_responsibles (contract_id, user_id, role_in_contract) VALUES (:contract_id, :user_id, :role_in_contract)");
            $result = $stmt->execute([
                'contract_id' => $this->contract_id,
                'user_id' => $this->user_id,
                'role_in_contract' => $this->role_in_contract
            ]);
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            return $result;
        }
    }

    public static function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM contract_responsibles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
