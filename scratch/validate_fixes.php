<?php
// scratch/validate_fixes.php
// ------------------------------------------------------------
// E2E Validation Tests for Foreign Key Fixes
// ------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'includes/certificate_helper.php';

echo "=== DATABASE VALIDATION TESTS ===\n\n";

try {
    $conn->begin_transaction();

    // 1. Create a temporary student user
    $email = 'temp_student_test@college.edu';
    // Clean up if previous run left it
    $conn->query("DELETE FROM users WHERE email = '$email'");

    $ins_user = $conn->prepare("INSERT INTO users (email, full_name, mobile, role, college, department, year_of_study, is_verified) VALUES (?, 'Test Student', '1234567890', 'student', 'Test College', 'CSE', '3rd Year', 1)");
    $ins_user->bind_param("s", $email);
    $ins_user->execute();
    $student_id = $conn->insert_id;
    echo "1. Created temporary student. ID: $student_id\n";

    // 2. Fetch an active workshop
    $ws = $conn->query("SELECT id, title FROM workshops LIMIT 1")->fetch_assoc();
    if (!$ws) {
        throw new Exception("No workshops found to run the test.");
    }
    $workshop_id = intval($ws['id']);
    echo "2. Selected Workshop: {$ws['title']} (ID: $workshop_id)\n";

    // 3. Create ticket (Register participant)
    $ticket_number = "TKT-2026-TESTY";
    $qr_path = "uploads/qrcodes/" . $ticket_number . ".png";
    $ins_tk = $conn->prepare("INSERT INTO tickets (user_id, event_id, ticket_number, qr_code_path, status, registration_status) VALUES (?, ?, ?, ?, 'Verified', 'Verified')");
    $ins_tk->bind_param("iiss", $student_id, $workshop_id, $ticket_number, $qr_path);
    $ins_tk->execute();
    $ticket_id = $conn->insert_id;
    echo "3. Registered participant. Ticket ID: $ticket_id, Number: $ticket_number\n";

    $conn->commit();
    echo "   Transaction committed.\n\n";

    // --------------------------------------------------------
    // TEST 1: Mark Attendance
    // --------------------------------------------------------
    echo "--- TEST 1: Mark Attendance ---\n";
    $conn->begin_transaction();

    // Mark present (Simulating what admin/attendance/index.php does)
    $admin_id = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc()['id'] ?? null;
    if (!$admin_id) {
        throw new Exception("No admin user found to mark attendance.");
    }

    $ins_att = $conn->prepare("INSERT INTO attendance (ticket_id, user_id, event_id, attendance_status, marked_by) VALUES (?, ?, ?, 'Present', ?)");
    $ins_att->bind_param("iiii", $ticket_id, $student_id, $workshop_id, $admin_id);
    if ($ins_att->execute()) {
        echo "   SUCCESS: Attendance record created successfully.\n";
    } else {
        throw new Exception("FAIL: Failed to create attendance record: " . $conn->error);
    }

    // Update ticket status
    $upd_tk = $conn->prepare("UPDATE tickets SET status = 'Used', registration_status = 'Completed' WHERE id = ?");
    $upd_tk->bind_param("i", $ticket_id);
    $upd_tk->execute();

    $conn->commit();

    // Verify attendance row exists
    $chk_att = $conn->query("SELECT * FROM attendance WHERE ticket_id = $ticket_id")->fetch_assoc();
    if ($chk_att) {
        echo "   SUCCESS: Verified attendance record in DB. ID: {$chk_att['id']}, Status: {$chk_att['attendance_status']}\n\n";
    } else {
        throw new Exception("FAIL: Attendance record not found in database.");
    }

    // --------------------------------------------------------
    // TEST 2: Generate Certificate
    // --------------------------------------------------------
    echo "--- TEST 2: Generate Certificate ---\n";
    
    // Call generate_certificate helper
    $cert_res = generate_certificate($ticket_id, $admin_id);

    if ($cert_res['success']) {
        echo "   SUCCESS: Certificate generated successfully!\n";
        echo "   Certificate ID: " . $cert_res['certificate_id'] . "\n";
        echo "   Certificate No: " . $cert_res['certificate_no'] . "\n";
        echo "   Verification Code: " . $cert_res['verification_code'] . "\n";
        echo "   File Path: " . $cert_res['pdf_path'] . "\n";

        // Check if PDF file exists
        if (file_exists(ROOT_PATH . $cert_res['pdf_path'])) {
            echo "   SUCCESS: Verified PDF file exists on disk.\n\n";
            // Delete the generated test file
            @unlink(ROOT_PATH . $cert_res['pdf_path']);
        } else {
            echo "   WARNING: PDF file does not exist on disk.\n\n";
        }
        $cert_id = $cert_res['certificate_id'];
    } else {
        throw new Exception("FAIL: Certificate generation failed. Reason: " . $cert_res['message']);
    }

    // --------------------------------------------------------
    // TEST 3: Delete Participant
    // --------------------------------------------------------
    echo "--- TEST 3: Delete Participant (Cascaded Integrity Check) ---\n";
    
    // Delete the temporary user
    $conn->query("DELETE FROM users WHERE id = $student_id");
    echo "   Deleted student user ID: $student_id\n";

    // Verify related records are gone
    $chk_tk_del = $conn->query("SELECT COUNT(*) as cnt FROM tickets WHERE id = $ticket_id")->fetch_assoc()['cnt'];
    $chk_att_del = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE ticket_id = $ticket_id")->fetch_assoc()['cnt'];
    $chk_cert_del = $conn->query("SELECT COUNT(*) as cnt FROM certificates WHERE registration_id = $ticket_id")->fetch_assoc()['cnt'];

    echo "   Tickets remaining for test: $chk_tk_del (Expected: 0)\n";
    echo "   Attendance records remaining: $chk_att_del (Expected: 0)\n";
    echo "   Certificates remaining: $chk_cert_del (Expected: 0)\n";

    if ($chk_tk_del == 0 && $chk_att_del == 0 && $chk_cert_del == 0) {
        echo "   SUCCESS: Related attendance and certificate records handled correctly (cascaded delete confirmed)!\n\n";
    } else {
        throw new Exception("FAIL: Referential integrity deletion cascade failed.");
    }

    echo "=== ALL VALIDATION TESTS PASSED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    echo "!!! ERROR DURING VALIDATION: " . $e->getMessage() . "\n";
    // Rollback if active transaction
    @$conn->rollback();
    
    // Clean up just in case
    if (isset($student_id)) {
        $conn->query("DELETE FROM users WHERE id = $student_id");
    }
}
?>
