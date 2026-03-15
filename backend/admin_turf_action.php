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

function resolve_admin_id_for_verification($conn, $adminId) {
    if ($adminId <= 0 || !table_exists($conn, 'admins')) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT admin_id FROM admins WHERE admin_id = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['admin_id'] ?? 0);
}

$data = read_request_data();
if (empty($data)) {
    send_json_response(false, 'No data received.', 400);
}

$adminId = intval($data['admin_id'] ?? 0);
$turfId = intval($data['turf_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));
$note = trim((string)($data['note'] ?? ''));

if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

if ($turfId <= 0) {
    send_json_response(false, 'Invalid turf id.', 400);
}

$allowed = ['approved', 'rejected', 'requested_changes'];
if (!in_array($action, $allowed, true)) {
    send_json_response(false, 'Invalid action.', 400);
}

$statusMap = [
    'approved' => 'active',
    'rejected' => 'rejected',
    'requested_changes' => 'pending'
];
$newStatus = $statusMap[$action] ?? 'pending';

$checkStmt = $conn->prepare('SELECT turf_id, status FROM turfs WHERE turf_id = ? LIMIT 1');
if (!$checkStmt) {
    send_json_response(false, 'Server error while loading turf.', 500);
}
$checkStmt->bind_param('i', $turfId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$turf = $checkRes ? $checkRes->fetch_assoc() : null;
$checkStmt->close();

if (!$turf) {
    send_json_response(false, 'Turf not found.', 404);
}

$logAdminId = resolve_admin_id_for_verification($conn, $adminId);
$verificationLogged = false;

try {
    $conn->begin_transaction();

    $updateStmt = $conn->prepare('UPDATE turfs SET status = ? WHERE turf_id = ? LIMIT 1');
    if (!$updateStmt) {
        throw new RuntimeException('Server error while updating turf.');
    }

    $updateStmt->bind_param('si', $newStatus, $turfId);
    $ok = $updateStmt->execute();
    $updateStmt->close();

    if (!$ok) {
        throw new RuntimeException('Failed to update turf status.');
    }

    if (table_exists($conn, 'turf_verifications') && $logAdminId > 0) {
        $hasNote = column_exists($conn, 'turf_verifications', 'note');
        if ($hasNote) {
            $logSql = 'INSERT INTO turf_verifications (turf_id, admin_id, action, note) VALUES (?, ?, ?, ?)';
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param('iiss', $turfId, $logAdminId, $action, $note);
                $verificationLogged = $logStmt->execute();
                $logStmt->close();
            }
        } else {
            $logSql = 'INSERT INTO turf_verifications (turf_id, admin_id, action) VALUES (?, ?, ?)';
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param('iis', $turfId, $logAdminId, $action);
                $verificationLogged = $logStmt->execute();
                $logStmt->close();
            }
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->errno || $conn->sqlstate) {
        $conn->rollback();
    }
    send_json_response(false, 'Turf action failed: ' . $e->getMessage(), 500);
}

$response = [
    'turf_id' => $turfId,
    'action' => $action,
    'status' => $newStatus,
    'verification_logged' => $verificationLogged
];

if ($logAdminId <= 0) {
    $response['warning'] = 'Verification log skipped because admin_id is not present in admins table.';
}

send_json_response(true, 'Turf verification updated successfully.', 200, $response);
?>
