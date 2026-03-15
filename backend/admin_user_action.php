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
$targetType = strtolower(trim((string)($data['target_type'] ?? '')));
$targetId = intval($data['target_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));

if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

if ($targetId <= 0) {
    send_json_response(false, 'Invalid target id.', 400);
}

if ($action !== 'ban' && $action !== 'unban') {
    send_json_response(false, 'Invalid action.', 400);
}

if ($targetType !== 'user' && $targetType !== 'owner') {
    send_json_response(false, 'Invalid target type.', 400);
}

if ($targetType === 'user') {
    $newStatus = $action === 'ban' ? 'banned' : 'active';

    $checkStmt = $conn->prepare('SELECT status FROM users WHERE user_id = ? LIMIT 1');
    if (!$checkStmt) {
      send_json_response(false, 'Server error while loading user.', 500);
    }
    $checkStmt->bind_param('i', $targetId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $checkRow = $checkRes ? $checkRes->fetch_assoc() : null;
    $checkStmt->close();

    if (!$checkRow) {
      send_json_response(false, 'User not found.', 404);
    }

    $currentStatus = strtolower((string)($checkRow['status'] ?? 'active'));
    if ($currentStatus === $newStatus) {
      send_json_response(true, 'User already in requested status.', 200, [
        'target_type' => 'user',
        'target_id' => $targetId,
        'status' => $newStatus
      ]);
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        $conn->rollback();
        send_json_response(false, 'Server error while updating user.', 500);
    }
    $stmt->bind_param('si', $newStatus, $targetId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        $conn->rollback();
        send_json_response(false, 'User status update failed.', 500);
    }

    if (column_exists($conn, 'users', 'role') && $action === 'unban') {
        $roleStmt = $conn->prepare("UPDATE users SET role = 'user' WHERE user_id = ? AND role = 'banned' LIMIT 1");
        if ($roleStmt) {
            $roleStmt->bind_param('i', $targetId);
            $roleStmt->execute();
            $roleStmt->close();
        }
    }

    $conn->commit();
    send_json_response(true, 'User status updated successfully.', 200, [
        'target_type' => 'user',
        'target_id' => $targetId,
        'status' => $newStatus
    ]);
}

$newStatus = $action === 'ban' ? 'suspended' : 'verified';

$ownerCheckStmt = $conn->prepare('SELECT status FROM turf_owners WHERE owner_id = ? LIMIT 1');
if (!$ownerCheckStmt) {
    send_json_response(false, 'Server error while loading owner.', 500);
}
$ownerCheckStmt->bind_param('i', $targetId);
$ownerCheckStmt->execute();
$ownerCheckRes = $ownerCheckStmt->get_result();
$ownerCheckRow = $ownerCheckRes ? $ownerCheckRes->fetch_assoc() : null;
$ownerCheckStmt->close();

if (!$ownerCheckRow) {
    send_json_response(false, 'Owner not found.', 404);
}

$currentOwnerStatus = strtolower((string)($ownerCheckRow['status'] ?? 'pending'));
if ($currentOwnerStatus === $newStatus) {
    send_json_response(true, 'Owner already in requested status.', 200, [
        'target_type' => 'owner',
        'target_id' => $targetId,
        'status' => $newStatus
    ]);
}

$conn->begin_transaction();

$stmt = $conn->prepare('UPDATE turf_owners SET status = ? WHERE owner_id = ? LIMIT 1');
if (!$stmt) {
    $conn->rollback();
    send_json_response(false, 'Server error while updating owner.', 500);
}
$stmt->bind_param('si', $newStatus, $targetId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    $conn->rollback();
    send_json_response(false, 'Owner status update failed.', 500);
}

if (table_exists($conn, 'turfs') && $action === 'ban') {
    $turfStmt = $conn->prepare("UPDATE turfs SET status = 'inactive' WHERE owner_id = ? AND status = 'active'");
    if ($turfStmt) {
        $turfStmt->bind_param('i', $targetId);
        $turfStmt->execute();
        $turfStmt->close();
    }
}

$conn->commit();

send_json_response(true, 'Owner status updated successfully.', 200, [
    'target_type' => 'owner',
    'target_id' => $targetId,
    'status' => $newStatus
]);
?>
