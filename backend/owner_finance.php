<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

function table_exists($conn, $table) {
    $name = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$name'");
    return $res && $res->num_rows > 0;
}

$ownerId = intval($_GET['owner_id'] ?? 0);
if ($ownerId <= 0) {
    send_response(false, 'Invalid owner id.', 400);
}

$ownerStmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1');
if (!$ownerStmt) {
    send_response(false, 'Server error while checking owner.', 500);
}
$ownerStmt->bind_param('i', $ownerId);
$ownerStmt->execute();
$ownerRes = $ownerStmt->get_result();
$ownerRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
$ownerStmt->close();

if (!$ownerRow) {
    send_response(false, 'Owner not found.', 404);
}

$summary = [
    'wallet_balance' => 0,
    'total_cancellation_earnings' => 0,
    'pending_refund_requests' => 0,
    'cancelled_bookings' => 0,
    'active_turfs' => 0
];

$transactions = [];

if (table_exists($conn, 'bookings') && table_exists($conn, 'slots') && table_exists($conn, 'turfs')) {
    $walletSql = "SELECT COALESCE(SUM(b.booked_price), 0) AS amount
                  FROM bookings b
                  JOIN slots s ON s.slot_id = b.slot_id
                  JOIN turfs t ON t.turf_id = s.turf_id
                  WHERE t.owner_id = ?
                    AND b.booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')";
    $walletStmt = $conn->prepare($walletSql);
    if ($walletStmt) {
        $walletStmt->bind_param('i', $ownerId);
        $walletStmt->execute();
        $walletRes = $walletStmt->get_result();
        $walletRow = $walletRes ? $walletRes->fetch_assoc() : null;
        $walletStmt->close();
        if ($walletRow) {
            $summary['wallet_balance'] = (float)($walletRow['amount'] ?? 0);
        }
    }
}

if (table_exists($conn, 'turfs')) {
    $activeStmt = $conn->prepare("SELECT COUNT(*) AS c FROM turfs WHERE owner_id = ? AND status = 'active'");
    if ($activeStmt) {
        $activeStmt->bind_param('i', $ownerId);
        $activeStmt->execute();
        $activeRes = $activeStmt->get_result();
        $activeRow = $activeRes ? $activeRes->fetch_assoc() : null;
        $activeStmt->close();
        $summary['active_turfs'] = (int)($activeRow['c'] ?? 0);
    }
}

if (table_exists($conn, 'refund_requests') && table_exists($conn, 'bookings') && table_exists($conn, 'slots') && table_exists($conn, 'turfs')) {
    $cancelEarningSql = "SELECT COALESCE(SUM(b.booked_price - COALESCE(r.requested_amount, 0)), 0) AS total_amount
                         FROM refund_requests r
                         JOIN bookings b ON b.booking_id = r.booking_id
                         JOIN slots s ON s.slot_id = b.slot_id
                         JOIN turfs t ON t.turf_id = s.turf_id
                         WHERE t.owner_id = ? AND r.status = 'paid'";
    $cancelEarningStmt = $conn->prepare($cancelEarningSql);
    if ($cancelEarningStmt) {
        $cancelEarningStmt->bind_param('i', $ownerId);
        $cancelEarningStmt->execute();
        $cancelEarningRes = $cancelEarningStmt->get_result();
        $cancelEarningRow = $cancelEarningRes ? $cancelEarningRes->fetch_assoc() : null;
        $cancelEarningStmt->close();
        if ($cancelEarningRow) {
            $summary['total_cancellation_earnings'] = (float)($cancelEarningRow['total_amount'] ?? 0);
            $summary['wallet_balance'] += $summary['total_cancellation_earnings'];
        }
    }

    $pendingSql = "SELECT COUNT(*) AS c
                   FROM refund_requests r
                   JOIN bookings b ON b.booking_id = r.booking_id
                   JOIN slots s ON s.slot_id = b.slot_id
                   JOIN turfs t ON t.turf_id = s.turf_id
                   WHERE t.owner_id = ? AND r.status = 'pending'";
    $pendingStmt = $conn->prepare($pendingSql);
    if ($pendingStmt) {
        $pendingStmt->bind_param('i', $ownerId);
        $pendingStmt->execute();
        $pendingRes = $pendingStmt->get_result();
        $pendingRow = $pendingRes ? $pendingRes->fetch_assoc() : null;
        $pendingStmt->close();
        $summary['pending_refund_requests'] = (int)($pendingRow['c'] ?? 0);
    }

    $cancelSql = "SELECT COUNT(*) AS c
                  FROM refund_requests r
                  JOIN bookings b ON b.booking_id = r.booking_id
                  JOIN slots s ON s.slot_id = b.slot_id
                  JOIN turfs t ON t.turf_id = s.turf_id
                  WHERE t.owner_id = ? AND r.status = 'paid'";
    $cancelStmt = $conn->prepare($cancelSql);
    if ($cancelStmt) {
        $cancelStmt->bind_param('i', $ownerId);
        $cancelStmt->execute();
        $cancelRes = $cancelStmt->get_result();
        $cancelRow = $cancelRes ? $cancelRes->fetch_assoc() : null;
        $cancelStmt->close();
        $summary['cancelled_bookings'] = (int)($cancelRow['c'] ?? 0);
    }

}

if (table_exists($conn, 'bookings') && table_exists($conn, 'slots') && table_exists($conn, 'turfs')) {
    $bookingTxnSql = "SELECT
                        b.booking_id AS txn_id,
                        'booking_income' AS txn_type,
                        b.booked_price AS amount,
                        CONCAT('Booking #', b.booking_id, ' income from ', t.turf_name) AS reference_note,
                        b.created_at
                      FROM bookings b
                      JOIN slots s ON s.slot_id = b.slot_id
                      JOIN turfs t ON t.turf_id = s.turf_id
                      WHERE t.owner_id = ?
                        AND b.booking_status IN ('pending', 'confirmed', 'completed', 'resell_listed', 'resold')
                      ORDER BY b.created_at DESC
                      LIMIT 8";
    $bookingTxnStmt = $conn->prepare($bookingTxnSql);
    if ($bookingTxnStmt) {
        $bookingTxnStmt->bind_param('i', $ownerId);
        $bookingTxnStmt->execute();
        $bookingTxnRes = $bookingTxnStmt->get_result();
        while ($row = $bookingTxnRes ? $bookingTxnRes->fetch_assoc() : null) {
            if (!$row) break;
            $transactions[] = [
                'txn_id' => (int)$row['txn_id'],
                'txn_type' => $row['txn_type'],
                'amount' => (float)($row['amount'] ?? 0),
                'reference_note' => $row['reference_note'],
                'created_at' => $row['created_at']
            ];
        }
        $bookingTxnStmt->close();
    }
}

if (table_exists($conn, 'refund_requests') && table_exists($conn, 'bookings') && table_exists($conn, 'slots') && table_exists($conn, 'turfs')) {
    $cancelTxnSql = "SELECT
                       r.refund_id AS txn_id,
                       'cancellation_share' AS txn_type,
                       (b.booked_price - COALESCE(r.requested_amount, 0)) AS amount,
                       CONCAT('Cancellation earning from booking #', b.booking_id, ' (', t.turf_name, ')') AS reference_note,
                       r.updated_at AS created_at
                     FROM refund_requests r
                     JOIN bookings b ON b.booking_id = r.booking_id
                     JOIN slots s ON s.slot_id = b.slot_id
                     JOIN turfs t ON t.turf_id = s.turf_id
                     WHERE t.owner_id = ?
                       AND r.status = 'paid'
                     ORDER BY r.updated_at DESC
                     LIMIT 8";
    $cancelTxnStmt = $conn->prepare($cancelTxnSql);
    if ($cancelTxnStmt) {
        $cancelTxnStmt->bind_param('i', $ownerId);
        $cancelTxnStmt->execute();
        $cancelTxnRes = $cancelTxnStmt->get_result();
        while ($row = $cancelTxnRes ? $cancelTxnRes->fetch_assoc() : null) {
            if (!$row) break;
            $transactions[] = [
                'txn_id' => (int)$row['txn_id'],
                'txn_type' => $row['txn_type'],
                'amount' => (float)($row['amount'] ?? 0),
                'reference_note' => $row['reference_note'],
                'created_at' => $row['created_at']
            ];
        }
        $cancelTxnStmt->close();
    }
}

usort($transactions, static function ($a, $b) {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});
$transactions = array_slice($transactions, 0, 8);

send_response(true, 'Owner finance loaded.', 200, [
    'summary' => $summary,
    'transactions' => $transactions
]);
?>
