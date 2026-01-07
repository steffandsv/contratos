<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Notification
{
    public $id;
    public $contract_id;
    public $user_id;
    public $type;
    public $title;
    public $message;
    public $send_channel;
    public $scheduled_for;
    public $sent_at;
    public $status;
    public $created_at;

    public static function getPendingSending()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM notifications WHERE status = 'PENDENTE' AND scheduled_for <= NOW()");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getForUser($userId, $channel = 'APP', $limit = 20)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id AND send_channel = :channel ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':channel', $channel, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        $data = [
            'contract_id' => $this->contract_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'send_channel' => $this->send_channel,
            'scheduled_for' => $this->scheduled_for,
            'sent_at' => $this->sent_at,
            'status' => $this->status ?? 'PENDENTE',
        ];

        if ($this->id) {
            $stmt = $db->prepare("UPDATE notifications SET sent_at = :sent_at, status = :status WHERE id = :id");
             return $stmt->execute([
                'sent_at' => $this->sent_at,
                'status' => $this->status,
                'id' => $this->id
            ]);
        } else {
             $stmt = $db->prepare("INSERT INTO notifications (contract_id, user_id, type, title, message, send_channel, scheduled_for, status) VALUES (:contract_id, :user_id, :type, :title, :message, :send_channel, :scheduled_for, :status)");
            $result = $stmt->execute($data);
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            return $result;
        }
    }
}
