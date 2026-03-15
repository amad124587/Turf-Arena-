<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once "db_connection.php";
require_once "promo_code_helper.php";
date_default_timezone_set("Asia/Dhaka");

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    send_response(false, "Invalid request body.", 400);
}

$user_id = intval($data['user_id'] ?? 0);
$slot_id = intval($data['slot_id'] ?? 0);
$price = $data['booked_price'] ?? null;
$promo_code = strtoupper(trim((string)($data['promo_code'] ?? '')));

if ($user_id <= 0 || $slot_id <= 0 || !is_numeric($price)) {
    send_response(false, "Invalid booking payload.", 400);
}

$client_booked_price = floatval($price);

// Validate slot exists
$slotStmt = $conn->prepare("
    SELECT s.slot_id, s.slot_date, s.start_time, s.end_time, s.base_price, t.owner_id, t.turf_name
    FROM slots s
    JOIN turfs t ON t.turf_id = s.turf_id
    WHERE s.slot_id = ? AND s.is_enabled = 1
    LIMIT 1
");
if (!$slotStmt) {
    send_response(false, "Server error while checking slot.", 500);
}
$slotStmt->bind_param("i", $slot_id);
$slotStmt->execute();
$slotResult = $slotStmt->get_result();
$slot = $slotResult ? $slotResult->fetch_assoc() : null;
$slotStmt->close();

if (!$slot) {
    send_response(false, "Slot not found or disabled.", 404);
}

$slotDate = (string)($slot['slot_date'] ?? '');
$slotStartTime = (string)($slot['start_time'] ?? '');
if ($slotDate === '' || $slotStartTime === '') {
    send_response(false, "Invalid slot date/time.", 400);
}

$now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$slotStart = DateTime::createFromFormat('Y-m-d H:i:s', $slotDate . ' ' . substr($slotStartTime, 0, 8), new DateTimeZone('Asia/Dhaka'));
if (!$slotStart) {
    send_response(false, "Invalid slot date/time.", 400);
}

if ($slotStart <= $now) {
    send_response(false, "Past date/time slot cannot be booked.", 400);
}

// Prevent duplicate active bookings for same slot
$checkSql = "SELECT booking_id
             FROM bookings
             WHERE slot_id = ?
               AND booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')
             LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    send_response(false, "Server error while checking booking.", 500);
}
$checkStmt->bind_param("i", $slot_id);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    send_response(false, "Slot already booked.", 200);
}
$checkStmt->close();

$subtotal = promo_calculate_slot_subtotal(
    floatval($slot['base_price'] ?? 0),
    (string)($slot['start_time'] ?? ''),
    (string)($slot['end_time'] ?? '')
);
if ($subtotal <= 0) {
    send_response(false, "Invalid slot price.", 400);
}

$discountAmount = 0;
$finalPrice = round($subtotal, 2);
if ($promo_code !== '') {
    $promoResult = promo_validate_for_owner_turf(
        $conn,
        intval($slot['owner_id'] ?? 0),
        $promo_code,
        $subtotal,
        $slotDate
    );
    if (!$promoResult['success']) {
        send_response(false, $promoResult['message'], 400);
    }
    $promo_code = (string)($promoResult['promo_code'] ?? $promo_code);
    $discountAmount = floatval($promoResult['discount_amount'] ?? 0);
    $finalPrice = floatval($promoResult['final_total'] ?? $subtotal);
}

if ($finalPrice <= 0) {
    send_response(false, "Invalid final booking amount.", 400);
}

if ($client_booked_price <= 0) {
    send_response(false, "Booked price must be positive.", 400);
}

$insertSql = "INSERT INTO bookings
              (user_id, slot_id, booking_status, booked_price, refund_amount, promo_code, created_at)
              VALUES (?, ?, 'pending', ?, 0, ?, NOW())";
$stmt = $conn->prepare($insertSql);
if (!$stmt) {
    send_response(false, "Server error while creating booking.", 500);
}

$stmt->bind_param("iids", $user_id, $slot_id, $finalPrice, $promo_code);
if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    send_response(false, "Booking failed. " . $error, 500);
}

$bookingId = (int)$stmt->insert_id;
$stmt->close();

send_response(true, "Booking request sent successfully.", 200, [
    "booking_id" => $bookingId,
    "booked_price" => $finalPrice,
    "discount_amount" => $discountAmount,
    "promo_code" => $promo_code
]);
?>
