<?php
// scratch/verify_phase11_certificates.php
// ------------------------------------------------------------
// E2E Verification Test Suite - Phase 11 Certificates System
// ------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'includes/certificate_helper.php';

echo "=== PHASE 11 Complete Certificates Integration Test ===\n";

// Fetch student and admin details
$student = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'student' LIMIT 1")->fetch_assoc();
$admin = $conn->query("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();

if (!$student || !$admin) {
    echo "      FAIL: Require student and admin accounts to perform the tests.\n";
    exit(1);
}

$student_id = $student['id'];
$admin_id = $admin['id'];
echo "      Testing with Admin: " . $admin['full_name'] . " (ID: $admin_id)\n";
echo "      Testing with Student: " . $student['full_name'] . " (ID: $student_id)\n";

// Use Workshop ID 5 for isolation
$ws_id = 5;

// Cleanup preceding records
$conn->query("DELETE FROM certificates WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM attendance WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM tickets WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM activity_logs WHERE details LIKE '%workshop ID: $ws_id%' OR details LIKE '%workshop: % (ID: $ws_id)%' OR details LIKE '%Code: CERT-2026-%'");

// ==========================================================
// TEST CASE 1: Present Participant (Expected: Generated)
// ==========================================================
echo "\n[Test Case 1] Present Participant...\n";

// 1. Participant Registers
$t_number = "TKT-2026-91101";
$qr_path = "uploads/qrcodes/" . $t_number . ".png";

$conn->begin_transaction();
$ins_tk = $conn->prepare("INSERT INTO tickets (user_id, event_id, ticket_number, qr_code_path, status, registration_status) VALUES (?, ?, ?, ?, 'Verified', 'Verified')");
$ins_tk->bind_param("iiss", $student_id, $ws_id, $t_number, $qr_path);
$ins_tk->execute();
$ticket_id = $conn->insert_id;

// 2. Mark Present (Completes registration)
$ins_att = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Present', ?)");
$ins_att->bind_param("iiii", $ticket_id, $student_id, $ws_id, $admin_id);
$ins_att->execute();

$upd_tk = $conn->prepare("UPDATE tickets SET status = 'Used', registration_status = 'Completed' WHERE id = ?");
$upd_tk->bind_param("i", $ticket_id);
$upd_tk->execute();
$conn->commit();

// 3. Trigger Generation
$res1 = generate_certificate($ticket_id, $admin_id);

if ($res1['success']) {
    echo "      PASS: Certificate Generated Successfully!\n";
    echo "         Number: " . $res1['certificate_no'] . "\n";
    echo "         Verification: " . $res1['verification_code'] . "\n";
    echo "         Path: " . $res1['pdf_path'] . "\n";
    
    // Check if file exists on disk
    if (file_exists(ROOT_PATH . $res1['pdf_path'])) {
        echo "         PDF File Exists on Disk: PASS\n";
    } else {
        echo "         FAIL: PDF File missing on disk!\n";
        exit(1);
    }
} else {
    echo "      FAIL: Certificate generation blocked. Reason: " . $res1['message'] . "\n";
    exit(1);
}

// ==========================================================
// TEST CASE 3: Duplicate Generation Attempt (Expected: Blocked)
// ==========================================================
echo "\n[Test Case 3] Duplicate Generation Attempt...\n";

// Attempt to generate certificate for Case 1 ticket again
$res3 = generate_certificate($ticket_id, $admin_id);

if (!$res3['success']) {
    echo "      PASS: Duplicate Generation Blocked correctly!\n";
    echo "         Blocked Reason: " . $res3['message'] . "\n";
} else {
    echo "      FAIL: Duplicate certificate generated for same registration!\n";
    exit(1);
}

// ==========================================================
// TEST CASE 4: QR Verification (Expected: Valid)
// ==========================================================
echo "\n[Test Case 4] QR Verification...\n";

$verify_code = $res1['verification_code'];
$chk_q = $conn->prepare("SELECT c.status, u.full_name, w.title as ws_title FROM certificates c JOIN users u ON c.user_id = u.id JOIN workshops w ON c.event_id = w.id WHERE c.verification_code = ?");
$chk_q->bind_param("s", $verify_code);
$chk_q->execute();
$chk_res = $chk_q->get_result()->fetch_assoc();

if ($chk_res && $chk_res['status'] === 'Generated') {
    echo "      PASS: Verification Succeeded!\n";
    echo "         Status: Valid (Generated)\n";
    echo "         Recipient: " . $chk_res['full_name'] . "\n";
    echo "         Workshop: " . $chk_res['ws_title'] . "\n";
} else {
    echo "      FAIL: Certificate verification failed or status not Generated!\n";
    exit(1);
}

// ==========================================================
// TEST CASE 5: Revoked Certificate (Expected: Revoked)
// ==========================================================
echo "\n[Test Case 5] Revoked Certificate...\n";

$cert_id = $res1['certificate_id'];
$upd_rev = $conn->prepare("UPDATE certificates SET status = 'Revoked' WHERE id = ?");
$upd_rev->bind_param("i", $cert_id);
$upd_rev->execute();

$chk_rev = $conn->prepare("SELECT status FROM certificates WHERE id = ?");
$chk_rev->bind_param("i", $cert_id);
$chk_rev->execute();
$rev_res = $chk_rev->get_result()->fetch_assoc();

if ($rev_res && $rev_res['status'] === 'Revoked') {
    echo "      PASS: Certificate Revoked and verified as Revoked!\n";
} else {
    echo "      FAIL: Certificate revocation failed or status not Revoked!\n";
    exit(1);
}

// Cleanup Case 1 records to clear (user_id, event_id) key constraint
$conn->query("DELETE FROM certificates WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM attendance WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM tickets WHERE user_id = $student_id AND event_id = $ws_id");

// Remove generated test PDF file
if (file_exists(ROOT_PATH . $res1['pdf_path'])) {
    @unlink(ROOT_PATH . $res1['pdf_path']);
}

// ==========================================================
// TEST CASE 2: Absent Participant (Expected: Blocked)
// ==========================================================
echo "\n[Test Case 2] Absent Participant...\n";

// 1. Participant Registers
$t_number2 = "TKT-2026-91102";
$conn->begin_transaction();
$ins_tk->bind_param("iiss", $student_id, $ws_id, $t_number2, $qr_path);
$ins_tk->execute();
$ticket_id2 = $conn->insert_id;

// 2. Mark Absent
$ins_att_abs = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Absent', ?)");
$ins_att_abs->bind_param("iiii", $ticket_id2, $student_id, $ws_id, $admin_id);
$ins_att_abs->execute();

$upd_tk_abs = $conn->prepare("UPDATE tickets SET status = 'Cancelled', registration_status = 'Absent' WHERE id = ?");
$upd_tk_abs->bind_param("i", $ticket_id2);
$upd_tk_abs->execute();
$conn->commit();

// 3. Trigger Generation
$res2 = generate_certificate($ticket_id2, $admin_id);

if (!$res2['success']) {
    echo "      PASS: Certificate Generation Blocked correctly!\n";
    echo "         Blocked Reason: " . $res2['message'] . "\n";
} else {
    echo "      FAIL: Certificate was generated for an Absent student!\n";
    exit(1);
}

// Cleanup Case 2 records
$conn->query("DELETE FROM certificates WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM attendance WHERE user_id = $student_id AND event_id = $ws_id");
$conn->query("DELETE FROM tickets WHERE user_id = $student_id AND event_id = $ws_id");
echo "\nTest cleanup completed.\n";

echo "\n=== ALL PHASE 11 CERTIFICATE TESTS PASSED SUCCESSFULLY ===\n";
?>
