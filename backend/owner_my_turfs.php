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

function load_owner_turfs($conn, $ownerId) {
    $rows = [];
    $stmt = $conn->prepare('
        SELECT turf_id, turf_name, sport_type, address, area, city, price_per_hour, status, cancel_before_hours, refund_percent, created_at
        FROM turfs
        WHERE owner_id = ?
        ORDER BY turf_id DESC
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
            'turf_id' => (int)$row['turf_id'],
            'turf_name' => $row['turf_name'],
            'sport_type' => $row['sport_type'],
            'address' => $row['address'],
            'area' => $row['area'],
            'city' => $row['city'],
            'price_per_hour' => (float)($row['price_per_hour'] ?? 0),
            'status' => $row['status'],
            'cancel_before_hours' => (int)($row['cancel_before_hours'] ?? 0),
            'refund_percent' => (int)($row['refund_percent'] ?? 0),
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
    $turfs = load_owner_turfs($conn, $ownerId);
    $summary = [
        'total' => count($turfs),
        'active' => count(array_filter($turfs, function ($item) { return strtolower((string)$item['status']) === 'active'; })),
        'inactive' => count(array_filter($turfs, function ($item) { return strtolower((string)$item['status']) === 'inactive'; })),
        'pending' => count(array_filter($turfs, function ($item) { return strtolower((string)$item['status']) === 'pending'; }))
    ];

    send_response(true, 'Owner turfs loaded.', 200, [
        'turfs' => $turfs,
        'summary' => $summary
    ]);
}

$turfId = intval($request['turf_id'] ?? 0);
$status = strtolower(trim((string)($request['status'] ?? '')));

if ($turfId <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    send_response(false, 'Invalid turf status update request.', 400);
}

$checkStmt = $conn->prepare('SELECT status FROM turfs WHERE turf_id = ? AND owner_id = ? LIMIT 1');
if (!$checkStmt) {
    send_response(false, 'Server error while checking turf.', 500);
}
$checkStmt->bind_param('ii', $turfId, $ownerId);
$checkStmt->execute();
$res = $checkStmt->get_result();
$turf = $res ? $res->fetch_assoc() : null;
$checkStmt->close();

if (!$turf) {
    send_response(false, 'Turf not found for this owner.', 404);
}

$currentStatus = strtolower((string)($turf['status'] ?? ''));
if ($currentStatus === 'pending' || $currentStatus === 'rejected') {
    send_response(false, 'This turf cannot be switched until it is approved.', 400);
}

$stmt = $conn->prepare('UPDATE turfs SET status = ? WHERE turf_id = ? AND owner_id = ? LIMIT 1');
if (!$stmt) {
    send_response(false, 'Server error while updating turf status.', 500);
}
$stmt->bind_param('sii', $status, $turfId, $ownerId);
$stmt->execute();
$stmt->close();

send_response(true, $status === 'active' ? 'Turf turned on successfully.' : 'Turf turned off successfully.');
?>
