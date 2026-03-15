<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'db_connection.php';

function send_response($success, $status, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'status' => $status
    ], $extra));
    exit();
}

function table_exists($conn, $table) {
    $name = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$name'");
    return $res && $res->num_rows > 0;
}

function column_exists($conn, $table, $column) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $safeColumn = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && $res->num_rows > 0;
}

$userId = intval($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    send_response(false, 'Invalid user id.', 400);
}

$stats = [
    'total' => 0,
    'upcoming' => 0,
    'cancelled' => 0
];


$statsSql = "
SELECT
  COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed','completed') THEN 1 ELSE 0 END), 0) AS total_bookings,
  COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' AND s.slot_date >= CURDATE() THEN 1 ELSE 0 END), 0) AS upcoming_bookings,
  COALESCE(SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_bookings
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
WHERE b.user_id = ?
";
$statsStmt = $conn->prepare($statsSql);
if (!$statsStmt) {
    send_response(false, 'Server error while loading booking stats.', 500);
}
$statsStmt->bind_param('i', $userId);
$statsStmt->execute();
$statsRes = $statsStmt->get_result();
$statsRow = $statsRes ? $statsRes->fetch_assoc() : null;
$statsStmt->close();

if ($statsRow) {
    $stats['total'] = (int)($statsRow['total_bookings'] ?? 0);
    $stats['upcoming'] = (int)($statsRow['upcoming_bookings'] ?? 0);
    $stats['cancelled'] = (int)($statsRow['cancelled_bookings'] ?? 0);
}

$nextBooking = null;
$hasTurfLocationColumn = column_exists($conn, 'turfs', 'location');
$locationExpr = $hasTurfLocationColumn
    ? "CONCAT_WS(', ', NULLIF(t.area, ''), NULLIF(t.city, ''), NULLIF(t.location, ''), NULLIF(t.address, '')) AS location_text"
    : "CONCAT_WS(', ', NULLIF(t.area, ''), NULLIF(t.city, ''), NULLIF(t.address, '')) AS location_text";
$hasRefundRequests = table_exists($conn, 'refund_requests');
$refundSelectExpr = $hasRefundRequests ? 'rr.status AS refund_request_status,' : 'NULL AS refund_request_status,';
$refundJoin = $hasRefundRequests ? 'LEFT JOIN refund_requests rr ON rr.booking_id = b.booking_id' : '';

$nextSql = "
SELECT
  b.booking_id,
  b.booking_status,
  b.booked_price,
  s.slot_date,
  s.start_time,
  s.end_time,
  t.turf_id,
  t.turf_name,
  t.cancel_before_hours,
  t.refund_percent,
  $refundSelectExpr
  $locationExpr
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
$refundJoin
WHERE b.user_id = ?
  AND b.booking_status = 'confirmed'
  AND s.slot_date >= CURDATE()
ORDER BY s.slot_date ASC, s.start_time ASC
LIMIT 1
";
$nextStmt = $conn->prepare($nextSql);
if ($nextStmt) {
    $nextStmt->bind_param('i', $userId);
    $nextStmt->execute();
    $nextRes = $nextStmt->get_result();
    $row = $nextRes ? $nextRes->fetch_assoc() : null;
    $nextStmt->close();

    if ($row) {
        $nextBooking = [
            'booking_id' => (int)$row['booking_id'],
            'booking_status' => $row['booking_status'],
            'booked_price' => (float)$row['booked_price'],
            'slot_date' => $row['slot_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name'],
            'cancel_before_hours' => (int)($row['cancel_before_hours'] ?? 24),
            'refund_percent' => (int)($row['refund_percent'] ?? 80),
            'location' => $row['location_text'],
            'refund_request_status' => $row['refund_request_status']
        ];
    }
}

$activities = [];
$activitySql = "
SELECT
  b.booking_status,
  b.created_at,
  t.turf_name,
  s.slot_date,
  s.start_time,
  s.end_time
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
WHERE b.user_id = ?
ORDER BY b.created_at DESC
LIMIT 8
";
$activityStmt = $conn->prepare($activitySql);
if ($activityStmt) {
    $activityStmt->bind_param('i', $userId);
    $activityStmt->execute();
    $activityRes = $activityStmt->get_result();

    $statusMap = [
        'confirmed' => 'You booked %s',
        'pending' => 'Booking request pending for %s',
        'cancelled' => 'Booking cancelled for %s',
        'completed' => 'Booking completed at %s',
        'resell_listed' => 'Slot listed for resell from %s',
        'resold' => 'Slot resold successfully for %s'
    ];

    while ($a = $activityRes ? $activityRes->fetch_assoc() : null) {
        if (!$a) {
            break;
        }
        $status = strtolower((string)($a['booking_status'] ?? ''));
        $turfName = (string)($a['turf_name'] ?? 'Turf');
        $template = $statusMap[$status] ?? 'Booking activity at %s';

        $activities[] = [
            'message' => sprintf($template, $turfName),
            'time' => $a['created_at'],
            'booking_status' => $status,
            'turf_name' => $turfName,
            'slot_date' => $a['slot_date'],
            'start_time' => $a['start_time'],
            'end_time' => $a['end_time'],
            'detail_message' => ''
        ];
    }

    $activityStmt->close();
}

if (table_exists($conn, 'notifications')) {
    $notifSql = "SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8";
    $notifStmt = $conn->prepare($notifSql);
    if ($notifStmt) {
        $notifStmt->bind_param('i', $userId);
        $notifStmt->execute();
        $notifRes = $notifStmt->get_result();

        while ($n = $notifRes ? $notifRes->fetch_assoc() : null) {
            if (!$n) {
                break;
            }

            $title = trim((string)($n['title'] ?? ''));
            $message = trim((string)($n['message'] ?? ''));
            $detailMessage = '';
            if ($message !== '' && stripos($message, 'Admin note:') !== false) {
                $detailMessage = $message;
            }

            $activities[] = [
                'message' => $title !== '' ? $title : ($message !== '' ? $message : 'Notification'),
                'time' => $n['created_at'],
                'booking_status' => 'notification',
                'turf_name' => '',
                'slot_date' => null,
                'start_time' => null,
                'end_time' => null,
                'detail_message' => $detailMessage
            ];
        }

        $notifStmt->close();
    }
}

usort($activities, function($a, $b) {
    $aTs = strtotime((string)($a['time'] ?? ''));
    $bTs = strtotime((string)($b['time'] ?? ''));
    return $bTs <=> $aTs;
});

if (count($activities) > 5) {
    $activities = array_slice($activities, 0, 5);
}

$bookingPoints = 0;
$reviewPoints = 0;
$resellPoints = 0;
$referralPoints = 0;

$pointsBookingSql = "SELECT COALESCE(SUM(booked_price), 0) AS completed_value FROM bookings WHERE user_id = ? AND booking_status IN ('confirmed', 'completed')";
$pointsStmt = $conn->prepare($pointsBookingSql);
if ($pointsStmt) {
    $pointsStmt->bind_param('i', $userId);
    $pointsStmt->execute();
    $pointsRes = $pointsStmt->get_result();
    $pointsRow = $pointsRes ? $pointsRes->fetch_assoc() : null;
    $pointsStmt->close();

    $completedValue = (float)($pointsRow['completed_value'] ?? 0);
    $bookingPoints = (int)floor($completedValue / 100);
}

if (table_exists($conn, 'reviews')) {
    $reviewSql = "SELECT COUNT(*) AS review_count FROM reviews WHERE user_id = ?";
    $reviewStmt = $conn->prepare($reviewSql);
    if ($reviewStmt) {
        $reviewStmt->bind_param('i', $userId);
        $reviewStmt->execute();
        $reviewRes = $reviewStmt->get_result();
        $reviewRow = $reviewRes ? $reviewRes->fetch_assoc() : null;
        $reviewStmt->close();

        $reviewCount = (int)($reviewRow['review_count'] ?? 0);
        $reviewPoints = $reviewCount * 5;
    }
}

if (table_exists($conn, 'resell_listings')) {
    $resellSql = "SELECT COALESCE(SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END), 0) AS sold_count FROM resell_listings WHERE seller_user_id = ?";
    $resellStmt = $conn->prepare($resellSql);
    if ($resellStmt) {
        $resellStmt->bind_param('i', $userId);
        $resellStmt->execute();
        $resellRes = $resellStmt->get_result();
        $resellRow = $resellRes ? $resellRes->fetch_assoc() : null;
        $resellStmt->close();

        $soldCount = (int)($resellRow['sold_count'] ?? 0);
        $resellPoints = $soldCount * 8;
    }
}

if (table_exists($conn, 'user_point_logs') && column_exists($conn, 'user_point_logs', 'user_id')) {
    if (column_exists($conn, 'user_point_logs', 'action') && column_exists($conn, 'user_point_logs', 'points_change')) {
        $refSql = "SELECT COALESCE(SUM(CASE WHEN action = 'promo' THEN points_change ELSE 0 END), 0) AS referral_points FROM user_point_logs WHERE user_id = ?";
    } elseif (column_exists($conn, 'user_point_logs', 'source') && column_exists($conn, 'user_point_logs', 'points')) {
        $refSql = "SELECT COALESCE(SUM(CASE WHEN source = 'admin_adjustment' THEN points ELSE 0 END), 0) AS referral_points FROM user_point_logs WHERE user_id = ?";
    } else {
        $refSql = '';
    }

    if ($refSql !== '') {
        $refStmt = $conn->prepare($refSql);
        if ($refStmt) {
            $refStmt->bind_param('i', $userId);
            $refStmt->execute();
            $refRes = $refStmt->get_result();
            $refRow = $refRes ? $refRes->fetch_assoc() : null;
            $refStmt->close();

            $referralPoints = (int)($refRow['referral_points'] ?? 0);
            if ($referralPoints < 0) {
                $referralPoints = 0;
            }
        }
    }
}

$totalPoints = $bookingPoints + $reviewPoints + $resellPoints + $referralPoints;

send_response(true, 'Dashboard loaded.', 200, [
    'stats' => $stats,
    'next_booking' => $nextBooking,
    'activities' => $activities,
    'points' => $totalPoints,
    'points_breakdown' => [
        'booking' => $bookingPoints,
        'review' => $reviewPoints,
        'resell' => $resellPoints,
        'referral' => $referralPoints
    ]
]);
?>




