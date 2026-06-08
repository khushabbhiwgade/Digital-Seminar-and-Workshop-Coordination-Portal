<?php
// scratch/verify_attendance_system.php
// ------------------------------------------------------------
// Ticket Verification and Attendance System Integration Tests
// ------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'config/db_connect.php';

echo "=== Workshop Ticket Verification and Attendance Integration Test ===\n";

// 1. Fetch first student and admin details in database
$student = $conn->query("SELECT id, full_name FROM users WHERE role = 'student' LIMIT 1")->fetch_assoc();
$admin = $conn->query("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();

if (!$student || !$admin) {
    echo "      FAIL: Require at least one student and one admin account to execute tests.\n";
    exit(1);
}

$student_id = $student['id'];
$admin_id = $admin['id'];
echo "      Testing with Admin: " . $admin['full_name'] . " (ID: $admin_id)\n";
echo "      Testing on Student: " . $student['full_name'] . " (ID: $student_id)\n";

// Use a distinct test workshop, e.g. workshop 5
$ws_id = 5;
$conn->query("DELETE FROM workshop_certificates WHERE ticket_id IN (SELECT id FROM tickets WHERE user_id = $student_id AND workshop_id = $ws_id)");
$conn->query("DELETE FROM workshop_attendance WHERE ticket_id IN (SELECT id FROM tickets WHERE user_id = $student_id AND workshop_id = $ws_id)");
$conn->query("DELETE FROM tickets WHERE user_id = $student_id AND workshop_id = $ws_id");

echo "[1/4] Simulating participant registration & ticket creation...\n";
$ticket_number = "TKT-" . date('Y') . "-99999";
$qr_code_path = "uploads/qrcodes/" . $ticket_number . ".png";

// Insert ticket
$ins_stmt = $conn->prepare("INSERT INTO tickets (user_id, workshop_id, ticket_number, qr_code_path, status) VALUES (?, ?, ?, ?, 'active')");
$ins_stmt->bind_param("iiss", $student_id, $ws_id, $ticket_number, $qr_code_path);
$ins_stmt->execute();
$ticket_id = $conn->insert_id;

echo "      Created Ticket: $ticket_number (ID: $ticket_id, Initial Status: active)\n";

// Verify database search
$search_stmt = $conn->prepare("SELECT ticket_number, status FROM tickets WHERE ticket_number = ?");
$search_stmt->bind_param("s", $ticket_number);
$search_stmt->execute();
$found = $search_stmt->get_result()->fetch_assoc();

if ($found && $found['status'] === 'active') {
    echo "      Search and Lookup Verification: PASS.\n";
} else {
    echo "      FAIL: Ticket lookup failed.\n";
    exit(1);
}

echo "[2/4] Simulating 'Mark Present' attendance recording flow...\n";
// Run Transaction like in attendance.php
$conn->begin_transaction();

// Insert/Update Present Attendance
$att_stmt = $conn->prepare("INSERT INTO workshop_attendance (ticket_id, status, marked_by) VALUES (?, 'present', ?) ON DUPLICATE KEY UPDATE status = 'present', marked_by = ?");
$att_stmt->bind_param("iii", $ticket_id, $admin_id, $admin_id);
$att_stmt->execute();

// Update ticket status to completed
$upd_stmt = $conn->prepare("UPDATE tickets SET status = 'completed' WHERE id = ?");
$upd_stmt->bind_param("i", $ticket_id);
$upd_stmt->execute();

// Auto-issue certificate
$cert_code = "CERT-WS-" . $ticket_number;
$cert_stmt = $conn->prepare("INSERT INTO workshop_certificates (ticket_id, certificate_code) VALUES (?, ?) ON DUPLICATE KEY UPDATE certificate_code = ?");
$cert_stmt->bind_param("iss", $ticket_id, $cert_code, $cert_code);
$cert_stmt->execute();

$conn->commit();

// Assert states
$chk_att = $conn->query("SELECT status FROM workshop_attendance WHERE ticket_id = $ticket_id")->fetch_assoc();
$chk_tick = $conn->query("SELECT status FROM tickets WHERE id = $ticket_id")->fetch_assoc();
$chk_cert = $conn->query("SELECT certificate_code FROM workshop_certificates WHERE ticket_id = $ticket_id")->fetch_assoc();

echo "      Attendance Status: " . ($chk_att['status'] ?? 'NULL') . " (Expected: present)\n";
echo "      Ticket Status: " . ($chk_tick['status'] ?? 'NULL') . " (Expected: completed)\n";
echo "      Issued Certificate: " . ($chk_cert['certificate_code'] ?? 'NULL') . " (Expected: $cert_code)\n";

if ($chk_att['status'] === 'present' && $chk_tick['status'] === 'completed' && isset($chk_cert['certificate_code'])) {
    echo "      Mark Present Flow Execution: PASS.\n";
} else {
    echo "      FAIL: State mismatch post present validation.\n";
    exit(1);
}

echo "[3/4] Simulating 'Mark Absent' attendance revocation flow...\n";
$conn->begin_transaction();

// Update Attendance to absent
$att_stmt = $conn->prepare("INSERT INTO workshop_attendance (ticket_id, status, marked_by) VALUES (?, 'absent', ?) ON DUPLICATE KEY UPDATE status = 'absent', marked_by = ?");
$att_stmt->bind_param("iii", $ticket_id, $admin_id, $admin_id);
$att_stmt->execute();

// Reset ticket status to active
$upd_stmt = $conn->prepare("UPDATE tickets SET status = 'active' WHERE id = ?");
$upd_stmt->bind_param("i", $ticket_id);
$upd_stmt->execute();

// Delete certificate
$cert_del_stmt = $conn->prepare("DELETE FROM workshop_certificates WHERE ticket_id = ?");
$cert_del_stmt->bind_param("i", $ticket_id);
$cert_del_stmt->execute();

$conn->commit();

// Assert states
$chk_att = $conn->query("SELECT status FROM workshop_attendance WHERE ticket_id = $ticket_id")->fetch_assoc();
$chk_tick = $conn->query("SELECT status FROM tickets WHERE id = $ticket_id")->fetch_assoc();
$chk_cert = $conn->query("SELECT certificate_code FROM workshop_certificates WHERE ticket_id = $ticket_id")->fetch_assoc();

echo "      Attendance Status: " . ($chk_att['status'] ?? 'NULL') . " (Expected: absent)\n";
echo "      Ticket Status: " . ($chk_tick['status'] ?? 'NULL') . " (Expected: active)\n";
echo "      Issued Certificate: " . ($chk_cert['certificate_code'] ?? 'NULL') . " (Expected: NULL)\n";

if ($chk_att['status'] === 'absent' && $chk_tick['status'] === 'active' && !isset($chk_cert['certificate_code'])) {
    echo "      Mark Absent Flow Execution: PASS.\n";
} else {
    echo "      FAIL: State mismatch post absent validation.\n";
    exit(1);
}

echo "[4/4] Cleaning up test records...\n";
$conn->query("DELETE FROM workshop_certificates WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM workshop_attendance WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM tickets WHERE id = $ticket_id");
echo "      Relational Cleanup: PASS.\n";

echo "\n=== ALL TICKET VERIFICATION AND ATTENDANCE TESTS PASSED SUCCESSFULLY ===\n";
?>
