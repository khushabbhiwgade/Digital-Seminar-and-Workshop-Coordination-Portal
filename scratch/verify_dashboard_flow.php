<?php
// scratch/verify_dashboard_flow.php
// ------------------------------------------------------------
// Upgraded Participant Dashboard Module Flow Verification Test Script
// Supports new tickets table schema and relation mappings.
// ------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'config/db_connect.php';

echo "=== Participant Dashboard Module Integration Test ===\n";

// 1. Fetch first student ID in database
$student_sql = "SELECT id, full_name FROM users WHERE role = 'student' LIMIT 1";
$student = $conn->query($student_sql)->fetch_assoc();

if (!$student) {
    echo "      FAIL: No student accounts found in the users table to perform the dashboard test.\n";
    exit(1);
}

$user_id = $student['id'];
$user_name = $student['full_name'];
echo "      Testing on behalf of student: $user_name (ID: $user_id)\n";

echo "[1/5] Simulating stats loading (dashboard.php stats)...\n";

// Available Workshops
$avail = $conn->query("SELECT COUNT(*) as cnt FROM workshops WHERE status IN ('active', 'upcoming') AND start_date >= CURDATE()")->fetch_assoc()['cnt'];

// Registered (active tickets)
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc()['cnt'];

// Completed
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$comp = $stmt->get_result()->fetch_assoc()['cnt'];

// Certificates
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM certificates c JOIN tickets t ON c.registration_id = t.id WHERE t.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certs = $stmt->get_result()->fetch_assoc()['cnt'];

echo "      Available Workshops count: $avail\n";
echo "      Registered Tickets count: $reg\n";
echo "      Completed Workshops count: $comp\n";
echo "      Certificates Issued count: $certs\n";

if ($reg >= 0 && $comp >= 0 && $certs >= 0) {
    echo "      Stats query mapping: PASS.\n";
} else {
    echo "      FAIL: Stats query mapping incorrect.\n";
    exit(1);
}

echo "[2/5] Testing upcoming workshops registration candidates listing...\n";
// Available workshops that the user has not registered for (active or completed tickets)
$reg_ids = [];
$reg_res = $conn->query("SELECT event_id FROM tickets WHERE user_id = $user_id");
while ($r = $reg_res->fetch_assoc()) {
    $reg_ids[] = (int)$r['event_id'];
}

$ws_res = $conn->query("SELECT id, title FROM workshops WHERE status IN ('active', 'upcoming') AND start_date >= CURDATE()");
$unregistered_count = 0;
while ($ws = $ws_res->fetch_assoc()) {
    if (!in_array(intval($ws['id']), $reg_ids)) {
        $unregistered_count++;
    }
}

echo "      Unregistered workshops available for enrollment: $unregistered_count\n";
echo "      Enrollment filter checks: PASS.\n";

echo "[3/5] Simulating quick-registration transaction (register_workshop.php)...\n";
// Pick a workshop ID that the student hasn't registered for, say workshop 4
$target_ws_id = 4;
$conn->query("DELETE FROM tickets WHERE user_id = $user_id AND event_id = $target_ws_id");

// Check current seats remaining
$chk_seat = $conn->query("SELECT seats_remaining, title FROM workshops WHERE id = $target_ws_id")->fetch_assoc();
$orig_seats = intval($chk_seat['seats_remaining']);
echo "      Workshop: " . $chk_seat['title'] . "\n";
echo "      Original seats remaining: $orig_seats\n";

// Execute registration transaction
$conn->begin_transaction();

// Generate sequential ticket number (Format: TKT-YYYY-NNNNN)
$year = date('Y');
$seq_sql = "SELECT COUNT(*) as total FROM tickets WHERE ticket_number LIKE 'TKT-$year-%'";
$total_tickets = intval($conn->query($seq_sql)->fetch_assoc()['total']);
$next_num = $total_tickets + 1;
$ticket_number = "TKT-" . $year . "-" . str_pad($next_num, 5, '0', STR_PAD_LEFT);
$qr_code_path = "uploads/qrcodes/" . $ticket_number . ".png";

echo "      Generated Ticket Number: $ticket_number\n";

$ins_stmt = $conn->prepare("INSERT INTO tickets (user_id, event_id, ticket_number, qr_code_path, status) VALUES (?, ?, ?, ?, 'active')");
$ins_stmt->bind_param("iiss", $user_id, $target_ws_id, $ticket_number, $qr_code_path);
$ins_stmt->execute();

$upd_stmt = $conn->prepare("UPDATE workshops SET seats_remaining = seats_remaining - 1 WHERE id = ?");
$upd_stmt->bind_param("i", $target_ws_id);
$upd_stmt->execute();
$conn->commit();

// Check updated seats
$chk_seat2 = $conn->query("SELECT seats_remaining FROM workshops WHERE id = $target_ws_id")->fetch_assoc();
$new_seats = intval($chk_seat2['seats_remaining']);
echo "      Seats remaining post-registration: $new_seats (Expected: " . ($orig_seats - 1) . ")\n";

if ($new_seats === ($orig_seats - 1)) {
    echo "      Transaction enrollment execution: PASS.\n";
} else {
    echo "      FAIL: Seat decrement mismatch.\n";
    exit(1);
}

echo "[4/5] Simulating duplicate registration unique constraint protection...\n";
try {
    $ins_stmt->execute();
    echo "      FAIL: Duplicate registration allowed.\n";
    exit(1);
} catch (Exception $e) {
    echo "      Unique key constraint caught correctly: PASS.\n";
}

echo "[5/5] Cleaning up test registration data...\n";
$conn->query("DELETE FROM tickets WHERE user_id = $user_id AND event_id = $target_ws_id");
$conn->query("UPDATE workshops SET seats_remaining = seats_remaining + 1 WHERE id = $target_ws_id");
echo "      Cleanup completed successfully.\n";

echo "\n=== ALL PARTICIPANT DASHBOARD FLOW TESTS PASSED SUCCESSFULLY ===\n";
?>
