<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== REGISTRATION AND TICKET RECORD AUDIT ===\n";

$registrations = $conn->query("SELECT id, full_name, email, event_name, token FROM registrations")->fetch_all(MYSQLI_ASSOC);
echo "Registrations count: " . count($registrations) . "\n";
foreach ($registrations as $r) {
    echo "  - Reg ID: {$r['id']}, Name: {$r['full_name']}, Event: {$r['event_name']}, Token: {$r['token']}\n";
}

$tickets = $conn->query("SELECT id, user_id, event_id, ticket_number, status FROM tickets")->fetch_all(MYSQLI_ASSOC);
echo "\nTickets count: " . count($tickets) . "\n";
foreach ($tickets as $t) {
    echo "  - Ticket ID: {$t['id']}, User ID: {$t['user_id']}, Event ID: {$t['event_id']}, Number: {$t['ticket_number']}, Status: {$t['status']}\n";
}

$attendance = $conn->query("SELECT id, ticket_id, user_id, event_id, attendance_status FROM attendance")->fetch_all(MYSQLI_ASSOC);
echo "\nAttendance count: " . count($attendance) . "\n";
foreach ($attendance as $a) {
    echo "  - Attendance ID: {$a['id']}, ticket_id (references registrations.id): {$a['ticket_id']}, User ID: {$a['user_id']}, Event ID: {$a['event_id']}, Status: {$a['attendance_status']}\n";
}
?>
