<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Log
{
    public $id;
    public $user_id;
    public $action;
    public $description;
    public $created_at;

    public static function create($userId, $action, $description)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->action = $action;
        $log->description = $description;
        $log->save();
        return $log;
    }

    public static function getByContract($contractId) {
        // Since logs table doesn't have contract_id, we usually assume description contains "Contract #ID" or similar,
        // or we need to add contract_id to logs table.
        // For now, let's just show logs for the user if we can't filter by contract strictly,
        // or we can implement a search in description.
        // Given the spec, let's try to search description.
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM logs l JOIN users u ON l.user_id = u.id WHERE l.description LIKE :search ORDER BY l.created_at DESC");
        $stmt->execute(['search' => "%Contract $contractId%"]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO logs (user_id, action, description) VALUES (:user_id, :action, :description)");
        $result = $stmt->execute([
            'user_id' => $this->user_id,
            'action' => $this->action,
            'description' => $this->description
        ]);
        if ($result) {
            $this->id = $db->lastInsertId();
        }
        return $result;
    }
}
