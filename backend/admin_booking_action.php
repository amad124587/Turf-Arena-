<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'admin_common.php';

$data = read_request_data();
if (empty($data)) {
    send_json_response(false, 'No data received.', 400);
}

$adminId = intval($data['admin_id'] ?? 0);
$bookingId = intval($data['booking_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));

if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

if ($bookingId <= 0) {
    send_json_response(false, 'Invalid booking id.', 400);
}

if ($action === 'approve') $action = 'confirm';
if ($action === 'decline') $action = 'reject';

if ($action !== 'confirm' && $action !== 'reject') {
    send_json_response(false, 'Invalid action.', 400);
}

$targetStatus = $action === 'confirm' ? 'confirmed' : 'cancelled';

$checkStmt = $conn->prepare('SELECT booking_id, booking_status FROM bookings WHERE booking_id = ? LIMIT 1');
if (!$checkStmt) {
    send_json_response(false, 'Server error while loading booking.', 500);
}
$checkStmt->bind_param('i', $bookingId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$booking = $checkRes ? $checkRes->fetch_assoc() : null;
$checkStmt->close();

if (!$booking) {
    send_json_response(false, 'Booking not found.', 404);
}

$currentStatus = strtolower((string)($booking['booking_status'] ?? ''));

if ($currentStatus === $targetStatus) {
    $pointsAwarded = 0;
    if ($targetStatus === 'confirmed') {
        $pointResult = award_confirm_points($conn, $bookingId);
        if (!$pointResult['ok']) {
            send_json_response(false, 'Booking already confirmed, but points sync failed: ' . $pointResult['reason'], 500);
        }
        $pointsAwarded = (int)($pointResult['points'] ?? 0);
    }

    send_json_response(true, 'Booking already in requested status.', 200, [
        'booking_id' => $bookingId,
        'status' => $targetStatus,
        'points_awarded' => $pointsAwarded
    ]);
}

if ($currentStatus !== 'pending') {
    send_json_response(false, 'Only pending bookings can be processed.', 400, [
        'current_status' => $currentStatus
    ]);
}

$conn->begin_transaction();

$updateStmt = $conn->prepare('UPDATE bookings SET booking_status = ? WHERE booking_id = ? AND booking_status = ? LIMIT 1');
if (!$updateStmt) {
    $conn->rollback();
    send_json_response(false, 'Server error while updating booking.', 500);
}
$pending = 'pending';
$updateStmt->bind_param('sis', $targetStatus, $bookingId, $pending);
$ok = $updateStmt->execute();
$affected = $updateStmt->affected_rows;
$updateStmt->close();

if (!$ok || $affected <= 0) {
    $conn->rollback();
    send_json_response(false, 'Booking status update failed.', 500);
}

$pointsAwarded = 0;
if ($targetStatus === 'confirmed') {
    $pointResult = award_confirm_points($conn, $bookingId);
    if (!$pointResult['ok']) {
        $conn->rollback();
        send_json_response(false, 'Booking updated but points failed: ' . $pointResult['reason'], 500);
    }
    $pointsAwarded = (int)($pointResult['points'] ?? 0);
}

$conn->commit();

send_json_response(true, 'Booking request processed successfully.', 200, [
    'booking_id' => $bookingId,
    'status' => $targetStatus,
    'points_awarded' => $pointsAwarded
]);
?>
