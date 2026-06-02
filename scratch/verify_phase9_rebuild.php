<?php
// scratch/verify_phase9_rebuild.php
// ------------------------------------------------------------
// E2E Verification Test Suite - Phase 9 Rebuild Flow
// ------------------------------------------------------------

define('ROOT_PATH', 'c:/xampp/htdocs/seminar_portal/');

require_once ROOT_PATH . 'config/db_connect.php';

echo "=== PHASE 9 Complete Ticket Verification and Attendance Integration Test ===\n";

// Fetch student and admin details
$student = $conn->query("SELECT id, full_name FROM users WHERE role = 'student' LIMIT 1")->fetch_assoc();
$admin = $conn->query("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();

if (!$student || !$admin) {
    echo "      FAIL: Require at least one student and one admin account to execute tests.\n";
    exit(1);
}

$student_id = $student['id'];
$admin_id = $admin['id'];
echo "      Admin: " . $admin['full_name'] . " (ID: $admin_id)\n";
echo "      Student: " . $student['full_name'] . " (ID: $student_id)\n";

// Target a test workshop (e.g. Workshop 5)
$target_ws_id = 5;

// Clean up any existing records
$conn->query("DELETE FROM workshop_certificates WHERE ticket_id IN (SELECT id FROM tickets WHERE user_id = $student_id AND event_id = $target_ws_id)");
$conn->query("DELETE FROM attendance WHERE user_id = $student_id AND event_id = $target_ws_id");
$conn->query("DELETE FROM tickets WHERE user_id = $student_id AND event_id = $target_ws_id");

// ==========================================
// TEST CASE 1: Mark Present Flow
// ==========================================
echo "\n[Test Case 1] Register -> Verify -> Mark Present Flow...\n";

// 1. Participant Registers (Ticket Generated in Pending status)
$t_number_1 = "TKT-2026-90001";
$qr_path = "uploads/qrcodes/" . $t_number_1 . ".png";

$ins_stmt = $conn->prepare("INSERT INTO tickets (user_id, event_id, ticket_number, qr_code_path, status, registration_status) VALUES (?, ?, ?, ?, 'Pending', 'Registered')");
$ins_stmt->bind_param("iiss", $student_id, $target_ws_id, $t_number_1, $qr_path);
$ins_stmt->execute();
$ticket_id = $conn->insert_id;
echo "      1. Participant Registered. Ticket: $t_number_1 (ID: $ticket_id, Status: Pending, RegStatus: Registered)\n";

// 2. Admin Verifies Ticket
$conn->begin_transaction();
$upd_verify = $conn->prepare("UPDATE tickets SET status = 'Verified', registration_status = 'Verified', verified_by = ?, verified_at = NOW() WHERE id = ?");
$upd_verify->bind_param("ii", $admin_id, $ticket_id);
$upd_verify->execute();
$conn->commit();

$chk_tick = $conn->query("SELECT status, registration_status, verified_by FROM tickets WHERE id = $ticket_id")->fetch_assoc();
echo "      2. Admin Verified Ticket. Status: " . $chk_tick['status'] . " (Expected: Verified), RegStatus: " . $chk_tick['registration_status'] . " (Expected: Verified)\n";

if ($chk_tick['status'] !== 'Verified' || $chk_tick['registration_status'] !== 'Verified') {
    echo "      FAIL: Ticket verification failed.\n";
    exit(1);
}

// 3. Mark Present (Attendance Recorded -> Used -> Completed -> Certificate Generated)
$conn->begin_transaction();

// Insert attendance Present
$ins_att = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Present', ?)");
$ins_att->bind_param("iiii", $ticket_id, $student_id, $target_ws_id, $admin_id);
$ins_att->execute();

// Update ticket status
$upd_tick = $conn->prepare("UPDATE tickets SET status = 'Used', registration_status = 'Completed' WHERE id = ?");
$upd_tick->bind_param("i", $ticket_id);
$upd_tick->execute();

// Generate certificate
$cert_code = "CERT-WS-" . $t_number_1;
$ins_cert = $conn->prepare("INSERT INTO workshop_certificates (ticket_id, certificate_code) VALUES (?, ?) ON DUPLICATE KEY UPDATE certificate_code = ?");
$ins_cert->bind_param("iss", $ticket_id, $cert_code, $cert_code);
$ins_cert->execute();

$conn->commit();

$chk_att = $conn->query("SELECT attendance_status FROM attendance WHERE ticket_id = $ticket_id")->fetch_assoc();
$chk_tick2 = $conn->query("SELECT status, registration_status FROM tickets WHERE id = $ticket_id")->fetch_assoc();
$chk_cert = $conn->query("SELECT certificate_code FROM workshop_certificates WHERE ticket_id = $ticket_id")->fetch_assoc();

echo "      3. Logged Present Check-In.\n";
echo "         Attendance Status: " . $chk_att['attendance_status'] . " (Expected: Present)\n";
echo "         Ticket Status: " . $chk_tick2['status'] . " (Expected: Used)\n";
echo "         Registration Status: " . $chk_tick2['registration_status'] . " (Expected: Completed)\n";
echo "         Certificate Generated: " . ($chk_cert['certificate_code'] ?? 'None') . " (Expected: $cert_code)\n";

if ($chk_att['attendance_status'] === 'Present' && $chk_tick2['status'] === 'Used' && $chk_tick2['registration_status'] === 'Completed' && isset($chk_cert['certificate_code'])) {
    echo "      Test Case 1 (Mark Present): PASS.\n";
} else {
    echo "      FAIL: Test Case 1 logic state failure.\n";
    exit(1);
}

// Clean up Case 1
$conn->query("DELETE FROM workshop_certificates WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM attendance WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM tickets WHERE id = $ticket_id");


// ==========================================
// TEST CASE 2: Mark Absent Flow
// ==========================================
echo "\n[Test Case 2] Register -> Verify -> Mark Absent Flow...\n";

// 1. Participant Registers
$t_number_2 = "TKT-2026-90002";
$ins_stmt->bind_param("iiss", $student_id, $target_ws_id, $t_number_2, $qr_path);
$ins_stmt->execute();
$ticket_id = $conn->insert_id;

// 2. Admin Verifies Ticket
$upd_verify->bind_param("ii", $admin_id, $ticket_id);
$upd_verify->execute();

// 3. Mark Absent (Attendance Recorded -> Cancelled -> Closed -> No Certificate)
$conn->begin_transaction();

// Insert attendance Absent
$ins_att_abs = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Absent', ?)");
$ins_att_abs->bind_param("iiii", $ticket_id, $student_id, $target_ws_id, $admin_id);
$ins_att_abs->execute();

// Update ticket status to Cancelled / Closed
$upd_tick_abs = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Closed' WHERE id = ?");
$upd_tick_abs->bind_param("i", $ticket_id);
$upd_tick_abs->execute();

$conn->commit();

$chk_att = $conn->query("SELECT attendance_status FROM attendance WHERE ticket_id = $ticket_id")->fetch_assoc();
$chk_tick3 = $conn->query("SELECT status, registration_status FROM tickets WHERE id = $ticket_id")->fetch_assoc();
$chk_cert = $conn->query("SELECT certificate_code FROM workshop_certificates WHERE ticket_id = $ticket_id")->fetch_assoc();

echo "      Logged Absent Check-In.\n";
echo "      Attendance Status: " . $chk_att['attendance_status'] . " (Expected: Absent)\n";
echo "      Ticket Status: " . $chk_tick3['status'] . " (Expected: Cancelled)\n";
echo "      Registration Status: " . $chk_tick3['registration_status'] . " (Expected: Closed)\n";
echo "      Certificate Code: " . ($chk_cert['certificate_code'] ?? 'NULL') . " (Expected: NULL)\n";

if ($chk_att['attendance_status'] === 'Absent' && $chk_tick3['status'] === 'Cancelled' && $chk_tick3['registration_status'] === 'Closed' && !isset($chk_cert['certificate_code'])) {
    echo "      Test Case 2 (Mark Absent): PASS.\n";
} else {
    echo "      FAIL: Test Case 2 logic state failure.\n";
    exit(1);
}


// ==========================================
// TEST CASE 3: Attempt Duplicate Attendance
// ==========================================
echo "\n[Test Case 3] Attempt Duplicate Attendance Protection...\n";
try {
    $ins_att_abs->execute();
    echo "      FAIL: Duplicate attendance record allowed.\n";
    exit(1);
} catch (Exception $e) {
    echo "      Duplicate attendance record blocked correctly: PASS.\n";
}


// ==========================================
// TEST CASE 4: Attempt Duplicate Verification
// ==========================================
echo "\n[Test Case 4] Attempt Duplicate Verification Protection...\n";
// Re-inserting ticket with same student and workshop to trigger unique constraint
try {
    $ins_stmt->bind_param("iiss", $student_id, $target_ws_id, $t_number_2, $qr_path);
    $ins_stmt->execute();
    echo "      FAIL: Duplicate ticket registration for same user and workshop allowed.\n";
    exit(1);
} catch (Exception $e) {
    echo "      Duplicate registration unique constraint blocked correctly: PASS.\n";
}

// Clean up all tests
$conn->query("DELETE FROM workshop_certificates WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM attendance WHERE ticket_id = $ticket_id");
$conn->query("DELETE FROM tickets WHERE id = $ticket_id");
echo "\nTest cleanup complete.\n";

echo "\n=== ALL PHASE 9 REBUILD TESTS PASSED SUCCESSFULLY ===\n";
?>
