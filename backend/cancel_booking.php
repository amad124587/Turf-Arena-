<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

function table_exists($conn, $table) {
    $name = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$name'");
    return $res && $res->num_rows > 0;
}

if (!table_exists($conn, 'refund_requests')) {
    send_response(false, 'Refund request system is not available.', 500);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
    if (empty($data)) {
        parse_str(file_get_contents('php://input'), $data);
    }
}

if (!is_array($data) || empty($data)) {
    send_response(false, 'No data received.', 400);
}

$userId = intval($data['user_id'] ?? 0);
$bookingId = intval($data['booking_id'] ?? 0);

if ($userId <= 0 || $bookingId <= 0) {
    send_response(false, 'Invalid cancellation request.', 400);
}

$sql = "
SELECT
  b.booking_id,
  b.booked_price,
  b.booking_status,
  s.slot_date,
  s.start_time,
  t.owner_id,
  t.turf_name
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
WHERE b.booking_id = ?
  AND b.user_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while loading booking.', 500);
}
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    send_response(false, 'Booking not found.', 404);
}

$status = strtolower((string)($row['booking_status'] ?? ''));
if ($status !== 'confirmed') {
    send_response(false, 'Only confirmed bookings can request cancellation.', 400);
}

$slotTs = strtotime((string)$row['slot_date'] . ' ' . (string)$row['start_time']);
$nowTs = time();
if (!$slotTs || $slotTs <= $nowTs) {
    send_response(false, 'Booking time has started or passed.', 400);
}

$bookedPrice = (float)($row['booked_price'] ?? 0);
if ($bookedPrice <= 0) {
    send_response(false, 'Invalid booking price.', 400);
}

$ownerShare = round($bookedPrice * 0.20, 2);
$userRefund = round($bookedPrice - $ownerShare, 2);

$existingStmt = $conn->prepare("SELECT refund_id, status FROM refund_requests WHERE booking_id = ? LIMIT 1");
if (!$existingStmt) {
    send_response(false, 'Server error while checking refund request.', 500);
}
$existingStmt->bind_param('i', $bookingId);
$existingStmt->execute();
$existingRes = $existingStmt->get_result();
$existing = $existingRes ? $existingRes->fetch_assoc() : null;
$existingStmt->close();

if ($existing) {
    $existingStatus = strtolower((string)($existing['status'] ?? 'pending'));
    if ($existingStatus === 'pending') {
        send_response(true, 'Cancellation request already pending for admin approval.', 200, [
            'booking_id' => $bookingId,
            'request_status' => 'pending',
            'refund_amount' => $userRefund,
            'owner_share' => $ownerShare
        ]);
    }

    if ($existingStatus === 'approved' || $existingStatus === 'paid') {
        send_response(false, 'Cancellation already processed for this booking.', 400);
    }
}

$conn->begin_transaction();

if ($existing) {
    $refundId = (int)($existing['refund_id'] ?? 0);
    $updateSql = "UPDATE refund_requests
                  SET requested_by = 'user', requested_amount = ?, status = 'pending', admin_id = NULL, admin_note = ?, updated_at = NOW()
                  WHERE refund_id = ? LIMIT 1";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        $conn->rollback();
        send_response(false, 'Server error while updating cancellation request.', 500);
    }
    $note = 'User cancellation request submitted';
    $updateStmt->bind_param('dsi', $userRefund, $note, $refundId);
    $ok = $updateStmt->execute();
    $affected = $updateStmt->affected_rows;
    $updateStmt->close();

    if (!$ok || $affected <= 0) {
        $conn->rollback();
        send_response(false, 'Could not submit cancellation request.', 500);
    }

    $requestId = $refundId;
} else {
    $insertSql = "INSERT INTO refund_requests (booking_id, requested_by, requested_amount, status, admin_note)
                  VALUES (?, 'user', ?, 'pending', ?)";
    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        $conn->rollback();
        send_response(false, 'Server error while creating cancellation request.', 500);
    }
    $note = 'User cancellation request submitted';
    $insertStmt->bind_param('ids', $bookingId, $userRefund, $note);
    $ok = $insertStmt->execute();
    $requestId = $ok ? (int)$insertStmt->insert_id : 0;
    $insertStmt->close();

    if (!$ok || $requestId <= 0) {
        $conn->rollback();
        send_response(false, 'Could not create cancellation request.', 500);
    }
}

if (table_exists($conn, 'notifications')) {
    $title = 'Cancellation request submitted';
    $message = 'Booking #' . $bookingId . ' cancellation request is pending admin approval.';
    $notifySql = 'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)';
    $notifyStmt = $conn->prepare($notifySql);
    if ($notifyStmt) {
        $notifyStmt->bind_param('iss', $userId, $title, $message);
        $notifyStmt->execute();
        $notifyStmt->close();
    }
}

$conn->commit();

send_response(true, 'Cancellation request sent to admin.', 200, [
    'booking_id' => $bookingId,
    'refund_id' => $requestId,
    'request_status' => 'pending',
    'refund_amount' => $userRefund,
    'owner_share' => $ownerShare,
    'refund_percent' => 80
]);
?>
