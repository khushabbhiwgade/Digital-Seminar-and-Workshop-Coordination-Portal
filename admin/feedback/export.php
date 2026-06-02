<?php
// admin/feedback/export.php
// ------------------------------------------------------------
// Admin — Dynamic Feedback Exporter (CSV & PDF Reports - Part 15)
// ------------------------------------------------------------

$base_path = '../../';

require_once $base_path . 'includes/auth.php';
require_role('admin');

require_once $base_path . 'config/db_connect.php';

$format = $_GET['format'] ?? 'csv';
$workshop_id = intval($_GET['workshop_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$rating_range = $_GET['rating_range'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build dynamic query matching selected filters (exactly matching manage.php filters)
$query_parts = [];
$types = '';

$sql = "
    SELECT f.*, 
           u.full_name, u.email, 
           w.title as ws_title, w.speaker as ws_speaker, w.host as ws_host, w.start_date as ws_date
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN workshops w ON f.event_id = w.id
    WHERE 1=1
";

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR w.title LIKE ?)";
    $like_search = "%$search%";
    $query_parts[] = $like_search;
    $query_parts[] = $like_search;
    $types .= 'ss';
}

if ($workshop_id > 0) {
    $sql .= " AND f.event_id = ?";
    $query_parts[] = $workshop_id;
    $types .= 'i';
}

if (!empty($rating_range)) {
    if ($rating_range === 'high') {
        $sql .= " AND f.overall_rating >= 4";
    } elseif ($rating_range === 'mid') {
        $sql .= " AND f.overall_rating = 3";
    } elseif ($rating_range === 'low') {
        $sql .= " AND f.overall_rating <= 2";
    }
}

if (!empty($start_date)) {
    $sql .= " AND f.submitted_at >= ?";
    $query_parts[] = $start_date . ' 00:00:00';
    $types .= 's';
}

if (!empty($end_date)) {
    $sql .= " AND f.submitted_at <= ?";
    $query_parts[] = $end_date . ' 23:59:59';
    $types .= 's';
}

$sql .= " ORDER BY f.submitted_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$query_parts);
}
$stmt->execute();
$res = $stmt->get_result();

$data_list = [];
while ($row = $res->fetch_assoc()) {
    $data_list[] = $row;
}

// ------------------------------------------------------------
// EXPORT FORMAT 1: CSV Export
// ------------------------------------------------------------
if ($format === 'csv') {
    $filename = "Workshop_Feedback_Report_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Column Headers
    fputcsv($output, [
        'Feedback ID', 
        'Participant Name', 
        'Participant Email', 
        'Workshop Title', 
        'Overall Rating', 
        'Speaker Rating', 
        'Content Rating', 
        'Organization Rating', 
        'Venue Rating', 
        'Written Comments', 
        'Submission Timestamp'
    ]);
    
    foreach ($data_list as $row) {
        fputcsv($output, [
            $row['id'],
            $row['full_name'],
            $row['email'],
            $row['ws_title'],
            $row['overall_rating'],
            $row['speaker_rating'],
            $row['content_rating'],
            $row['organization_rating'],
            $row['venue_rating'],
            $row['comments'],
            $row['submitted_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// ------------------------------------------------------------
// EXPORT FORMAT 2: PDF Export (Part 15 - TCPDF Report)
// ------------------------------------------------------------
if ($format === 'pdf') {
    require_once $base_path . 'includes/tcpdf/tcpdf.php';
    
    $filename = "Workshop_Feedback_Report_" . date('Ymd_His') . ".pdf";

    // Setup custom headers/footers
    class ReportPDF extends TCPDF {
        public function Header() {
            $this->SetY(10);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(15, 23, 42); // slate (#0f172a)
            $this->Cell(0, 5, 'CAMPUS CONNECT PORTAL — EVALUATION REPORT', 0, 1, 'L');
            
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(148, 163, 184); // light gray (#94a3b8)
            $this->Cell(0, 4, 'Quality Assurance Evaluation & Analytics Desk', 0, 1, 'L');
            
            $this->SetDrawColor(226, 232, 240);
            $this->SetLineWidth(0.3);
            $this->Line(15, 20, 195, 20);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(148, 163, 184);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new ReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Campus Connect Portal');
    $pdf->SetTitle('Workshop Evaluation Report');
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    
    // Page Title
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 10, 'WORKSHOP EVALUATION REPORT', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(0, 4, 'Generated on ' . date('F d, Y h:i A'), 0, 1, 'C');
    $pdf->Ln(5);

    // Calculate aggregated statistics
    $total_reviews = count($data_list);
    $avg_overall = 0.0;
    $avg_speaker = 0.0;
    $avg_content = 0.0;
    $avg_org = 0.0;
    $avg_venue = 0.0;
    
    if ($total_reviews > 0) {
        $sum_overall = 0; $sum_speaker = 0; $sum_content = 0; $sum_org = 0; $sum_venue = 0;
        foreach ($data_list as $r) {
            $sum_overall += $r['overall_rating'];
            $sum_speaker += $r['speaker_rating'];
            $sum_content += $r['content_rating'];
            $sum_org += $r['organization_rating'];
            $sum_venue += $r['venue_rating'];
        }
        $avg_overall = $sum_overall / $total_reviews;
        $avg_speaker = $sum_speaker / $total_reviews;
        $avg_content = $sum_content / $total_reviews;
        $avg_org = $sum_org / $total_reviews;
        $avg_venue = $sum_venue / $total_reviews;
    }

    // 1. Render Aggregated Statistics Table
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(0, 8, '1. Aggregated Statistics Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    
    // Draw nice statistics HTML table
    $stats_html = '
    <table cellpadding="6" cellspacing="0" border="1" style="border-color: #cbd5e1;">
        <tr style="background-color: #f1f5f9; font-weight: bold; color: #1e293b;">
            <th width="70%">Evaluation Metric Category</th>
            <th width="30%" align="center">Average Score</th>
        </tr>
        <tr>
            <td>Overall Workshop Experience</td>
            <td align="center" style="font-weight: bold;">' . number_format($avg_overall, 2) . ' / 5.00</td>
        </tr>
        <tr>
            <td>Speaker &amp; Presenter Communication</td>
            <td align="center" style="font-weight: bold;">' . number_format($avg_speaker, 2) . ' / 5.00</td>
        </tr>
        <tr>
            <td>Technical Content Quality</td>
            <td align="center" style="font-weight: bold;">' . number_format($avg_content, 2) . ' / 5.00</td>
        </tr>
        <tr>
            <td>Session Organization &amp; Coordination</td>
            <td align="center" style="font-weight: bold;">' . number_format($avg_org, 2) . ' / 5.00</td>
        </tr>
        <tr>
            <td>Venue &amp; Digital Facilities</td>
            <td align="center" style="font-weight: bold;">' . number_format($avg_venue, 2) . ' / 5.00</td>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td>Total Evaluations Received</td>
            <td align="center" style="color: #0284c7;">' . $total_reviews . ' Reviews</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($stats_html, true, false, false, false, '');
    $pdf->Ln(5);

    // 2. Render Workshop Filter Context
    if ($workshop_id > 0 && isset($data_list[0])) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, '2. Workshop Information Context', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        $ws_html = '
        <table cellpadding="5" cellspacing="0" border="1" style="border-color: #cbd5e1;">
            <tr>
                <td width="30%" style="background-color: #f8fafc; font-weight: bold;">Workshop Title:</td>
                <td width="70%">' . htmlspecialchars($data_list[0]['ws_title']) . '</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Speaker / Presenter:</td>
                <td>' . htmlspecialchars($data_list[0]['ws_speaker']) . '</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Host Institution:</td>
                <td>' . htmlspecialchars($data_list[0]['ws_host']) . '</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Seminar Date:</td>
                <td>' . date('F d, Y', strtotime($data_list[0]['ws_date'])) . '</td>
            </tr>
        </table>';
        $pdf->writeHTML($ws_html, true, false, false, false, '');
        $pdf->Ln(5);
    }

    // 3. Render Individual Participant Reviews
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, '3. Individual Reviews & Participant Comments', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    
    if ($total_reviews > 0) {
        foreach ($data_list as $idx => $r) {
            $pdf->SetTextColor(51, 65, 85);
            $pdf->SetFont('helvetica', 'B', 9);
            $header_text = ($idx + 1) . ". Participant: " . $r['full_name'] . " (" . $r['email'] . ") | Submitted: " . date('M d, Y', strtotime($r['submitted_at']));
            $pdf->Cell(0, 5, $header_text, 0, 1, 'L');
            
            // Ratings line
            $pdf->SetFont('helvetica', '', 8);
            $ratings_text = "   Ratings -> Overall: " . $r['overall_rating'] . "/5 | Speaker: " . $r['speaker_rating'] . "/5 | Content: " . $r['content_rating'] . "/5 | Org: " . $r['organization_rating'] . "/5 | Venue: " . $r['venue_rating'] . "/5";
            $pdf->Cell(0, 4, $ratings_text, 0, 1, 'L');
            
            // Comment Box
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('courier', '', 8.5);
            
            $comment_box = '
            <table cellpadding="6" cellspacing="0" border="1" style="border-color: #e2e8f0; background-color: #f8fafc;">
                <tr>
                    <td style="font-style: italic; font-size: 8.5pt;">"' . nl2br(htmlspecialchars($r['comments'])) . '"</td>
                </tr>
            </table>';
            $pdf->writeHTML($comment_box, true, false, false, false, '');
            $pdf->Ln(2);
        }
    } else {
        $pdf->Cell(0, 6, 'No reviews matched the specified report criteria.', 0, 1, 'C');
    }

    // Output PDF dynamically to browser (inline view)
    $pdf->Output($filename, 'I');
    exit;
}
?>
