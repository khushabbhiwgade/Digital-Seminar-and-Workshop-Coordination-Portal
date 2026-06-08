<?php
require_once __DIR__ . '/../config/db_connect.php';

$possible_tables = ['workshop_certificates', 'workshop_attendance'];

foreach ($possible_tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        echo "Table: $table exists!\n";
        $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_assoc();
        echo $create['Create Table'] . "\n\n";
    } else {
        echo "Table: $table does not exist.\n";
    }
}
?>
