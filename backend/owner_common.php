<?php
require_once 'db_connection.php';

function owner_send_json_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

function owner_table_exists($conn, $table) {
    $name = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$name'");
    return $res && $res->num_rows > 0;
}

function owner_column_exists($conn, $table, $column) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $safeColumn = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && $res->num_rows > 0;
}

function owner_read_request_data() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    $data = $_POST;
    if (!is_array($data) || empty($data)) {
        parse_str(file_get_contents('php://input'), $data);
    }

    return is_array($data) ? $data : [];
}

function owner_exists($conn, $ownerId) {
    if ($ownerId <= 0 || !owner_table_exists($conn, 'turf_owners')) {
        return false;
    }

    $stmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row && isset($row['owner_id']);
}

function owner_award_confirm_points($conn, $bookingId) {
    $bookingId = (int)$bookingId;
    if ($bookingId <= 0) {
        return ['ok' => false, 'points' => 0, 'reason' => 'Invalid booking id'];
    }

    $stmt = $conn->prepare('SELECT user_id, booked_price, booking_status FROM bookings WHERE booking_id = ? LIMIT 1');
    if (!$stmt) {
        return ['ok' => false, 'points' => 0, 'reason' => 'Failed to load booking'];
    }

    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return ['ok' => false, 'points' => 0, 'reason' => 'Booking not found'];
    }

    $status = strtolower((string)($row['booking_status'] ?? ''));
    if ($status !== 'confirmed' && $status !== 'completed') {
        return ['ok' => false, 'points' => 0, 'reason' => 'Booking is not confirmed'];
    }

    $userId = (int)($row['user_id'] ?? 0);
    $bookedPrice = (float)($row['booked_price'] ?? 0);
    $points = (int)floor($bookedPrice / 100);

    if ($userId <= 0 || $points <= 0) {
        return ['ok' => true, 'points' => 0, 'reason' => 'No points applicable'];
    }

    $note = 'booking_confirm:' . $bookingId;
    $alreadyLogged = false;

    if (owner_table_exists($conn, 'user_point_logs') && owner_column_exists($conn, 'user_point_logs', 'user_id') && owner_column_exists($conn, 'user_point_logs', 'note')) {
        $checkStmt = $conn->prepare('SELECT log_id FROM user_point_logs WHERE user_id = ? AND note = ? LIMIT 1');
        if ($checkStmt) {
            $checkStmt->bind_param('is', $userId, $note);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            $alreadyLogged = $checkRes && $checkRes->num_rows > 0;
            $checkStmt->close();
        }
    }

    if ($alreadyLogged) {
        return ['ok' => true, 'points' => 0, 'reason' => 'Points already awarded'];
    }

    if (owner_table_exists($conn, 'user_points') && owner_column_exists($conn, 'user_points', 'user_id')) {
        if (owner_column_exists($conn, 'user_points', 'total_points') && owner_column_exists($conn, 'user_points', 'booking_points')) {
            $upsert = "INSERT INTO user_points (user_id, total_points, booking_points)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE
                         total_points = total_points + VALUES(total_points),
                         booking_points = booking_points + VALUES(booking_points)";
            $upsertStmt = $conn->prepare($upsert);
            if ($upsertStmt) {
                $upsertStmt->bind_param('iii', $userId, $points, $points);
                $upsertStmt->execute();
                $upsertStmt->close();
            }
        } elseif (owner_column_exists($conn, 'user_points', 'points')) {
            $upsert = "INSERT INTO user_points (user_id, points)
                       VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE points = points + VALUES(points)";
            $upsertStmt = $conn->prepare($upsert);
            if ($upsertStmt) {
                $upsertStmt->bind_param('ii', $userId, $points);
                $upsertStmt->execute();
                $upsertStmt->close();
            }
        }
    }

    if (owner_table_exists($conn, 'user_point_logs') && owner_column_exists($conn, 'user_point_logs', 'user_id')) {
        if (owner_column_exists($conn, 'user_point_logs', 'source') && owner_column_exists($conn, 'user_point_logs', 'points')) {
            $source = 'booking';
            if (owner_column_exists($conn, 'user_point_logs', 'note')) {
                $logSql = 'INSERT INTO user_point_logs (user_id, source, points, note) VALUES (?, ?, ?, ?)';
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('isis', $userId, $source, $points, $note);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
        } elseif (owner_column_exists($conn, 'user_point_logs', 'action') && owner_column_exists($conn, 'user_point_logs', 'points_change')) {
            $action = 'booking';
            if (owner_column_exists($conn, 'user_point_logs', 'note')) {
                $logSql = 'INSERT INTO user_point_logs (user_id, action, points_change, note) VALUES (?, ?, ?, ?)';
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('isis', $userId, $action, $points, $note);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
        }
    }

    return ['ok' => true, 'points' => $points, 'reason' => 'Points awarded'];
}
?>
