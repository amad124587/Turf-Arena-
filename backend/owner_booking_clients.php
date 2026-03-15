<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'db_connection.php';

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

$ownerId = intval($_GET['owner_id'] ?? 0);
if ($ownerId <= 0) {
    send_response(false, 'Invalid owner id.', 400);
}

$ownerStmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1');
if (!$ownerStmt) {
    send_response(false, 'Server error while checking owner.', 500);
}
$ownerStmt->bind_param('i', $ownerId);
$ownerStmt->execute();
$ownerRes = $ownerStmt->get_result();
$ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
$ownerStmt->close();

if (!$ownerRow) {
    send_response(false, 'Owner not found.', 404);
}

$sql = "
SELECT
  b.booking_id,
  b.booking_status,
  b.created_at,
  b.booked_price,
  s.slot_date,
  s.start_time,
  s.end_time,
  t.turf_id,
  t.turf_name,
  COALESCE(t.sport_type, '') AS sport_type,
  u.user_id,
  u.full_name AS customer_name,
  u.email AS customer_email,
  COALESCE(u.phone, '') AS customer_phone
FROM bookings b
JOIN slots s ON s.slot_id = b.slot_id
JOIN turfs t ON t.turf_id = s.turf_id
JOIN users u ON u.user_id = b.user_id
WHERE t.owner_id = ?
ORDER BY s.slot_date DESC, s.start_time DESC, b.booking_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while loading booking clients.', 500);
}
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res ? $res->fetch_assoc() : null) {
    if (!$row) {
        break;
    }

    $rows[] = [
        'booking_id' => (int)$row['booking_id'],
        'booking_status' => $row['booking_status'],
        'created_at' => $row['created_at'],
        'booked_price' => (float)($row['booked_price'] ?? 0),
        'slot_date' => $row['slot_date'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'turf_id' => (int)$row['turf_id'],
        'turf_name' => $row['turf_name'],
        'sport_type' => $row['sport_type'],
        'client' => [
            'user_id' => (int)$row['user_id'],
            'full_name' => $row['customer_name'],
            'email' => $row['customer_email'],
            'phone' => $row['customer_phone']
        ]
    ];
}

$stmt->close();

send_response(true, 'Booking client details loaded.', 200, [
    'bookings' => $rows
]);
?>
