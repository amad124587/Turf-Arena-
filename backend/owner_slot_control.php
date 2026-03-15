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
date_default_timezone_set('Asia/Dhaka');

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

function parse_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function seed_default_slots_if_missing($conn, $turfId, $slotDate, $basePrice) {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM slots WHERE turf_id = ? AND slot_date = ?');
    if (!$countStmt) {
        return false;
    }
    $countStmt->bind_param('is', $turfId, $slotDate);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $countRow = $countRes ? $countRes->fetch_assoc() : null;
    $countStmt->close();

    if (($countRow && intval($countRow['total'] ?? 0) > 0) || $basePrice <= 0) {
        return true;
    }

    $insertStmt = $conn->prepare('
        INSERT IGNORE INTO slots
        (turf_id, slot_date, start_time, end_time, base_price, is_enabled, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ');
    if (!$insertStmt) {
        return false;
    }

    for ($hour = 10; $hour <= 23; $hour++) {
        $start = sprintf('%02d:00:00', $hour);
        $end = ($hour === 23) ? '24:00:00' : sprintf('%02d:00:00', $hour + 1);
        $insertStmt->bind_param('isssd', $turfId, $slotDate, $start, $end, $basePrice);
        if (!$insertStmt->execute()) {
            $insertStmt->close();
            return false;
        }
    }

    $insertStmt->close();
    return true;
}

function get_owner_turfs($conn, $ownerId) {
    $turfs = [];
    $stmt = $conn->prepare("
        SELECT turf_id, turf_name, price_per_hour, status
        FROM turfs
        WHERE owner_id = ?
        ORDER BY turf_name ASC
    ");
    if (!$stmt) {
        return $turfs;
    }
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res ? $res->fetch_assoc() : null) {
        if (!$row) break;
        $turfs[] = [
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name'],
            'price_per_hour' => (float)($row['price_per_hour'] ?? 0),
            'status' => $row['status']
        ];
    }
    $stmt->close();
    return $turfs;
}

function get_slots_for_day($conn, $ownerId, $turfId, $slotDate) {
    $turfStmt = $conn->prepare('
        SELECT turf_id, turf_name, price_per_hour
        FROM turfs
        WHERE owner_id = ? AND turf_id = ?
        LIMIT 1
    ');
    if (!$turfStmt) {
        return [null, null];
    }
    $turfStmt->bind_param('ii', $ownerId, $turfId);
    $turfStmt->execute();
    $turfRes = $turfStmt->get_result();
    $turfRow = $turfRes ? $turfRes->fetch_assoc() : null;
    $turfStmt->close();

    if (!$turfRow) {
        return [null, null];
    }

    if (!seed_default_slots_if_missing($conn, $turfId, $slotDate, floatval($turfRow['price_per_hour'] ?? 0))) {
        return [$turfRow, null];
    }

    $todayDate = date('Y-m-d');
    $nowTime = date('H:i:s');

    $sql = "
    SELECT
      s.slot_id,
      s.slot_date,
      s.start_time,
      s.end_time,
      s.base_price,
      COALESCE(s.is_enabled, 1) AS is_enabled,
      b.booking_id,
      COALESCE(b.booking_status, '') AS booking_status
    FROM slots s
    LEFT JOIN bookings b
      ON b.booking_id = (
          SELECT b2.booking_id
          FROM bookings b2
          WHERE b2.slot_id = s.slot_id
            AND b2.booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')
          ORDER BY b2.booking_id DESC
          LIMIT 1
      )
    WHERE s.turf_id = ?
      AND s.slot_date = ?
      AND COALESCE(b.booking_id, 0) = 0
    ORDER BY s.start_time ASC, s.end_time ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [$turfRow, null];
    }
    $stmt->bind_param('is', $turfId, $slotDate);
    $stmt->execute();
    $res = $stmt->get_result();

    $slots = [];
    while ($row = $res ? $res->fetch_assoc() : null) {
        if (!$row) break;
        if ($slotDate < $todayDate) {
            continue;
        }
        if ($slotDate === $todayDate && (string)$row['start_time'] <= $nowTime) {
            continue;
        }
        $slots[] = [
            'slot_id' => (int)$row['slot_id'],
            'slot_date' => $row['slot_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'base_price' => (float)($row['base_price'] ?? 0),
            'is_enabled' => intval($row['is_enabled'] ?? 1) === 1,
            'booking_id' => 0,
            'booking_status' => '',
            'is_booked' => false
        ];
    }
    $stmt->close();

    return [$turfRow, $slots];
}

$requestOwnerId = intval($_GET['owner_id'] ?? ($_POST['owner_id'] ?? 0));
if ($requestOwnerId <= 0) {
    $body = parse_json_body();
    $requestOwnerId = intval($body['owner_id'] ?? 0);
}

if ($requestOwnerId <= 0) {
    send_response(false, 'Invalid owner id.', 400);
}

$ownerStmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1');
if (!$ownerStmt) {
    send_response(false, 'Server error while checking owner.', 500);
}
$ownerStmt->bind_param('i', $requestOwnerId);
$ownerStmt->execute();
$ownerRes = $ownerStmt->get_result();
$ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
$ownerStmt->close();

if (!$ownerRow) {
    send_response(false, 'Owner not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $turfs = get_owner_turfs($conn, $requestOwnerId);
    $selectedTurfId = intval($_GET['turf_id'] ?? 0);
    $slotDate = trim((string)($_GET['slot_date'] ?? date('Y-m-d')));
    $todayDate = date('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate)) {
        $slotDate = date('Y-m-d');
    }

    if ($slotDate < $todayDate) {
        send_response(true, 'Past date slots are not controllable.', 200, [
            'turfs' => $turfs,
            'selected_turf' => null,
            'slot_date' => $slotDate,
            'slots' => []
        ]);
    }

    $selectedTurf = null;
    $slots = [];
    if ($selectedTurfId > 0) {
        [$selectedTurf, $slotRows] = get_slots_for_day($conn, $requestOwnerId, $selectedTurfId, $slotDate);
        if ($selectedTurf && is_array($slotRows)) {
            $slots = $slotRows;
        }
    }

    send_response(true, 'Owner slot data loaded.', 200, [
        'turfs' => $turfs,
        'selected_turf' => $selectedTurf,
        'slot_date' => $slotDate,
        'slots' => $slots
    ]);
}

$payload = parse_json_body();
$turfId = intval($payload['turf_id'] ?? 0);
$slotDate = trim((string)($payload['slot_date'] ?? ''));
$slotIds = $payload['slot_ids'] ?? [];
$isEnabled = intval($payload['is_enabled'] ?? -1);

if ($turfId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate) || !is_array($slotIds) || ($isEnabled !== 0 && $isEnabled !== 1)) {
    send_response(false, 'Missing or invalid slot control data.', 400);
}

$todayDate = date('Y-m-d');
if ($slotDate < $todayDate) {
    send_response(false, 'Past date slots cannot be updated.', 400);
}

$slotIds = array_values(array_filter(array_map('intval', $slotIds), function ($id) {
    return $id > 0;
}));
if (!count($slotIds)) {
    send_response(false, 'Please select at least one slot.', 400);
}

$placeholders = implode(',', array_fill(0, count($slotIds), '?'));
$types = 'iis' . str_repeat('i', count($slotIds));
$params = [$requestOwnerId, $turfId, $slotDate];
foreach ($slotIds as $slotId) {
    $params[] = $slotId;
}

$sql = "
SELECT
  s.slot_id,
  COALESCE(b.booking_id, 0) AS booking_id,
  s.start_time
FROM slots s
JOIN turfs t ON t.turf_id = s.turf_id
LEFT JOIN bookings b
  ON b.booking_id = (
      SELECT b2.booking_id
      FROM bookings b2
      WHERE b2.slot_id = s.slot_id
        AND b2.booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')
      ORDER BY b2.booking_id DESC
      LIMIT 1
  )
WHERE t.owner_id = ?
  AND s.turf_id = ?
  AND s.slot_date = ?
  AND s.slot_id IN ($placeholders)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while validating selected slots.', 500);
}

$bind = [];
$bind[] = &$types;
foreach ($params as $index => $value) {
    $bind[] = &$params[$index];
}
call_user_func_array([$stmt, 'bind_param'], $bind);
$stmt->execute();
$res = $stmt->get_result();

$allowedSlotIds = [];
$skippedBooked = 0;
$skippedPast = 0;
    $nowTime = date('H:i:s');
while ($row = $res ? $res->fetch_assoc() : null) {
    if (!$row) break;
    if (intval($row['booking_id'] ?? 0) > 0) {
        $skippedBooked++;
        continue;
    }
    if ($slotDate === $todayDate && (string)($row['start_time'] ?? '') <= $nowTime) {
        $skippedPast++;
        continue;
    }
    $allowedSlotIds[] = intval($row['slot_id']);
}
$stmt->close();

if (!count($allowedSlotIds)) {
    send_response(false, 'Selected slots are already booked or unavailable for update.', 409, [
        'updated_count' => 0,
        'skipped_booked' => $skippedBooked,
        'skipped_past' => $skippedPast
    ]);
}

$updatePlaceholders = implode(',', array_fill(0, count($allowedSlotIds), '?'));
$updateTypes = 'is' . str_repeat('i', count($allowedSlotIds));
$updateParams = [$isEnabled, $slotDate];
foreach ($allowedSlotIds as $slotId) {
    $updateParams[] = $slotId;
}

$updateSql = "UPDATE slots SET is_enabled = ? WHERE slot_date = ? AND slot_id IN ($updatePlaceholders)";
$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    send_response(false, 'Server error while updating slots.', 500);
}

$updateBind = [];
$updateBind[] = &$updateTypes;
foreach ($updateParams as $index => $value) {
    $updateBind[] = &$updateParams[$index];
}
call_user_func_array([$updateStmt, 'bind_param'], $updateBind);
$updateStmt->execute();
$updatedCount = $updateStmt->affected_rows;
$updateStmt->close();

send_response(true, $isEnabled === 1 ? 'Selected slots are now enabled.' : 'Selected slots are now disabled.', 200, [
    'updated_count' => max(0, $updatedCount),
    'skipped_booked' => $skippedBooked,
    'skipped_past' => $skippedPast
]);
?>
