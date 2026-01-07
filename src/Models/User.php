<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public $id;
    public $name;
    public $email;
    public $password_hash;
    public $role;
    public $is_active;
    public $created_at;
    public $updated_at;

    public static function find($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchObject(self::class);
    }

    public static function findByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchObject(self::class);
    }

    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM users ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function save()
    {
        $db = Database::getInstance()->getConnection();
        if ($this->id) {
            $stmt = $db->prepare("UPDATE users SET name = :name, email = :email, password_hash = :password_hash, role = :role, is_active = :is_active WHERE id = :id");
            return $stmt->execute([
                'name' => $this->name,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'role' => $this->role,
                'is_active' => $this->is_active,
                'id' => $this->id
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, :role, :is_active)");
            $result = $stmt->execute([
                'name' => $this->name,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'role' => $this->role,
                'is_active' => $this->is_active ?? 1
            ]);
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            return $result;
        }
    }
}
