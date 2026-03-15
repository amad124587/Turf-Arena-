<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

function parse_request_body() {
    $payload = $_POST;
    if (!count($payload)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }
    return $payload;
}

function ensure_owner_exists($conn, $ownerId) {
    $stmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return !!$row;
}

function load_owner_promos($conn, $ownerId) {
    $rows = [];
    $stmt = $conn->prepare('
        SELECT promo_id, code, discount_type, discount_value, min_booking_amount, start_date, end_date, is_active, created_at
        FROM promo_codes
        WHERE owner_id = ?
        ORDER BY promo_id DESC
    ');
    if (!$stmt) {
        return $rows;
    }
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res ? $res->fetch_assoc() : null) {
        if (!$row) break;
        $rows[] = [
            'promo_id' => (int)$row['promo_id'],
            'code' => $row['code'],
            'discount_type' => $row['discount_type'],
            'discount_value' => (float)($row['discount_value'] ?? 0),
            'min_booking_amount' => (float)($row['min_booking_amount'] ?? 0),
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'is_active' => intval($row['is_active'] ?? 0) === 1,
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    return $rows;
}

$request = parse_request_body();
$ownerId = intval($_GET['owner_id'] ?? ($request['owner_id'] ?? 0));
if ($ownerId <= 0) {
    send_response(false, 'Invalid owner id.', 400);
}

if (!ensure_owner_exists($conn, $ownerId)) {
    send_response(false, 'Owner not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $promos = load_owner_promos($conn, $ownerId);
    $summary = [
        'total_codes' => count($promos),
        'active_codes' => count(array_filter($promos, function ($item) { return $item['is_active']; })),
        'inactive_codes' => count(array_filter($promos, function ($item) { return !$item['is_active']; }))
    ];
    send_response(true, 'Promo codes loaded.', 200, [
        'promos' => $promos,
        'summary' => $summary
    ]);
}

$action = strtolower(trim((string)($request['action'] ?? 'create')));

if ($action === 'toggle') {
    $promoId = intval($request['promo_id'] ?? 0);
    $isActive = intval($request['is_active'] ?? -1);
    if ($promoId <= 0 || ($isActive !== 0 && $isActive !== 1)) {
        send_response(false, 'Invalid promo toggle request.', 400);
    }

    $stmt = $conn->prepare('UPDATE promo_codes SET is_active = ? WHERE promo_id = ? AND owner_id = ? LIMIT 1');
    if (!$stmt) {
        send_response(false, 'Server error while updating promo status.', 500);
    }
    $stmt->bind_param('iii', $isActive, $promoId, $ownerId);
    $stmt->execute();
    $stmt->close();

    send_response(true, $isActive === 1 ? 'Promo code activated.' : 'Promo code deactivated.');
}

$code = strtoupper(trim((string)($request['code'] ?? '')));
$discountType = strtolower(trim((string)($request['discount_type'] ?? 'percent')));
$discountValue = floatval($request['discount_value'] ?? 0);
$minBookingAmount = floatval($request['min_booking_amount'] ?? 0);
$startDate = trim((string)($request['start_date'] ?? ''));
$endDate = trim((string)($request['end_date'] ?? ''));

if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,20}$/', $code)) {
    send_response(false, 'Promo code must be 3-20 characters using letters, numbers, dash, or underscore.', 400);
}
if (!in_array($discountType, ['percent', 'fixed'], true)) {
    send_response(false, 'Invalid discount type.', 400);
}
if ($discountValue <= 0) {
    send_response(false, 'Discount value must be greater than zero.', 400);
}
if ($discountType === 'percent' && $discountValue > 100) {
    send_response(false, 'Percent discount cannot be greater than 100.', 400);
}
if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    send_response(false, 'Invalid start date.', 400);
}
if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    send_response(false, 'Invalid end date.', 400);
}
if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
    send_response(false, 'End date cannot be earlier than start date.', 400);
}

$stmt = $conn->prepare('
    INSERT INTO promo_codes
    (owner_id, code, discount_type, discount_value, min_booking_amount, start_date, end_date, is_active, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
');
if (!$stmt) {
    send_response(false, 'Server error while creating promo code.', 500);
}
$stmt->bind_param('issddss', $ownerId, $code, $discountType, $discountValue, $minBookingAmount, $startDate, $endDate);

if (!$stmt->execute()) {
    $error = strtolower((string)$stmt->error);
    $stmt->close();
    if (strpos($error, 'duplicate') !== false || strpos($error, 'unique') !== false) {
        send_response(false, 'This promo code already exists.', 409);
    }
    send_response(false, 'Failed to create promo code.', 500);
}
$promoId = (int)$stmt->insert_id;
$stmt->close();

send_response(true, 'Promo code created successfully.', 200, [
    'promo_id' => $promoId
]);
?>
