<?php
require_once __DIR__ . '/../config/db_connect.php';

$tables = [
    'users',
    'workshops',
    'events',
    'registrations',
    'attendance',
];

echo "=== DATABASE SCHEMA AUDIT ===\n\n";

foreach ($tables as $table) {
    echo "Table: $table\n";
    echo "========================================\n";
    
    // Check if table exists
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows === 0) {
        echo "STATUS: MISSING\n\n";
        continue;
    }
    
    // Get columns
    $columns = $conn->query("DESCRIBE `$table`")->fetch_all(MYSQLI_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo sprintf(
            "  - %s: %s (Null: %s, Key: %s, Default: %s, Extra: %s)\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key'],
            $col['Default'] ?? 'NULL',
            $col['Extra']
        );
    }
    
    // Get Create Table statement
    $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_assoc();
    echo "\nCreate Table SQL:\n";
    echo $create['Create Table'] . "\n\n";
    echo "----------------------------------------\n\n";
}
?>
