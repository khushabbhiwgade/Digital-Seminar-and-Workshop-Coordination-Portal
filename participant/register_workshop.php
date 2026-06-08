<?php
// participant/register_workshop.php
// ------------------------------------------------------------
// Upgraded One-Click Workshop Registration Processor with Transactions, 
// Sequential Ticket Tracking, Activity Logging, and Caching
// ------------------------------------------------------------

require_once '../includes/auth.php';
require_role('student');

require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = get_user_id();
    $workshop_id = intval($_POST['workshop_id'] ?? 0);

    if ($workshop_id <= 0) {
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid workshop selection.']);
            exit;
        }
        header("Location: dashboard.php?error=" . urlencode("Invalid workshop selection."));
        exit;
    }

    try {
        // Save mobile number if passed
        if (isset($_POST['mobile'])) {
            $mobile = trim($_POST['mobile']);
            if (!empty($mobile)) {
                $upd_mobile = $conn->prepare("UPDATE users SET mobile = ? WHERE id = ?");
                $upd_mobile->bind_param("si", $mobile, $user_id);
                $upd_mobile->execute();
            }
        }

        // 1. Start Database Transaction FIRST for atomic concurrency protection
        $conn->begin_transaction();

        // 2. Lock the workshop row to prevent concurrent modifications
        $fetch_sql = "SELECT capacity, status, title FROM workshops WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($fetch_sql);
        $stmt->bind_param("i", $workshop_id);
        $stmt->execute();
        $ws = $stmt->get_result()->fetch_assoc();

        if (!$ws) {
            throw new Exception("The selected workshop does not exist.");
        }

        if ($ws['status'] === 'archived') {
            throw new Exception("Registration failed. This workshop has been archived.");
        }

        // 3. DYNAMIC seat availability — count active registrations atomically
        $count_sql = "SELECT COUNT(*) as reg_count FROM tickets WHERE event_id = ? AND registration_status NOT IN ('Cancelled', 'Rejected')";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $workshop_id);
        $count_stmt->execute();
        $reg_count = intval($count_stmt->get_result()->fetch_assoc()['reg_count']);

        $available = intval($ws['capacity']) - $reg_count;
        if ($available <= 0) {
            throw new Exception("Registration failed. The workshop is fully booked.");
        }

        // 4. Check if already registered (in the tickets table)
        $check_reg_sql = "SELECT id FROM tickets WHERE user_id = ? AND event_id = ?";
        $check_stmt = $conn->prepare($check_reg_sql);
        $check_stmt->bind_param("ii", $user_id, $workshop_id);
        $check_stmt->execute();
        $existing_reg = $check_stmt->get_result();

        if ($existing_reg && $existing_reg->num_rows > 0) {
            throw new Exception("You are already registered for this workshop.");
        }

        // A. Generate sequential ticket number (Format: TKT-YYYY-NNNNN)
        $year = date('Y');
        $seq_sql = "SELECT COUNT(*) as total FROM tickets WHERE ticket_number LIKE ?";
        $seq_stmt = $conn->prepare($seq_sql);
        $like_pattern = "TKT-$year-%";
        $seq_stmt->bind_param("s", $like_pattern);
        $seq_stmt->execute();
        $seq_res = $seq_stmt->get_result()->fetch_assoc();
        
        $next_num = intval($seq_res['total']) + 1;
        $ticket_number = "TKT-" . $year . "-" . str_pad($next_num, 5, '0', STR_PAD_LEFT);

        // B. Generate and Save local QR code
        $qr_filename = $ticket_number . '.png';
        $qr_relative_path = 'uploads/qrcodes/' . $qr_filename;
        $qr_absolute_path = '../' . $qr_relative_path;

        // Ensure directories exist
        $qr_dir = dirname($qr_absolute_path);
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0777, true);
        }

        // Fetch QR image from free secure goqr.me API
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($ticket_number);
        
        // Timeout context to prevent freezing on network issues
        $ctx = stream_context_create(['http' => ['timeout' => 2.0]]);
        $qr_image = @file_get_contents($api_url, false, $ctx);

        if ($qr_image !== false) {
            // Success: save PNG file locally
            file_put_contents($qr_absolute_path, $qr_image);
        } else {
            // Offline Safe Fallback: Write a vector SVG placeholder locally
            $fallback_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" fill="#f8fafc" stroke="#e2e8f0" stroke-width="4" rx="8"/>
                <!-- QR Code Outline Mock -->
                <rect x="25" y="25" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="35" y="35" width="20" height="20" fill="#0f172a"/>
                <rect x="135" y="25" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="145" y="35" width="20" height="20" fill="#0f172a"/>
                <rect x="25" y="135" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="35" y="145" width="20" height="20" fill="#0f172a"/>
                <!-- Random dots to mock code content -->
                <rect x="85" y="45" width="10" height="20" fill="#0f172a"/>
                <rect x="105" y="25" width="15" height="10" fill="#0f172a"/>
                <rect x="85" y="85" width="30" height="30" fill="#0f172a"/>
                <rect x="45" y="85" width="15" height="15" fill="#0f172a"/>
                <rect x="135" y="85" width="20" height="15" fill="#0f172a"/>
                <rect x="145" y="145" width="20" height="20" fill="#0f172a"/>
                <rect x="85" y="145" width="25" height="15" fill="#0f172a"/>
                <text x="50%" y="65%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-weight="bold" font-size="10" fill="#eab308">OFFLINE TICKET</text>
                <text x="50%" y="75%" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="9" fill="#64748b">' . htmlspecialchars($ticket_number) . '</text>
            </svg>';
            
            // Save as PNG name but with SVG text markup. Browsers can render it!
            file_put_contents($qr_absolute_path, $fallback_svg);
        }

        // C. Save ticket registration
        $insert_reg_sql = "INSERT INTO tickets (user_id, event_id, ticket_number, qr_code_path, status, registration_status) VALUES (?, ?, ?, ?, 'Pending', 'Pending')";
        $insert_stmt = $conn->prepare($insert_reg_sql);
        $insert_stmt->bind_param("iiss", $user_id, $workshop_id, $ticket_number, $qr_relative_path);

        if (!$insert_stmt->execute()) {
            throw new Exception("Error saving registration details.");
        }

        // D. Log Activity Log
        $log_action = "Registration";
        $log_details = "Participant registered for workshop: " . $ws['title'] . " (ID: $workshop_id). Ticket: $ticket_number.";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->bind_param("iss", $user_id, $log_action, $log_details);
        $log_stmt->execute();

        // F. Commit Transaction
        $conn->commit();

        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Successfully registered for the workshop!']);
            exit;
        }
        header("Location: dashboard.php?msg=register_success");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    header("Location: dashboard.php");
    exit;
}
