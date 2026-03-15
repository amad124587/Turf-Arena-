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

function ensure_owner_wallet_tables($conn) {
    if (!table_exists($conn, 'turf_owners')) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS owner_wallets (
        owner_wallet_id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL UNIQUE,
        balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_owner_wallet_owner FOREIGN KEY (owner_id) REFERENCES turf_owners(owner_id)
          ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB");

    $conn->query("CREATE TABLE IF NOT EXISTS owner_wallet_transactions (
        owner_wallet_txn_id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        txn_type ENUM('cancellation_share','booking_income','withdrawal','adjustment') NOT NULL DEFAULT 'cancellation_share',
        amount DECIMAL(10,2) NOT NULL,
        reference_note VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_owner_wallet_txn_owner FOREIGN KEY (owner_id) REFERENCES turf_owners(owner_id)
          ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB");
}

function resolve_admin_id_for_fk($conn, $adminId) {
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
$refundId = intval($data['refund_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));
$adminNote = trim((string)($data['admin_note'] ?? ''));

if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

if ($refundId <= 0) {
    send_json_response(false, 'Invalid refund id.', 400);
}

if ($action === 'process') $action = 'paid';
if ($action === 'approve') $action = 'approved';
if ($action === 'reject') $action = 'rejected';

$allowed = ['approved', 'rejected', 'paid'];
if (!in_array($action, $allowed, true)) {
    send_json_response(false, 'Invalid refund action.', 400);
}

$persistAdminId = resolve_admin_id_for_fk($conn, $adminId);

$sql = "SELECT
          r.refund_id,
          r.booking_id,
          r.status,
          r.requested_amount,
          b.user_id,
          b.booked_price,
          b.booking_status,
          t.owner_id,
          t.turf_name
        FROM refund_requests r
        JOIN bookings b ON b.booking_id = r.booking_id
        JOIN slots s ON s.slot_id = b.slot_id
        JOIN turfs t ON t.turf_id = s.turf_id
        WHERE r.refund_id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json_response(false, 'Server error while loading refund request.', 500);
}
$stmt->bind_param('i', $refundId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    send_json_response(false, 'Refund request not found.', 404);
}

$currentStatus = strtolower((string)($row['status'] ?? 'pending'));
$bookingId = (int)$row['booking_id'];
$userId = (int)$row['user_id'];
$ownerId = (int)$row['owner_id'];
$turfName = (string)($row['turf_name'] ?? 'Turf');
$bookedPrice = (float)($row['booked_price'] ?? 0);

if ($bookedPrice <= 0) {
    send_json_response(false, 'Invalid booking amount for refund processing.', 400);
}

$ownerShare = round($bookedPrice * 0.20, 2);
$userRefund = round($bookedPrice - $ownerShare, 2);

if ($action === 'rejected') {
    if ($currentStatus === 'rejected') {
        send_json_response(true, 'Refund request already rejected.', 200, [
            'refund_id' => $refundId,
            'status' => 'rejected'
        ]);
    }

    if ($persistAdminId > 0) {
        $updateSql = 'UPDATE refund_requests SET status = ?, admin_id = ?, admin_note = ?, updated_at = NOW() WHERE refund_id = ? LIMIT 1';
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            send_json_response(false, 'Server error while rejecting refund.', 500);
        }
        $rejected = 'rejected';
        $updateStmt->bind_param('sisi', $rejected, $persistAdminId, $adminNote, $refundId);
    } else {
        $updateSql = 'UPDATE refund_requests SET status = ?, admin_id = NULL, admin_note = ?, updated_at = NOW() WHERE refund_id = ? LIMIT 1';
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            send_json_response(false, 'Server error while rejecting refund.', 500);
        }
        $rejected = 'rejected';
        $updateStmt->bind_param('ssi', $rejected, $adminNote, $refundId);
    }

    $ok = $updateStmt->execute();
    $updateStmt->close();

    if (!$ok) {
        send_json_response(false, 'Failed to reject refund request.', 500);
    }

    $extra = [
        'refund_id' => $refundId,
        'status' => 'rejected'
    ];
    if ($persistAdminId <= 0) {
        $extra['warning'] = 'Admin id is not in admins table, so admin_id stored as NULL.';
    }

    send_json_response(true, 'Refund request rejected.', 200, $extra);
}

if ($currentStatus === 'approved' || $currentStatus === 'paid') {
    send_json_response(true, 'Refund already processed.', 200, [
        'refund_id' => $refundId,
        'status' => $currentStatus,
        'booking_id' => $bookingId,
        'refund_amount' => $userRefund,
        'owner_share' => $ownerShare
    ]);
}

$inTransaction = false;

try {
    $conn->begin_transaction();
    $inTransaction = true;

    $finalStatus = 'paid';
    $finalNote = $adminNote !== '' ? $adminNote : 'Admin approved cancellation refund payout';

    if ($persistAdminId > 0) {
        $refundUpdateSql = 'UPDATE refund_requests SET status = ?, requested_amount = ?, admin_id = ?, admin_note = ?, updated_at = NOW() WHERE refund_id = ? LIMIT 1';
        $refundUpdateStmt = $conn->prepare($refundUpdateSql);
        if (!$refundUpdateStmt) {
            throw new RuntimeException('Server error while updating refund status.');
        }
        $refundUpdateStmt->bind_param('sdisi', $finalStatus, $userRefund, $persistAdminId, $finalNote, $refundId);
    } else {
        $refundUpdateSql = 'UPDATE refund_requests SET status = ?, requested_amount = ?, admin_id = NULL, admin_note = ?, updated_at = NOW() WHERE refund_id = ? LIMIT 1';
        $refundUpdateStmt = $conn->prepare($refundUpdateSql);
        if (!$refundUpdateStmt) {
            throw new RuntimeException('Server error while updating refund status.');
        }
        $refundUpdateStmt->bind_param('sdsi', $finalStatus, $userRefund, $finalNote, $refundId);
    }

    $ok = $refundUpdateStmt->execute();
    $refundUpdateStmt->close();
    if (!$ok) {
        throw new RuntimeException('Failed to update refund request.');
    }

    $bookingUpdateSql = "UPDATE bookings
                         SET booking_status = 'cancelled', refund_amount = ?
                         WHERE booking_id = ?
                         LIMIT 1";
    $bookingStmt = $conn->prepare($bookingUpdateSql);
    if ($bookingStmt) {
        $bookingStmt->bind_param('di', $userRefund, $bookingId);
        $bookingStmt->execute();
        $bookingStmt->close();
    }

    if (table_exists($conn, 'wallets')) {
        $initWalletSql = 'INSERT INTO wallets (user_id, balance) VALUES (?, 0) ON DUPLICATE KEY UPDATE user_id = user_id';
        $initStmt = $conn->prepare($initWalletSql);
        if ($initStmt) {
            $initStmt->bind_param('i', $userId);
            $initStmt->execute();
            $initStmt->close();
        }

        $walletUpdateSql = 'UPDATE wallets SET balance = balance + ? WHERE user_id = ?';
        $walletStmt = $conn->prepare($walletUpdateSql);
        if ($walletStmt) {
            $walletStmt->bind_param('di', $userRefund, $userId);
            $walletStmt->execute();
            $walletStmt->close();
        }

        if (table_exists($conn, 'wallet_transactions')) {
            $txnType = 'refund';
            $note = 'Refund for booking #' . $bookingId . ' approved by admin';
            $txnSql = 'INSERT INTO wallet_transactions (user_id, txn_type, amount, reference_note) VALUES (?, ?, ?, ?)';
            $txnStmt = $conn->prepare($txnSql);
            if ($txnStmt) {
                $txnStmt->bind_param('isds', $userId, $txnType, $userRefund, $note);
                $txnStmt->execute();
                $txnStmt->close();
            }
        }
    }

    if ($ownerId > 0) {
        ensure_owner_wallet_tables($conn);

        if (table_exists($conn, 'owner_wallets')) {
            $ownerInitSql = 'INSERT INTO owner_wallets (owner_id, balance) VALUES (?, 0) ON DUPLICATE KEY UPDATE owner_id = owner_id';
            $ownerInitStmt = $conn->prepare($ownerInitSql);
            if ($ownerInitStmt) {
                $ownerInitStmt->bind_param('i', $ownerId);
                $ownerInitStmt->execute();
                $ownerInitStmt->close();
            }

            $ownerUpdateSql = 'UPDATE owner_wallets SET balance = balance + ? WHERE owner_id = ?';
            $ownerWalletStmt = $conn->prepare($ownerUpdateSql);
            if ($ownerWalletStmt) {
                $ownerWalletStmt->bind_param('di', $ownerShare, $ownerId);
                $ownerWalletStmt->execute();
                $ownerWalletStmt->close();
            }
        }

        if (table_exists($conn, 'owner_wallet_transactions')) {
            $ownerTxnType = 'cancellation_share';
            $ownerNote = '20% cancellation share from booking #' . $bookingId . ' (' . $turfName . ')';
            $ownerTxnSql = 'INSERT INTO owner_wallet_transactions (owner_id, txn_type, amount, reference_note) VALUES (?, ?, ?, ?)';
            $ownerTxnStmt = $conn->prepare($ownerTxnSql);
            if ($ownerTxnStmt) {
                $ownerTxnStmt->bind_param('isds', $ownerId, $ownerTxnType, $ownerShare, $ownerNote);
                $ownerTxnStmt->execute();
                $ownerTxnStmt->close();
            }
        }
    }

    if (table_exists($conn, 'notifications')) {
        $userTitle = 'Refund approved';
        $userMessage = 'Booking #' . $bookingId . ' refund approved. Tk ' . number_format($userRefund, 2) . ' credited to your wallet.';
        if ($adminNote !== '') {
            $userMessage .= ' Admin note: ' . $adminNote;
        }
        $userNotifSql = 'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)';
        $userNotifStmt = $conn->prepare($userNotifSql);
        if ($userNotifStmt) {
            $userNotifStmt->bind_param('iss', $userId, $userTitle, $userMessage);
            $userNotifStmt->execute();
            $userNotifStmt->close();
        }

        if ($ownerId > 0) {
            $ownerTitle = 'Cancellation share credited';
            $ownerMessage = 'Tk ' . number_format($ownerShare, 2) . ' (20%) credited from booking #' . $bookingId . '.';
            $ownerNotifSql = 'INSERT INTO notifications (owner_id, title, message) VALUES (?, ?, ?)';
            $ownerNotifStmt = $conn->prepare($ownerNotifSql);
            if ($ownerNotifStmt) {
                $ownerNotifStmt->bind_param('iss', $ownerId, $ownerTitle, $ownerMessage);
                $ownerNotifStmt->execute();
                $ownerNotifStmt->close();
            }
        }
    }

    $conn->commit();
    $inTransaction = false;

    $extra = [
        'refund_id' => $refundId,
        'status' => $finalStatus,
        'booking_id' => $bookingId,
        'refund_amount' => $userRefund,
        'owner_share' => $ownerShare
    ];
    if ($persistAdminId <= 0) {
        $extra['warning'] = 'Admin id is not in admins table, so admin_id stored as NULL.';
    }

    send_json_response(true, 'Refund approved and payout completed.', 200, $extra);
} catch (Throwable $e) {
    if ($inTransaction) {
        $conn->rollback();
    }
    send_json_response(false, 'Refund action failed: ' . $e->getMessage(), 500);
}
?>
