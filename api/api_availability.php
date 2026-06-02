<?php
// api/api_availability.php
// ------------------------------------------------------------
// AJAX JSON Endpoint — Live Workshop Availability
// ------------------------------------------------------------
// Returns real-time seat availability calculated dynamically.
// Supports single workshop (?id=X) or all workshops (no param).
// Polled by frontend JS every 5 seconds.
// ------------------------------------------------------------

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/config/db_connect.php';
require_once dirname(__DIR__) . '/includes/workshop_availability.php';

// Single workshop mode
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $workshop_id = intval($_GET['id']);
    $avail = get_workshop_availability($conn, $workshop_id);
    
    if ($avail) {
        echo json_encode([
            'success'    => true,
            'workshop'   => [
                'id'         => $avail['id'],
                'title'      => $avail['title'],
                'capacity'   => $avail['capacity'],
                'registered' => $avail['registered'],
                'available'  => $avail['available'],
                'status'     => $avail['status']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Workshop not found.']);
    }
    exit;
}

// All workshops mode
$workshops = get_all_workshops_availability($conn);

$output = [];
foreach ($workshops as $ws) {
    $output[] = [
        'id'         => $ws['id'],
        'title'      => $ws['title'],
        'capacity'   => $ws['capacity'],
        'registered' => $ws['registered'],
        'available'  => $ws['available'],
        'status'     => $ws['status']
    ];
}

echo json_encode([
    'success'   => true,
    'workshops' => $output,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
