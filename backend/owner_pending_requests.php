<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'owner_common.php';

$ownerId = intval($_GET['owner_id'] ?? 0);
if (!owner_exists($conn, $ownerId)) {
    owner_send_json_response(false, 'Owner access denied.', 403);
}

$pendingBookings = [];
$pendingRefunds = [];

if (
    owner_table_exists($conn, 'bookings') &&
    owner_table_exists($conn, 'users') &&
    owner_table_exists($conn, 'slots') &&
    owner_table_exists($conn, 'turfs')
) {
    $bookingSql = "SELECT
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
                     AND t.owner_id = ?
                   ORDER BY b.created_at ASC, b.booking_id ASC
                   LIMIT 60";
    $bookingStmt = $conn->prepare($bookingSql);
    if ($bookingStmt) {
        $bookingStmt->bind_param('i', $ownerId);
        $bookingStmt->execute();
        $bookingRes = $bookingStmt->get_result();
        while ($row = $bookingRes ? $bookingRes->fetch_assoc() : null) {
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
        $bookingStmt->close();
    }
}

if (
    owner_table_exists($conn, 'refund_requests') &&
    owner_table_exists($conn, 'bookings') &&
    owner_table_exists($conn, 'users') &&
    owner_table_exists($conn, 'slots') &&
    owner_table_exists($conn, 'turfs')
) {
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
                    u.email,
                    t.turf_id,
                    t.turf_name
                  FROM refund_requests r
                  JOIN bookings b ON b.booking_id = r.booking_id
                  JOIN users u ON u.user_id = b.user_id
                  JOIN slots s ON s.slot_id = b.slot_id
                  JOIN turfs t ON t.turf_id = s.turf_id
                  WHERE r.status = 'pending'
                    AND t.owner_id = ?
                  ORDER BY r.created_at ASC, r.refund_id ASC
                  LIMIT 60";
    $refundStmt = $conn->prepare($refundSql);
    if ($refundStmt) {
        $refundStmt->bind_param('i', $ownerId);
        $refundStmt->execute();
        $refundRes = $refundStmt->get_result();
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
                'booked_price' => (float)($row['booked_price'] ?? 0),
                'turf_id' => (int)$row['turf_id'],
                'turf_name' => $row['turf_name']
            ];
        }
        $refundStmt->close();
    }
}

owner_send_json_response(true, 'Owner pending requests loaded.', 200, [
    'pending_bookings' => $pendingBookings,
    'pending_refunds' => $pendingRefunds
]);
?>
