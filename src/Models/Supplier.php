<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Supplier
{
    public $id;
    public $document;
    public $name;
    public $created_at;
    public $updated_at;

    public static function find($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchObject(self::class);
    }

    public static function findByDocument($document)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE document = :document");
        $stmt->execute(['document' => $document]);
        return $stmt->fetchObject(self::class);
    }

    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM suppliers ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        if ($this->id) {
            $stmt = $db->prepare("UPDATE suppliers SET document = :document, name = :name WHERE id = :id");
            return $stmt->execute([
                'document' => $this->document,
                'name' => $this->name,
                'id' => $this->id
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO suppliers (document, name) VALUES (:document, :name)");
            $result = $stmt->execute([
                'document' => $this->document,
                'name' => $this->name
            ]);
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            return $result;
        }
    }
}
