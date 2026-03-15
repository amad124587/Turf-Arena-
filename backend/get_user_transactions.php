<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

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

$userId = intval($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    send_response(false, 'Invalid user id.', 400);
}

$rows = [];
$hasWalletTransactions = table_exists($conn, 'wallet_transactions');

$bookingSql = "
SELECT
  b.booking_id,
  b.booking_status,
  b.booked_price,
  b.refund_amount,
  b.created_at,
  s.slot_date,
  s.start_time,
  s.end_time,
  t.turf_name
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
WHERE b.user_id = ?
ORDER BY b.created_at DESC, b.booking_id DESC
";

$bookingStmt = $conn->prepare($bookingSql);
if (!$bookingStmt) {
    send_response(false, 'Server error while loading transactions.', 500);
}

$bookingStmt->bind_param('i', $userId);
$bookingStmt->execute();
$bookingRes = $bookingStmt->get_result();

while ($row = $bookingRes ? $bookingRes->fetch_assoc() : null) {
    if (!$row) {
        break;
    }

    $bookingId = (int)$row['booking_id'];
    $bookedPrice = (float)($row['booked_price'] ?? 0);
    $refundAmount = (float)($row['refund_amount'] ?? 0);
    $bookingStatus = strtolower((string)($row['booking_status'] ?? 'pending'));
    $slotSummary = trim(($row['slot_date'] ?? '') . ' ' . ($row['start_time'] ?? '') . ' - ' . ($row['end_time'] ?? ''));

    $hasRealPayment = in_array($bookingStatus, ['confirmed', 'completed', 'resell_listed', 'resold'], true)
        || ($bookingStatus === 'cancelled' && $refundAmount > 0);

    if ($hasRealPayment) {
        $rows[] = [
            'id' => 'payment-' . $bookingId,
            'type' => 'payment',
            'tone' => 'info',
            'reference' => 'Booking #' . $bookingId,
            'title' => 'Turf booking payment',
            'description' => trim(($row['turf_name'] ?? 'Turf booking') . ($slotSummary !== '' ? ' | ' . $slotSummary : '')),
            'method' => 'Booking payment',
            'status' => 'Paid',
            'amount' => -abs($bookedPrice),
            'time' => $row['created_at'],
            'sort_time' => $row['created_at']
        ];
    }

    if (!$hasWalletTransactions && $bookingStatus === 'cancelled' && $refundAmount > 0) {
        $rows[] = [
            'id' => 'refund-' . $bookingId,
            'type' => 'refund',
            'tone' => 'success',
            'reference' => 'Booking #' . $bookingId,
            'title' => 'Cancellation refund',
            'description' => 'Refund approved for ' . ($row['turf_name'] ?? 'cancelled booking'),
            'method' => 'Wallet refund',
            'status' => 'Approved',
            'amount' => abs($refundAmount),
            'time' => $row['created_at'],
            'sort_time' => $row['created_at']
        ];
    }
}

$bookingStmt->close();

if ($hasWalletTransactions) {
    $walletSql = "
    SELECT wallet_txn_id, txn_type, amount, reference_note, created_at
    FROM wallet_transactions
    WHERE user_id = ?
    ORDER BY wallet_txn_id DESC
    ";

    $walletStmt = $conn->prepare($walletSql);
    if ($walletStmt) {
        $walletStmt->bind_param('i', $userId);
        $walletStmt->execute();
        $walletRes = $walletStmt->get_result();

        while ($txn = $walletRes ? $walletRes->fetch_assoc() : null) {
            if (!$txn) {
                break;
            }

            $txnType = strtolower((string)($txn['txn_type'] ?? 'adjustment'));
            $amount = (float)($txn['amount'] ?? 0);
            $rows[] = [
                'id' => 'wallet-' . (int)$txn['wallet_txn_id'],
                'type' => $txnType === 'refund' ? 'refund' : $txnType,
                'tone' => $txnType === 'refund' ? 'success' : 'warning',
                'reference' => 'Wallet Txn #' . (int)$txn['wallet_txn_id'],
                'title' => ucfirst(str_replace('_', ' ', $txnType)),
                'description' => (string)($txn['reference_note'] ?? 'Wallet transaction'),
                'method' => 'Wallet ledger',
                'status' => 'Recorded',
                'amount' => $amount,
                'time' => $txn['created_at'],
                'sort_time' => $txn['created_at']
            ];
        }

        $walletStmt->close();
    }
}

usort($rows, function ($a, $b) {
    return strtotime((string)($b['sort_time'] ?? '')) <=> strtotime((string)($a['sort_time'] ?? ''));
});

send_response(true, 'Transactions loaded.', 200, [
    'transactions' => $rows
]);
?>
