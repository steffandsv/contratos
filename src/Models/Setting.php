<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    public $id;
    public $name;
    public $value;
    public $updated_at;

    public static function get($name, $default = null)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE name = :name");
        $stmt->execute(['name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : $default;
    }

    public static function set($name, $value)
    {
        $db = Database::getInstance()->getConnection();

        // Check if exists
        $stmt = $db->prepare("SELECT id FROM settings WHERE name = :name");
        $stmt->execute(['name' => $name]);

        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE settings SET value = :value WHERE name = :name");
        } else {
            $stmt = $db->prepare("INSERT INTO settings (name, value) VALUES (:name, :value)");
        }

        return $stmt->execute(['name' => $name, 'value' => $value]);
    }
}
