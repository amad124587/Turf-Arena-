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

function send_response($success, $pending, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'pending' => $pending
    ]);
    exit();
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    send_response(false, null, 400);
}

$sql = "
SELECT
    b.booking_id,
    b.created_at,
    s.turf_id,
    t.turf_name
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
LEFT JOIN reviews r ON r.booking_id = b.booking_id
WHERE b.user_id = ?
  AND b.booking_status = 'completed'
  AND r.review_id IS NULL
ORDER BY b.created_at DESC
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, null, 500);
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$pending = $res ? $res->fetch_assoc() : null;
$stmt->close();

send_response(true, $pending ?: null);
?>
