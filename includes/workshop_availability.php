<?php
// includes/workshop_availability.php
// ------------------------------------------------------------
// Workshop Capacity Management — Core Availability Engine
// ------------------------------------------------------------
// All seat calculations are DYNAMIC: capacity - COUNT(active tickets).
// Never relies on the static seats_remaining column.
// ------------------------------------------------------------

/**
 * Get live availability for a single workshop.
 *
 * @param mysqli $conn   Database connection
 * @param int    $workshop_id  Workshop ID
 * @return array|null  [id, title, capacity, registered, available, status] or null
 */
function get_workshop_availability($conn, $workshop_id) {
    $sql = "SELECT w.id, w.title, w.capacity, w.status as ws_status,
                   COALESCE(t.reg_count, 0) AS registered
            FROM workshops w
            LEFT JOIN (
                SELECT event_id, COUNT(*) AS reg_count
                FROM tickets
                WHERE registration_status NOT IN ('Cancelled', 'Rejected')
                GROUP BY event_id
            ) t ON w.id = t.event_id
            WHERE w.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $workshop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        return null;
    }
    
    $row = $result->fetch_assoc();
    $capacity = intval($row['capacity']);
    $registered = intval($row['registered']);
    $available = max(0, $capacity - $registered);
    $status = ($available > 0) ? 'Open' : 'Full';
    
    return [
        'id'         => intval($row['id']),
        'title'      => $row['title'],
        'capacity'   => $capacity,
        'registered' => $registered,
        'available'  => $available,
        'status'     => $status,
        'ws_status'  => $row['ws_status']
    ];
}

/**
 * Get live availability for all workshops (or filtered by status).
 *
 * @param mysqli $conn    Database connection
 * @param string $filter  Optional status filter ('active', 'upcoming', 'archived', or '' for all)
 * @return array  Array of availability records
 */
function get_all_workshops_availability($conn, $filter = '') {
    $sql = "SELECT w.id, w.title, w.domain, w.host, w.speaker, w.capacity, 
                   w.start_date, w.end_date, w.venue, w.poster_image, w.status as ws_status,
                   COALESCE(t.reg_count, 0) AS registered
            FROM workshops w
            LEFT JOIN (
                SELECT event_id, COUNT(*) AS reg_count
                FROM tickets
                WHERE registration_status NOT IN ('Cancelled', 'Rejected')
                GROUP BY event_id
            ) t ON w.id = t.event_id";
    
    $params = [];
    $types = "";
    
    if (!empty($filter)) {
        $sql .= " WHERE w.status = ?";
        $params[] = $filter;
        $types .= "s";
    }
    
    $sql .= " ORDER BY w.start_date ASC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $workshops = [];
    while ($row = $result->fetch_assoc()) {
        $capacity = intval($row['capacity']);
        $registered = intval($row['registered']);
        $available = max(0, $capacity - $registered);
        
        $row['capacity'] = $capacity;
        $row['registered'] = $registered;
        $row['available'] = $available;
        $row['status'] = ($available > 0) ? 'Open' : 'Full';
        
        $workshops[] = $row;
    }
    
    return $workshops;
}

/**
 * Get live availability for upcoming/active workshops only.
 *
 * @param mysqli $conn  Database connection
 * @param int    $limit Max records to return (0 = all)
 * @return array
 */
function get_upcoming_workshops_availability($conn, $limit = 0) {
    $sql = "SELECT w.id, w.title, w.domain, w.host, w.speaker, w.capacity,
                   w.start_date, w.venue, w.poster_image, w.status as ws_status,
                   COALESCE(t.reg_count, 0) AS registered
            FROM workshops w
            LEFT JOIN (
                SELECT event_id, COUNT(*) AS reg_count
                FROM tickets
                WHERE registration_status NOT IN ('Cancelled', 'Rejected')
                GROUP BY event_id
            ) t ON w.id = t.event_id
            WHERE w.status IN ('active', 'upcoming') AND w.start_date >= CURDATE()
            ORDER BY w.start_date ASC";
    
    if ($limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $workshops = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $capacity = intval($row['capacity']);
            $registered = intval($row['registered']);
            $available = max(0, $capacity - $registered);
            
            $row['capacity'] = $capacity;
            $row['registered'] = $registered;
            $row['available'] = $available;
            $row['status'] = ($available > 0) ? 'Open' : 'Full';
            
            $workshops[] = $row;
        }
    }
    
    return $workshops;
}

/**
 * Check if a workshop is full (atomic, suitable for use inside transactions).
 *
 * @param mysqli $conn         Database connection
 * @param int    $workshop_id  Workshop ID
 * @return bool  True if workshop is full
 */
function is_workshop_full($conn, $workshop_id) {
    $avail = get_workshop_availability($conn, $workshop_id);
    if (!$avail) return true; // workshop doesn't exist = treat as full
    return ($avail['available'] <= 0);
}

/**
 * Get count of active registrations for a workshop (for use in transactions with FOR UPDATE).
 *
 * @param mysqli $conn         Database connection
 * @param int    $workshop_id  Workshop ID
 * @param bool   $for_update   Whether to use FOR UPDATE lock
 * @return int   Count of active registrations
 */
function get_registration_count($conn, $workshop_id, $for_update = false) {
    $sql = "SELECT COUNT(*) as cnt FROM tickets WHERE event_id = ? AND registration_status NOT IN ('Cancelled', 'Rejected')";
    if ($for_update) {
        $sql .= " FOR UPDATE";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $workshop_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return intval($result['cnt']);
}
?>
