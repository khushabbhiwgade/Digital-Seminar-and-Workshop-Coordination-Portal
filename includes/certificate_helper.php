<?php
// includes/certificate_helper.php
// ------------------------------------------------------------
// Core Certificate Engine (Automatic Generation & PDF Layout)
// ------------------------------------------------------------

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'includes/mail_helper.php';

/**
 * Validates eligibility, generates certificate numbers, saves a QR image,
 * renders a professional PDF using TCPDF, stores database record, and sends email.
 * 
 * @param int $registration_id The ticket ID (representing registration)
 * @param int|null $admin_id The ID of the admin who marked attendance / triggered generation
 * @return array Array with success status and details/error message
 */
function generate_certificate($registration_id, $admin_id = null) {
    global $conn;

    try {
        $registration_id = intval($registration_id);

        // 1. Fetch ticket, user, and workshop details
        $stmt = $conn->prepare("
            SELECT t.id, t.ticket_number, t.registration_status, t.user_id, t.event_id,
                   u.full_name, u.email,
                   w.title as ws_title, w.domain as ws_domain, w.host as ws_host, w.end_date as ws_date
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN workshops w ON t.event_id = w.id
            WHERE t.id = ?
        ");
        $stmt->bind_param("i", $registration_id);
        $stmt->execute();
        $tk = $stmt->get_result()->fetch_assoc();

        if (!$tk) {
            throw new Exception("Validation failed: Workshop registration file does not exist.");
        }

        $user_id = intval($tk['user_id']);
        $event_id = intval($tk['event_id']);
        $student_name = $tk['full_name'];
        $student_email = $tk['email'];
        $workshop_name = $tk['ws_title'];
        $workshop_domain = $tk['ws_domain'];
        $host_org = $tk['ws_host'];
        $completion_date = $tk['ws_date'];

        // 2. Validate Eligibility (Part 5)
        // Check 1: Attendance marked Present
        $att_stmt = $conn->prepare("SELECT attendance_status FROM attendance WHERE ticket_id = ?");
        $att_stmt->bind_param("i", $registration_id);
        $att_stmt->execute();
        $att_res = $att_stmt->get_result()->fetch_assoc();
        $attendance_status = $att_res ? $att_res['attendance_status'] : null;

        if ($attendance_status !== 'Present') {
            throw new Exception("Eligibility Blocked: Participant was not marked Present in attendance.");
        }

        // Check 2: Ticket registration status is Completed
        if ($tk['registration_status'] !== 'Completed') {
            throw new Exception("Eligibility Blocked: Registration status must be Completed. Current: " . $tk['registration_status']);
        }

        // Check 3: Certificate not already generated
        $cert_chk = $conn->prepare("SELECT id, certificate_no FROM certificates WHERE registration_id = ?");
        $cert_chk->bind_param("i", $registration_id);
        $cert_chk->execute();
        $existing_cert = $cert_chk->get_result()->fetch_assoc();

        if ($existing_cert) {
            throw new Exception("Duplicate Error: A certificate has already been generated for this registration (" . $existing_cert['certificate_no'] . ").");
        }

        // 3. Generate Certificate Number (Part 3)
        // Format: CERT-YYYY-000001
        $year = date('Y');
        $seq_stmt = $conn->prepare("SELECT certificate_no FROM certificates WHERE certificate_no LIKE ? ORDER BY id DESC LIMIT 1");
        $like_pattern = "CERT-$year-%";
        $seq_stmt->bind_param("s", $like_pattern);
        $seq_stmt->execute();
        $seq_res = $seq_stmt->get_result()->fetch_assoc();

        $next_num = 1;
        if ($seq_res) {
            $last_no = $seq_res['certificate_no'];
            $parts = explode('-', $last_no);
            if (count($parts) === 3) {
                $next_num = intval($parts[2]) + 1;
            }
        }
        $certificate_no = "CERT-" . $year . "-" . str_pad($next_num, 6, '0', STR_PAD_LEFT);

        // 4. Generate Verification Code (Part 4)
        // Format: VER-8F2A9C71
        $verification_code = '';
        while (true) {
            $rand_hex = strtoupper(bin2hex(random_bytes(4))); // 8 characters
            $verification_code = "VER-" . $rand_hex;

            // Ensure uniqueness
            $code_chk = $conn->prepare("SELECT id FROM certificates WHERE verification_code = ?");
            $code_chk->bind_param("s", $verification_code);
            $code_chk->execute();
            if ($code_chk->get_result()->num_rows === 0) {
                break;
            }
        }

        // Ensure directories exist
        $certificates_dir = ROOT_PATH . 'storage/certificates/';
        $qrcodes_dir = ROOT_PATH . 'storage/qrcodes/';
        if (!is_dir($certificates_dir)) mkdir($certificates_dir, 0777, true);
        if (!is_dir($qrcodes_dir)) mkdir($qrcodes_dir, 0777, true);

        // 5. Generate QR Code Image (Part 8)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $verification_url = "$protocol://$host/seminar_portal/verify_certificate.php?code=" . urlencode($verification_code);

        $qr_filename = $verification_code . '.png';
        $qr_absolute_path = $qrcodes_dir . $qr_filename;
        $qr_relative_path = 'storage/qrcodes/' . $qr_filename;

        // Fetch QR image from free secure goqr.me API
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verification_url);
        $ctx = stream_context_create(['http' => ['timeout' => 3.0]]);
        $qr_image = @file_get_contents($api_url, false, $ctx);

        if ($qr_image !== false) {
            file_put_contents($qr_absolute_path, $qr_image);
        } else {
            // Offline Safe Fallback: Write a vector SVG placeholder
            $fallback_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" fill="#f8fafc" stroke="#e2e8f0" stroke-width="4" rx="8"/>
                <rect x="25" y="25" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="35" y="35" width="20" height="20" fill="#0f172a"/>
                <rect x="135" y="25" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="145" y="35" width="20" height="20" fill="#0f172a"/>
                <rect x="25" y="135" width="40" height="40" fill="none" stroke="#0f172a" stroke-width="6"/>
                <rect x="35" y="145" width="20" height="20" fill="#0f172a"/>
                <rect x="85" y="45" width="10" height="20" fill="#0f172a"/>
                <rect x="105" y="25" width="15" height="10" fill="#0f172a"/>
                <rect x="85" y="85" width="30" height="30" fill="#0f172a"/>
                <rect x="45" y="85" width="15" height="15" fill="#0f172a"/>
                <rect x="135" y="85" width="20" height="15" fill="#0f172a"/>
                <rect x="145" y="145" width="20" height="20" fill="#0f172a"/>
                <rect x="85" y="145" width="25" height="15" fill="#0f172a"/>
                <text x="50%" y="65%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-weight="bold" font-size="10" fill="#eab308">OFFLINE VERIFY</text>
                <text x="50%" y="75%" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="8" fill="#64748b">' . htmlspecialchars($verification_code) . '</text>
            </svg>';
            file_put_contents($qr_absolute_path, $fallback_svg);
        }

        // 6. Generate PDF Certificate Design (Part 6 & Part 7)
        require_once ROOT_PATH . 'includes/tcpdf/tcpdf.php';

        $pdf_filename = $certificate_no . '.pdf';
        $pdf_absolute_path = $certificates_dir . $pdf_filename;
        $pdf_relative_path = 'storage/certificates/' . $pdf_filename;

        // Custom PDF subclass to block header/footer defaults
        class CertificatePDF extends TCPDF {
            public function Header() {
                // Custom elegant gold border around the entire certificate page
                $this->SetLineStyle(['width' => 1.5, 'color' => [234, 179, 8]]); // Gold border (#eab308)
                $this->Rect(8, 8, $this->getPageWidth() - 16, $this->getPageHeight() - 16);
                
                $this->SetLineStyle(['width' => 0.5, 'color' => [15, 23, 42]]); // Dark Blue inner border (#0f172a)
                $this->Rect(10, 10, $this->getPageWidth() - 20, $this->getPageHeight() - 20);
            }
            public function Footer() {
                // No default footer
            }
        }

        // Create new Landscape PDF A4 format
        $pdf = new CertificatePDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Setup document metadata
        $pdf->SetCreator('Campus Connect Portal');
        $pdf->SetAuthor('Digital Coordination Desk');
        $pdf->SetTitle('Certificate - ' . $certificate_no);
        $pdf->SetSubject('Workshop Completion Certificate');

        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(false);

        // Add a beautiful landscape page
        $pdf->AddPage();

        // 1. Portal Brand Logo & Subtitle
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(15, 23, 42); // Dark slate (#0f172a)
        $pdf->Cell(0, 8, 'CAMPUS CONNECT PORTAL', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(100, 116, 139); // Muted slate (#64748b)
        $pdf->Cell(0, 4, 'Digital Seminar and Workshop Coordination Desk', 0, 1, 'C');

        // Draw elegant visual divider
        $pdf->Ln(4);
        $pdf->SetDrawColor(234, 179, 8); // Gold (#eab308)
        $pdf->SetLineWidth(0.8);
        $pdf->Line(100, 31, 197, 31);
        
        $pdf->Ln(10);

        // 2. Certificate Title
        $pdf->SetFont('times', 'B', 28);
        $pdf->SetTextColor(15, 23, 42); // Dark slate
        $pdf->Cell(0, 12, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');
        
        $pdf->Ln(4);

        // 3. Content Text
        $pdf->SetFont('times', 'I', 15);
        $pdf->SetTextColor(51, 65, 85); // Slate (#334155)
        $pdf->Cell(0, 8, 'This is proudly presented to', 0, 1, 'C');

        // 4. Participant Name
        $pdf->Ln(2);
        $pdf->SetFont('times', 'B', 24);
        $pdf->SetTextColor(30, 41, 59); // Sleek dark slate
        $pdf->Cell(0, 10, strtoupper($student_name), 0, 1, 'C');

        // Draw underline under name
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(60, 93, 237, 93);

        // 5. Completion text
        $pdf->Ln(8);
        $pdf->SetFont('times', 'I', 14);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(0, 6, 'for successfully attending and completing the digital workshop on', 0, 1, 'C');

        // 6. Workshop Name & Domain
        $pdf->Ln(3);
        $pdf->SetFont('times', 'B', 18);
        $pdf->SetTextColor(234, 179, 8); // Elegant gold accent
        $pdf->Cell(0, 8, '"' . $workshop_name . '"', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(71, 85, 105); // Cool grey
        $pdf->Cell(0, 6, 'DOMAIN: ' . strtoupper($workshop_domain), 0, 1, 'C');

        // 7. Organization & Date Wording
        $pdf->Ln(6);
        $pdf->SetFont('times', 'I', 13);
        $pdf->SetTextColor(51, 65, 85);
        $date_formatted = date('F d, Y', strtotime($completion_date));
        $pdf->Cell(0, 6, 'hosted by ' . $host_org . ' on ' . $date_formatted . '.', 0, 1, 'C');

        // 8. Footer Section (Signature, QR Code, Verification Info)
        
        // Left Column: Certificate details
        $pdf->SetY(148);
        $pdf->SetX(20);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(80, 4, 'CERTIFICATE DETAILS', 0, 1, 'L');
        
        $pdf->SetFont('courier', '', 9);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetX(20);
        $pdf->Cell(80, 4, 'Number: ' . $certificate_no, 0, 1, 'L');
        $pdf->SetX(20);
        $pdf->Cell(80, 4, 'Verify: ' . $verification_code, 0, 1, 'L');

        // Middle Column: Elegant Simulated Authorized Signature
        $pdf->SetY(142);
        $pdf->SetX(100);
        $pdf->SetFont('times', 'I', 16);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(97, 8, 'Coordination Desk', 0, 1, 'C'); // simulated signature text
        
        $pdf->SetDrawColor(148, 163, 184); // line color
        $pdf->SetLineWidth(0.3);
        $pdf->Line(115, 151, 182, 151); // signature line
        
        $pdf->SetY(152);
        $pdf->SetX(100);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(97, 4, 'AUTHORIZED SIGNATURE', 0, 1, 'C');

        // Right Column: Embed QR Code image
        $pdf->Image($qr_absolute_path, 235, 140, 32, 32, 'PNG');
        
        $pdf->SetY(172);
        $pdf->SetX(215);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(70, 4, 'Scan to Verify Authenticity', 0, 1, 'C');

        // Save PDF certificate locally
        $pdf->Output($pdf_absolute_path, 'F');

        // 7. Write to database table 'certificates' (Part 2)
        $ins_cert = $conn->prepare("
            INSERT INTO certificates (user_id, event_id, registration_id, certificate_no, verification_code, certificate_path, generated_by, generated_at, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Generated')
        ");
        $ins_cert->bind_param("iiisssi", $user_id, $event_id, $registration_id, $certificate_no, $verification_code, $pdf_relative_path, $admin_id);
        
        if (!$ins_cert->execute()) {
            throw new Exception("Database Error: Failed to insert certificate record into database. Details: " . $conn->error);
        }

        $certificate_id = $conn->insert_id;

        // 8. Log activities in audit logs
        $log_action = "Certificate Generation";
        $log_details = "Certificate generated for student: $student_name in workshop: $workshop_name. Code: $certificate_no. Verification: $verification_code.";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->bind_param("iss", $admin_id, $log_action, $log_details);
        $log_stmt->execute();

        // 9. Send Email Integration (Part 15)
        $download_link = "$protocol://$host/seminar_portal/participant/certificates.php?action=download&id=" . $certificate_id;
        send_certificate_email($student_email, $student_name, $workshop_name, $download_link, $verification_url);

        return [
            'success' => true,
            'certificate_id' => $certificate_id,
            'certificate_no' => $certificate_no,
            'verification_code' => $verification_code,
            'pdf_path' => $pdf_relative_path
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
