<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'owner_common.php';

$data = owner_read_request_data();
if (empty($data)) {
    owner_send_json_response(false, 'No data received.', 400);
}

$ownerId = intval($data['owner_id'] ?? 0);
$bookingId = intval($data['booking_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));

if (!owner_exists($conn, $ownerId)) {
    owner_send_json_response(false, 'Owner access denied.', 403);
}

if ($bookingId <= 0) {
    owner_send_json_response(false, 'Invalid booking id.', 400);
}

if ($action === 'approve') $action = 'confirm';
if ($action === 'decline') $action = 'reject';

if ($action !== 'confirm' && $action !== 'reject') {
    owner_send_json_response(false, 'Invalid action.', 400);
}

$targetStatus = $action === 'confirm' ? 'confirmed' : 'cancelled';

$checkSql = "SELECT b.booking_id, b.booking_status
             FROM bookings b
             JOIN slots s ON s.slot_id = b.slot_id
             JOIN turfs t ON t.turf_id = s.turf_id
             WHERE b.booking_id = ?
               AND t.owner_id = ?
             LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    owner_send_json_response(false, 'Server error while loading booking.', 500);
}
$checkStmt->bind_param('ii', $bookingId, $ownerId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$booking = $checkRes ? $checkRes->fetch_assoc() : null;
$checkStmt->close();

if (!$booking) {
    owner_send_json_response(false, 'Booking not found for this owner.', 404);
}

$currentStatus = strtolower((string)($booking['booking_status'] ?? ''));
if ($currentStatus === $targetStatus) {
    $pointsAwarded = 0;
    if ($targetStatus === 'confirmed') {
        $pointResult = owner_award_confirm_points($conn, $bookingId);
        if (!$pointResult['ok']) {
            owner_send_json_response(false, 'Booking already confirmed, but points sync failed: ' . $pointResult['reason'], 500);
        }
        $pointsAwarded = (int)($pointResult['points'] ?? 0);
    }

    owner_send_json_response(true, 'Booking already in requested status.', 200, [
        'booking_id' => $bookingId,
        'status' => $targetStatus,
        'points_awarded' => $pointsAwarded
    ]);
}

if ($currentStatus !== 'pending') {
    owner_send_json_response(false, 'Only pending bookings can be processed.', 400, [
        'current_status' => $currentStatus
    ]);
}

$conn->begin_transaction();

$updateStmt = $conn->prepare('UPDATE bookings SET booking_status = ? WHERE booking_id = ? AND booking_status = ? LIMIT 1');
if (!$updateStmt) {
    $conn->rollback();
    owner_send_json_response(false, 'Server error while updating booking.', 500);
}
$pending = 'pending';
$updateStmt->bind_param('sis', $targetStatus, $bookingId, $pending);
$ok = $updateStmt->execute();
$affected = $updateStmt->affected_rows;
$updateStmt->close();

if (!$ok || $affected <= 0) {
    $conn->rollback();
    owner_send_json_response(false, 'Booking status update failed.', 500);
}

$pointsAwarded = 0;
if ($targetStatus === 'confirmed') {
    $pointResult = owner_award_confirm_points($conn, $bookingId);
    if (!$pointResult['ok']) {
        $conn->rollback();
        owner_send_json_response(false, 'Booking updated but points failed: ' . $pointResult['reason'], 500);
    }
    $pointsAwarded = (int)($pointResult['points'] ?? 0);
}

$conn->commit();

owner_send_json_response(true, 'Booking request processed successfully.', 200, [
    'booking_id' => $bookingId,
    'status' => $targetStatus,
    'points_awarded' => $pointsAwarded
]);
?>
