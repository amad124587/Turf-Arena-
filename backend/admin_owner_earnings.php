<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'admin_common.php';

$adminId = intval($_GET['admin_id'] ?? 0);
if (!admin_exists($conn, $adminId)) {
    send_json_response(false, 'Admin access denied.', 403);
}

$owners = [];

$liveBookingStatuses = "'pending','confirmed','completed','resell_listed','resold'";
$selectedMonth = trim((string)($_GET['month'] ?? ''));
$monthStart = '';
$monthEnd = '';

if ($selectedMonth !== '') {
    if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
        send_json_response(false, 'Invalid month filter.', 400);
    }
    $monthStart = $selectedMonth . '-01 00:00:00';
    $monthEnd = date('Y-m-d H:i:s', strtotime($monthStart . ' +1 month'));
}

$bookingMonthFilter = '';
$cancelMonthFilter = '';
if ($monthStart !== '' && $monthEnd !== '') {
    $safeMonthStart = $conn->real_escape_string($monthStart);
    $safeMonthEnd = $conn->real_escape_string($monthEnd);
    $bookingMonthFilter = " AND b.created_at >= '$safeMonthStart' AND b.created_at < '$safeMonthEnd'";
    $cancelMonthFilter = " AND r.updated_at >= '$safeMonthStart' AND r.updated_at < '$safeMonthEnd'";
}

$ownerSql = "SELECT
               o.owner_id,
               o.owner_name,
               o.email,
               o.status,
               COUNT(DISTINCT t.turf_id) AS total_turfs,
               COALESCE(SUM(CASE
                 WHEN b.booking_status IN ($liveBookingStatuses)$bookingMonthFilter
                 THEN b.booked_price ELSE 0 END), 0) AS booking_income_total,
               COALESCE(SUM(CASE
                 WHEN r.status = 'paid'$cancelMonthFilter
                 THEN (b.booked_price - COALESCE(r.requested_amount, 0)) ELSE 0 END), 0) AS cancellation_earnings_total
             FROM turf_owners o
             LEFT JOIN turfs t ON t.owner_id = o.owner_id
             LEFT JOIN slots s ON s.turf_id = t.turf_id
             LEFT JOIN bookings b ON b.slot_id = s.slot_id
             LEFT JOIN refund_requests r ON r.booking_id = b.booking_id
             GROUP BY o.owner_id, o.owner_name, o.email, o.status
             ORDER BY o.owner_name ASC, o.owner_id ASC";

$ownerRes = $conn->query($ownerSql);
while ($row = $ownerRes ? $ownerRes->fetch_assoc() : null) {
    if (!$row) break;

    $bookingIncome = (float)($row['booking_income_total'] ?? 0);
    $cancellationEarnings = (float)($row['cancellation_earnings_total'] ?? 0);

    $owners[] = [
        'owner_id' => (int)$row['owner_id'],
        'owner_name' => $row['owner_name'],
        'email' => $row['email'],
        'status' => $row['status'],
        'total_turfs' => (int)($row['total_turfs'] ?? 0),
        'booking_income_total' => $bookingIncome,
        'cancellation_earnings_total' => $cancellationEarnings,
        'wallet_balance' => $bookingIncome + $cancellationEarnings
    ];
}

$selectedOwnerId = intval($_GET['owner_id'] ?? 0);
$ownerDetails = null;

if ($selectedOwnerId > 0) {
    $ownerInfoStmt = $conn->prepare('SELECT owner_id, owner_name, email, status FROM turf_owners WHERE owner_id = ? LIMIT 1');
    if (!$ownerInfoStmt) {
        send_json_response(false, 'Server error while loading owner details.', 500);
    }

    $ownerInfoStmt->bind_param('i', $selectedOwnerId);
    $ownerInfoStmt->execute();
    $ownerInfoRes = $ownerInfoStmt->get_result();
    $ownerInfo = $ownerInfoRes ? $ownerInfoRes->fetch_assoc() : null;
    $ownerInfoStmt->close();

    if ($ownerInfo) {
        $turfItems = [];

        $turfSql = "SELECT
                      t.turf_id,
                      t.turf_name,
                      t.city,
                      t.area,
                      t.status,
                      COALESCE(SUM(CASE
                        WHEN b.booking_status IN ($liveBookingStatuses)$bookingMonthFilter
                        THEN b.booked_price ELSE 0 END), 0) AS booking_income,
                      COALESCE(SUM(CASE
                        WHEN r.status = 'paid'$cancelMonthFilter
                        THEN (b.booked_price - COALESCE(r.requested_amount, 0)) ELSE 0 END), 0) AS cancellation_earnings,
                      COUNT(DISTINCT CASE
                        WHEN b.booking_status IN ($liveBookingStatuses)$bookingMonthFilter
                        THEN b.booking_id END) AS active_booking_count,
                      COUNT(DISTINCT CASE
                        WHEN r.status = 'paid'$cancelMonthFilter
                        THEN r.refund_id END) AS paid_cancellation_count
                    FROM turfs t
                    LEFT JOIN slots s ON s.turf_id = t.turf_id
                    LEFT JOIN bookings b ON b.slot_id = s.slot_id
                    LEFT JOIN refund_requests r ON r.booking_id = b.booking_id
                    WHERE t.owner_id = ?
                    GROUP BY t.turf_id, t.turf_name, t.city, t.area, t.status
                    ORDER BY t.turf_name ASC, t.turf_id ASC";
        $turfStmt = $conn->prepare($turfSql);
        if (!$turfStmt) {
            send_json_response(false, 'Server error while loading turf earnings.', 500);
        }

        $turfStmt->bind_param('i', $selectedOwnerId);
        $turfStmt->execute();
        $turfRes = $turfStmt->get_result();
        while ($row = $turfRes ? $turfRes->fetch_assoc() : null) {
            if (!$row) break;

            $bookingIncome = (float)($row['booking_income'] ?? 0);
            $cancellationEarnings = (float)($row['cancellation_earnings'] ?? 0);

            $turfItems[] = [
                'turf_id' => (int)$row['turf_id'],
                'turf_name' => $row['turf_name'],
                'city' => $row['city'],
                'area' => $row['area'],
                'status' => $row['status'],
                'booking_income' => $bookingIncome,
                'cancellation_earnings' => $cancellationEarnings,
                'wallet_balance' => $bookingIncome + $cancellationEarnings,
                'active_booking_count' => (int)($row['active_booking_count'] ?? 0),
                'paid_cancellation_count' => (int)($row['paid_cancellation_count'] ?? 0)
            ];
        }
        $turfStmt->close();

        $ownerDetails = [
            'owner_id' => (int)$ownerInfo['owner_id'],
            'owner_name' => $ownerInfo['owner_name'],
            'email' => $ownerInfo['email'],
            'status' => $ownerInfo['status'],
            'turfs' => $turfItems
        ];
    }
}

send_json_response(true, 'Owner earnings loaded.', 200, [
    'owners' => $owners,
    'owner_details' => $ownerDetails,
    'selected_month' => $selectedMonth
]);
?>
