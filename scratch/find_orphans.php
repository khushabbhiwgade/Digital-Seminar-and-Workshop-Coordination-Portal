<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== CHECKING FOR ORPHANED TICKETS / RECORDS ===\n";

// 1. Tickets with missing users
$res1 = $conn->query("SELECT t.id, t.user_id FROM tickets t LEFT JOIN users u ON t.user_id = u.id WHERE u.id IS NULL");
echo "Tickets with missing users: " . $res1->num_rows . "\n";
while ($row = $res1->fetch_assoc()) {
    echo "  - Ticket ID: {$row['id']} points to missing User ID: {$row['user_id']}\n";
}

// 2. Tickets with missing workshops
$res2 = $conn->query("SELECT t.id, t.event_id FROM tickets t LEFT JOIN workshops w ON t.event_id = w.id WHERE w.id IS NULL");
echo "Tickets with missing workshops: " . $res2->num_rows . "\n";
while ($row = $res2->fetch_assoc()) {
    echo "  - Ticket ID: {$row['id']} points to missing Workshop ID: {$row['event_id']}\n";
}

// 3. Certificates with missing users, workshops, or tickets
$res3 = $conn->query("SELECT c.id, c.registration_id FROM certificates c LEFT JOIN tickets t ON c.registration_id = t.id WHERE t.id IS NULL");
echo "Certificates with missing tickets (registration_id): " . $res3->num_rows . "\n";
while ($row = $res3->fetch_assoc()) {
    echo "  - Certificate ID: {$row['id']} points to missing Ticket ID: {$row['registration_id']}\n";
}

// 4. Attendance with missing tickets
$res4 = $conn->query("SELECT a.id, a.ticket_id FROM attendance a LEFT JOIN tickets t ON a.ticket_id = t.id WHERE t.id IS NULL");
echo "Attendance records with missing tickets: " . $res4->num_rows . "\n";
while ($row = $res4->fetch_assoc()) {
    echo "  - Attendance ID: {$row['id']} points to missing Ticket ID: {$row['ticket_id']}\n";
}

// 5. Feedback with missing tickets
$res5 = $conn->query("SELECT f.id, f.registration_id FROM feedback f LEFT JOIN tickets t ON f.registration_id = t.id WHERE t.id IS NULL");
echo "Feedback records with missing tickets (registration_id): " . $res5->num_rows . "\n";
while ($row = $res5->fetch_assoc()) {
    echo "  - Feedback ID: {$row['id']} points to missing Ticket ID: {$row['registration_id']}\n";
}

?>
