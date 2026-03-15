<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'db_connection.php';
require_once 'promo_code_helper.php';
date_default_timezone_set('Asia/Dhaka');

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    send_response(false, 'Invalid request body.', 400);
}

$slotId = intval($payload['slot_id'] ?? 0);
$promoCode = trim((string)($payload['promo_code'] ?? ''));

if ($slotId <= 0 || $promoCode === '') {
    send_response(false, 'Slot and promo code are required.', 400);
}

$slotStmt = $conn->prepare('
    SELECT s.slot_id, s.slot_date, s.start_time, s.end_time, s.base_price, s.is_enabled, t.owner_id
    FROM slots s
    JOIN turfs t ON t.turf_id = s.turf_id
    WHERE s.slot_id = ?
    LIMIT 1
');
if (!$slotStmt) {
    send_response(false, 'Server error while checking slot.', 500);
}
$slotStmt->bind_param('i', $slotId);
$slotStmt->execute();
$slotRes = $slotStmt->get_result();
$slot = $slotRes ? $slotRes->fetch_assoc() : null;
$slotStmt->close();

if (!$slot || intval($slot['is_enabled'] ?? 0) !== 1) {
    send_response(false, 'Selected slot is not available.', 404);
}

$now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$slotStart = DateTime::createFromFormat('Y-m-d H:i:s', $slot['slot_date'] . ' ' . substr((string)$slot['start_time'], 0, 8), new DateTimeZone('Asia/Dhaka'));
if (!$slotStart || $slotStart <= $now) {
    send_response(false, 'Past date/time slot cannot use promo code.', 400);
}

$bookingStmt = $conn->prepare("SELECT booking_id FROM bookings WHERE slot_id = ? AND booking_status IN ('pending','confirmed','completed','resell_listed','resold') LIMIT 1");
if (!$bookingStmt) {
    send_response(false, 'Server error while checking booking state.', 500);
}
$bookingStmt->bind_param('i', $slotId);
$bookingStmt->execute();
$bookingStmt->store_result();
if ($bookingStmt->num_rows > 0) {
    $bookingStmt->close();
    send_response(false, 'This slot is already booked.', 409);
}
$bookingStmt->close();

$subtotal = promo_calculate_slot_subtotal($slot['base_price'], $slot['start_time'], $slot['end_time']);
$promoResult = promo_validate_for_owner_turf($conn, intval($slot['owner_id'] ?? 0), $promoCode, $subtotal, (string)$slot['slot_date']);
if (!$promoResult['success']) {
    send_response(false, $promoResult['message'], 400);
}

send_response(true, $promoResult['message'], 200, [
    'promo' => [
        'code' => $promoResult['promo_code'],
        'discount_type' => $promoResult['discount_type'],
        'discount_value' => $promoResult['discount_value'],
        'discount_amount' => $promoResult['discount_amount'],
        'subtotal' => $subtotal,
        'final_total' => $promoResult['final_total']
    ]
]);
?>
