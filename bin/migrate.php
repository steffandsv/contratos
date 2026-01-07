<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');

    $db->exec($sql);
    echo "Migration completed successfully.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
