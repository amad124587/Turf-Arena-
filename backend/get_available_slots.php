<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once "db_connection.php";
date_default_timezone_set("Asia/Dhaka");

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        "success" => $success,
        "status" => $message
    ], $extra));
    exit();
}

function seed_default_slots_if_missing($conn, $turfId, $slotDate, $basePrice) {
    $countSql = "SELECT COUNT(*) AS total FROM slots WHERE turf_id = ? AND slot_date = ?";
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        return false;
    }

    $countStmt->bind_param("is", $turfId, $slotDate);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $row = $countResult ? $countResult->fetch_assoc() : null;
    $countStmt->close();

    if (($row && intval($row["total"] ?? 0) > 0) || $basePrice <= 0) {
        return true;
    }

    $insertSql = "INSERT IGNORE INTO slots
        (turf_id, slot_date, start_time, end_time, base_price, is_enabled, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())";

    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        return false;
    }

    for ($hour = 10; $hour <= 23; $hour++) {
        $start = sprintf("%02d:00:00", $hour);
        $end = ($hour === 23) ? "24:00:00" : sprintf("%02d:00:00", $hour + 1);

        $insertStmt->bind_param("isssd", $turfId, $slotDate, $start, $end, $basePrice);
        if (!$insertStmt->execute()) {
            $insertStmt->close();
            return false;
        }
    }

    $insertStmt->close();
    return true;
}

$turfId = intval($_GET['turf_id'] ?? 0);
$slotDate = trim((string)($_GET['slot_date'] ?? ''));

if ($turfId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate)) {
    send_response(false, "Invalid turf_id or slot_date.", 400);
}

$todayDate = date("Y-m-d");
$nowTime = date("H:i:s");
if ($slotDate < $todayDate) {
    send_response(true, "Past date is not available for booking.", 200, [
        "slots" => [],
        "count" => 0
    ]);
}

$priceStmt = $conn->prepare("SELECT price_per_hour FROM turfs WHERE turf_id = ? LIMIT 1");
if (!$priceStmt) {
    send_response(false, "Server error while loading turf.", 500);
}
$priceStmt->bind_param("i", $turfId);
$priceStmt->execute();
$priceResult = $priceStmt->get_result();
$turf = $priceResult ? $priceResult->fetch_assoc() : null;
$priceStmt->close();

if (!$turf) {
    send_response(false, "Turf not found.", 404);
}

$basePrice = floatval($turf['price_per_hour'] ?? 0);
if (!seed_default_slots_if_missing($conn, $turfId, $slotDate, $basePrice)) {
    send_response(false, "Server error while creating ready slots.", 500);
}

$sql = "
SELECT
    s.slot_id,
    s.slot_date,
    s.start_time,
    s.end_time,
    s.base_price
FROM slots s
LEFT JOIN bookings b
    ON b.slot_id = s.slot_id
    AND b.booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')
WHERE s.turf_id = ?
  AND s.slot_date = ?
  AND s.is_enabled = 1
  AND b.booking_id IS NULL
ORDER BY s.start_time ASC, s.end_time ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, "Server error while loading slots.", 500);
}

$stmt->bind_param("is", $turfId, $slotDate);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    if ($slotDate === $todayDate && (string)$row["start_time"] <= $nowTime) {
        continue;
    }

    $slots[] = [
        "slot_id" => (int)$row["slot_id"],
        "slot_date" => (string)$row["slot_date"],
        "start_time" => (string)$row["start_time"],
        "end_time" => (string)$row["end_time"],
        "base_price" => (float)$row["base_price"]
    ];
}

$stmt->close();

send_response(true, "Available slots loaded.", 200, [
    "slots" => $slots,
    "count" => count($slots)
]);
?>