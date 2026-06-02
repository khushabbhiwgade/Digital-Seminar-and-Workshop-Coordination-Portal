<?php
// admin/workshops/delete.php
// ------------------------------------------------------------
// Secure workshop deletion controller with physical file cleanup
// ------------------------------------------------------------

require_once '../../includes/auth.php';
require_role('admin');

require_once '../../config/db_connect.php';

if (isset($_GET['id'])) {
    $workshop_id = intval($_GET['id']);

    // 1. Fetch workshop record to retrieve the associated poster filename
    $fetch_sql = "SELECT poster_image FROM workshops WHERE id = ?";
    $stmt = $conn->prepare($fetch_sql);
    $stmt->bind_param("i", $workshop_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $ws = $res->fetch_assoc();

        // 2. Permanently delete the physical poster image file from the server uploads folder if present
        if (!empty($ws['poster_image'])) {
            $file_path = '../../uploads/workshops/' . $ws['poster_image'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 3. Delete the workshop record from the database
        $delete_sql = "DELETE FROM workshops WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $workshop_id);
        $delete_stmt->execute();
    }
}

// Redirect back to management dashboard on completion
header("Location: manage.php?msg=delete_success");
exit;
