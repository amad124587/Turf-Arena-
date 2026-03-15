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

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
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

$locationSelect = column_exists($conn, 'turfs', 'location') ? 't.location,' : "'' AS location,";

$sql = "
SELECT
  b.booking_id,
  b.booking_status,
  b.booked_price,
  b.refund_amount,
  b.created_at,
  s.slot_id,
  s.slot_date,
  s.start_time,
  s.end_time,
  t.turf_id,
  t.turf_name,
  t.city,
  t.area,
  $locationSelect
  t.address,
  t.cancel_before_hours,
  t.refund_percent
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
WHERE b.user_id = ?
ORDER BY b.created_at DESC, b.booking_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while loading bookings.', 500);
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$nowTs = time();

while ($row = $res ? $res->fetch_assoc() : null) {
    if (!$row) break;

    $slotDate = (string)$row['slot_date'];
    $startTime = (string)$row['start_time'];
    $slotTs = strtotime($slotDate . ' ' . $startTime);
    $hoursLeft = $slotTs ? (($slotTs - $nowTs) / 3600) : -1;

    $canCancel = strtolower((string)$row['booking_status']) === 'confirmed' && $hoursLeft > 0;

    $rows[] = [
        'booking_id' => (int)$row['booking_id'],
        'booking_status' => $row['booking_status'],
        'booked_price' => (float)$row['booked_price'],
        'refund_amount' => (float)$row['refund_amount'],
        'created_at' => $row['created_at'],
        'slot_id' => (int)$row['slot_id'],
        'slot_date' => $row['slot_date'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'turf_id' => (int)$row['turf_id'],
        'turf_name' => $row['turf_name'],
        'location' => ($row['location'] ?? '') ?: ($row['area'] ?: ($row['city'] ?: $row['address'])),
        'cancel_before_hours' => (int)($row['cancel_before_hours'] ?? 24),
        'refund_percent' => (int)($row['refund_percent'] ?? 80),
        'can_cancel' => $canCancel
    ];
}

$stmt->close();

send_response(true, 'Bookings loaded.', 200, [
    'bookings' => $rows
]);
?>
