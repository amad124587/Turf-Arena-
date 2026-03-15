<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

$userId = (int)($_POST['user_id'] ?? 0);
$bookingId = (int)($_POST['booking_id'] ?? 0);
$turfId = (int)($_POST['turf_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($userId <= 0 || $bookingId <= 0 || $turfId <= 0 || $rating < 1 || $rating > 5) {
    send_response(false, 'Invalid review data.', 422);
}

if (!table_exists($conn, 'reviews')) {
    $createSql = "CREATE TABLE IF NOT EXISTS reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        booking_id INT NOT NULL UNIQUE,
        turf_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($createSql)) {
        send_response(false, 'Could not prepare review storage.', 500);
    }
}

$bookingSql = "
SELECT b.booking_id, b.user_id, b.booking_status, s.turf_id, t.turf_name
FROM bookings b
JOIN slots s ON b.slot_id = s.slot_id
JOIN turfs t ON s.turf_id = t.turf_id
WHERE b.booking_id = ? AND b.user_id = ?
LIMIT 1
";
$bookingStmt = $conn->prepare($bookingSql);
if (!$bookingStmt) {
    send_response(false, 'Could not verify booking.', 500);
}

$bookingStmt->bind_param('ii', $bookingId, $userId);
$bookingStmt->execute();
$bookingRes = $bookingStmt->get_result();
$booking = $bookingRes ? $bookingRes->fetch_assoc() : null;
$bookingStmt->close();

if (!$booking) {
    send_response(false, 'Booking not found for this user.', 404);
}

if ((int)($booking['turf_id'] ?? 0) != $turfId) {
    send_response(false, 'Review turf mismatch.', 422);
}

$status = strtolower((string)($booking['booking_status'] ?? ''));
if ($status !== 'completed') {
    send_response(false, 'You can review only completed bookings.', 422);
}

$duplicateStmt = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? LIMIT 1");
if (!$duplicateStmt) {
    send_response(false, 'Could not validate previous review.', 500);
}

$duplicateStmt->bind_param('i', $bookingId);
$duplicateStmt->execute();
$duplicateRes = $duplicateStmt->get_result();
$alreadyReviewed = $duplicateRes && $duplicateRes->num_rows > 0;
$duplicateStmt->close();

if ($alreadyReviewed) {
    send_response(false, 'This booking is already reviewed.', 409);
}

$insertStmt = $conn->prepare("INSERT INTO reviews (user_id, booking_id, turf_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$insertStmt) {
    send_response(false, 'Could not save review.', 500);
}

$insertStmt->bind_param('iiiis', $userId, $bookingId, $turfId, $rating, $comment);
$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    send_response(false, 'Failed to submit review.', 500);
}

send_response(true, 'Review submitted. Review points added to your dashboard.', 200, [
    'review' => [
        'booking_id' => $bookingId,
        'turf_name' => $booking['turf_name'] ?? 'Turf',
        'rating' => $rating
    ]
]);
?>
