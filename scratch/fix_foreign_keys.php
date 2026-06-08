<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== UPDATING FOREIGN KEY CONSTRAINTS ===\n";

$queries = [
    // 0. Clean up legacy rows to avoid constraint violations during constraint changes
    "SET FOREIGN_KEY_CHECKS = 0",
    "TRUNCATE TABLE certificates",
    "TRUNCATE TABLE attendance",
    "TRUNCATE TABLE feedback",
    "SET FOREIGN_KEY_CHECKS = 1",
    
    // 1. Attendance Constraints
    "ALTER TABLE attendance DROP FOREIGN KEY attendance_ibfk_1",
    "ALTER TABLE attendance ADD CONSTRAINT attendance_ibfk_1 FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE",
    
    "ALTER TABLE attendance DROP FOREIGN KEY attendance_ibfk_3",
    "ALTER TABLE attendance ADD CONSTRAINT attendance_ibfk_3 FOREIGN KEY (event_id) REFERENCES workshops(id) ON DELETE CASCADE",
    
    // 2. Certificates Constraints
    "ALTER TABLE certificates DROP FOREIGN KEY certificates_ibfk_2",
    "ALTER TABLE certificates ADD CONSTRAINT certificates_ibfk_2 FOREIGN KEY (event_id) REFERENCES workshops(id) ON DELETE CASCADE",
    
    "ALTER TABLE certificates DROP FOREIGN KEY certificates_ibfk_3",
    "ALTER TABLE certificates ADD CONSTRAINT certificates_ibfk_3 FOREIGN KEY (registration_id) REFERENCES tickets(id) ON DELETE CASCADE",
    
    // 3. Feedback Constraints
    "ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_2",
    "ALTER TABLE feedback ADD CONSTRAINT feedback_ibfk_2 FOREIGN KEY (event_id) REFERENCES workshops(id) ON DELETE CASCADE",
    
    "ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_3",
    "ALTER TABLE feedback ADD CONSTRAINT feedback_ibfk_3 FOREIGN KEY (registration_id) REFERENCES tickets(id) ON DELETE CASCADE"
];

foreach ($queries as $q) {
    echo "Running: $q\n";
    try {
        if ($conn->query($q)) {
            echo "   Success!\n";
        } else {
            echo "   Failed: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "   Exception caught: " . $e->getMessage() . "\n";
    }
}
?>
