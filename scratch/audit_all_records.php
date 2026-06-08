<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== COUNTING RECORDS ===\n";
foreach (['attendance', 'certificates', 'feedback'] as $table) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM `$table`")->fetch_assoc();
    echo "$table count: " . $res['cnt'] . "\n";
    if ($res['cnt'] > 0) {
        $rows = $conn->query("SELECT * FROM `$table` LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        echo "Sample data for $table:\n";
        print_r($rows);
    }
}
?>
