<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'admin_common.php';

$adminId = intval($_GET['admin_id'] ?? 0);
if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

$stats = [
    'users_active' => 0,
    'users_banned' => 0,
    'owners_verified' => 0,
    'owners_suspended' => 0,
    'owners_pending' => 0,
    'pending_turfs' => 0,
    'pending_bookings' => 0,
    'pending_refunds' => 0,
    'today_revenue' => 0
];

$monitorUsers = [];
$monitorOwners = [];
$pendingTurfs = [];
$pendingBookings = [];
$pendingRefunds = [];
$analytics = [
    'top_rated_turfs' => [],
    'most_active_users' => [],
    'revenue_trend' => [],
    'booking_growth' => []
];

if (table_exists($conn, 'users')) {
    $sql = "SELECT
              SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS users_active,
              SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) AS users_banned
            FROM users";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    if ($row) {
        $stats['users_active'] = (int)($row['users_active'] ?? 0);
        $stats['users_banned'] = (int)($row['users_banned'] ?? 0);
    }
}

if (table_exists($conn, 'turf_owners')) {
    $sql = "SELECT
              SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) AS owners_verified,
              SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS owners_suspended,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS owners_pending
            FROM turf_owners";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    if ($row) {
        $stats['owners_verified'] = (int)($row['owners_verified'] ?? 0);
        $stats['owners_suspended'] = (int)($row['owners_suspended'] ?? 0);
        $stats['owners_pending'] = (int)($row['owners_pending'] ?? 0);
    }
}

if (table_exists($conn, 'turfs')) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM turfs WHERE status = 'pending'");
    $row = $res ? $res->fetch_assoc() : null;
    $stats['pending_turfs'] = (int)($row['c'] ?? 0);
}

if (table_exists($conn, 'bookings')) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE booking_status = 'pending'");
    $row = $res ? $res->fetch_assoc() : null;
    $stats['pending_bookings'] = (int)($row['c'] ?? 0);

    $revRes = $conn->query("SELECT COALESCE(SUM(booked_price), 0) AS amount
                            FROM bookings
                            WHERE booking_status IN ('confirmed', 'completed')
                              AND DATE(created_at) = CURDATE()");
    $revRow = $revRes ? $revRes->fetch_assoc() : null;
    $stats['today_revenue'] = (float)($revRow['amount'] ?? 0);
}

if (table_exists($conn, 'refund_requests')) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM refund_requests WHERE status = 'pending'");
    $row = $res ? $res->fetch_assoc() : null;
    $stats['pending_refunds'] = (int)($row['c'] ?? 0);
}

if (table_exists($conn, 'users') && table_exists($conn, 'bookings')) {
    $userSql = "SELECT
                  u.user_id,
                  u.full_name,
                  u.email,
                  u.status,
                  COUNT(b.booking_id) AS total_bookings,
                  SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
                  SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
                  MAX(b.created_at) AS last_booking_at
                FROM users u
                LEFT JOIN bookings b ON b.user_id = u.user_id
                GROUP BY u.user_id, u.full_name, u.email, u.status
                ORDER BY total_bookings DESC, u.user_id DESC
                LIMIT 12";
    $userRes = $conn->query($userSql);
    while ($row = $userRes ? $userRes->fetch_assoc() : null) {
        if (!$row) break;
        $monitorUsers[] = [
            'user_id' => (int)$row['user_id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'status' => $row['status'],
            'total_bookings' => (int)($row['total_bookings'] ?? 0),
            'confirmed_bookings' => (int)($row['confirmed_bookings'] ?? 0),
            'completed_bookings' => (int)($row['completed_bookings'] ?? 0),
            'last_booking_at' => $row['last_booking_at']
        ];
    }
}

if (table_exists($conn, 'turf_owners') && table_exists($conn, 'turfs')) {
    $ownerSql = "SELECT
                  o.owner_id,
                  o.owner_name,
                  o.email,
                  o.status,
                  COUNT(t.turf_id) AS total_turfs,
                  SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) AS active_turfs,
                  SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) AS pending_turfs
                FROM turf_owners o
                LEFT JOIN turfs t ON t.owner_id = o.owner_id
                GROUP BY o.owner_id, o.owner_name, o.email, o.status
                ORDER BY total_turfs DESC, o.owner_id DESC
                LIMIT 12";
    $ownerRes = $conn->query($ownerSql);
    while ($row = $ownerRes ? $ownerRes->fetch_assoc() : null) {
        if (!$row) break;
        $monitorOwners[] = [
            'owner_id' => (int)$row['owner_id'],
            'owner_name' => $row['owner_name'],
            'email' => $row['email'],
            'status' => $row['status'],
            'total_turfs' => (int)($row['total_turfs'] ?? 0),
            'active_turfs' => (int)($row['active_turfs'] ?? 0),
            'pending_turfs' => (int)($row['pending_turfs'] ?? 0)
        ];
    }
}

if (table_exists($conn, 'turfs') && table_exists($conn, 'turf_owners')) {
    $turfSql = "SELECT
                  t.turf_id,
                  t.turf_name,
                  t.sport_type,
                  t.city,
                  t.area,
                  t.address,
                  t.price_per_hour,
                  t.status,
                  t.created_at,
                  o.owner_id,
                  o.owner_name,
                  o.email AS owner_email
                FROM turfs t
                JOIN turf_owners o ON o.owner_id = t.owner_id
                WHERE t.status = 'pending'
                ORDER BY t.created_at ASC, t.turf_id ASC
                LIMIT 50";
    $turfRes = $conn->query($turfSql);
    while ($row = $turfRes ? $turfRes->fetch_assoc() : null) {
        if (!$row) break;
        $pendingTurfs[] = [
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name'],
            'sport_type' => $row['sport_type'],
            'city' => $row['city'],
            'area' => $row['area'],
            'address' => $row['address'],
            'price_per_hour' => (float)($row['price_per_hour'] ?? 0),
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'owner_id' => (int)$row['owner_id'],
            'owner_name' => $row['owner_name'],
            'owner_email' => $row['owner_email']
        ];
    }
}

if (table_exists($conn, 'bookings') && table_exists($conn, 'users') && table_exists($conn, 'slots') && table_exists($conn, 'turfs')) {
    $bookSql = "SELECT
                  b.booking_id,
                  b.user_id,
                  b.slot_id,
                  b.booking_status,
                  b.booked_price,
                  b.created_at,
                  u.full_name,
                  u.email,
                  s.slot_date,
                  s.start_time,
                  s.end_time,
                  t.turf_id,
                  t.turf_name
                FROM bookings b
                JOIN users u ON u.user_id = b.user_id
                JOIN slots s ON s.slot_id = b.slot_id
                JOIN turfs t ON t.turf_id = s.turf_id
                WHERE b.booking_status = 'pending'
                ORDER BY b.created_at ASC, b.booking_id ASC
                LIMIT 60";
    $bookRes = $conn->query($bookSql);
    while ($row = $bookRes ? $bookRes->fetch_assoc() : null) {
        if (!$row) break;
        $pendingBookings[] = [
            'booking_id' => (int)$row['booking_id'],
            'user_id' => (int)$row['user_id'],
            'slot_id' => (int)$row['slot_id'],
            'booking_status' => $row['booking_status'],
            'booked_price' => (float)($row['booked_price'] ?? 0),
            'created_at' => $row['created_at'],
            'user_name' => $row['full_name'],
            'user_email' => $row['email'],
            'slot_date' => $row['slot_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name']
        ];
    }
}

if (table_exists($conn, 'refund_requests') && table_exists($conn, 'bookings') && table_exists($conn, 'users')) {
    $refundSql = "SELECT
                    r.refund_id,
                    r.booking_id,
                    r.requested_by,
                    r.requested_amount,
                    r.status,
                    r.admin_note,
                    r.created_at,
                    b.user_id,
                    b.booking_status,
                    b.booked_price,
                    u.full_name,
                    u.email
                  FROM refund_requests r
                  JOIN bookings b ON b.booking_id = r.booking_id
                  JOIN users u ON u.user_id = b.user_id
                  WHERE r.status = 'pending'
                  ORDER BY r.created_at ASC, r.refund_id ASC
                  LIMIT 60";
    $refundRes = $conn->query($refundSql);
    while ($row = $refundRes ? $refundRes->fetch_assoc() : null) {
        if (!$row) break;
        $pendingRefunds[] = [
            'refund_id' => (int)$row['refund_id'],
            'booking_id' => (int)$row['booking_id'],
            'requested_by' => $row['requested_by'],
            'requested_amount' => (float)($row['requested_amount'] ?? 0),
            'status' => $row['status'],
            'admin_note' => $row['admin_note'],
            'created_at' => $row['created_at'],
            'user_id' => (int)$row['user_id'],
            'user_name' => $row['full_name'],
            'user_email' => $row['email'],
            'booking_status' => $row['booking_status'],
            'booked_price' => (float)($row['booked_price'] ?? 0)
        ];
    }
}

if (table_exists($conn, 'turfs')) {
    $topTurfSql = "SELECT turf_id, turf_name, rating_avg, city, price_per_hour
                   FROM turfs
                   WHERE status = 'active'
                   ORDER BY rating_avg DESC, turf_id DESC
                   LIMIT 6";
    $topRes = $conn->query($topTurfSql);
    while ($row = $topRes ? $topRes->fetch_assoc() : null) {
        if (!$row) break;
        $analytics['top_rated_turfs'][] = [
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name'],
            'rating_avg' => (float)($row['rating_avg'] ?? 0),
            'city' => $row['city'],
            'price_per_hour' => (float)($row['price_per_hour'] ?? 0)
        ];
    }
}

if (table_exists($conn, 'bookings') && table_exists($conn, 'users')) {
    $activeUserSql = "SELECT u.user_id, u.full_name, u.email, COUNT(b.booking_id) AS booking_count
                      FROM users u
                      JOIN bookings b ON b.user_id = u.user_id
                      GROUP BY u.user_id, u.full_name, u.email
                      ORDER BY booking_count DESC, u.user_id DESC
                      LIMIT 6";
    $activeRes = $conn->query($activeUserSql);
    while ($row = $activeRes ? $activeRes->fetch_assoc() : null) {
        if (!$row) break;
        $analytics['most_active_users'][] = [
            'user_id' => (int)$row['user_id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'booking_count' => (int)($row['booking_count'] ?? 0)
        ];
    }

    $trendSql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                        COUNT(*) AS booking_count,
                        COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN booked_price ELSE 0 END), 0) AS revenue
                 FROM bookings
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY ym ASC";
    $trendRes = $conn->query($trendSql);
    while ($row = $trendRes ? $trendRes->fetch_assoc() : null) {
        if (!$row) break;
        $analytics['revenue_trend'][] = [
            'month' => $row['ym'],
            'revenue' => (float)($row['revenue'] ?? 0)
        ];
        $analytics['booking_growth'][] = [
            'month' => $row['ym'],
            'bookings' => (int)($row['booking_count'] ?? 0)
        ];
    }
}

send_json_response(true, 'Admin dashboard loaded.', 200, [
    'stats' => $stats,
    'monitor_users' => $monitorUsers,
    'monitor_owners' => $monitorOwners,
    'pending_turfs' => $pendingTurfs,
    'pending_bookings' => $pendingBookings,
    'pending_refunds' => $pendingRefunds,
    'analytics' => $analytics
]);
?>
