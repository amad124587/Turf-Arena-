<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

include 'db_connection.php';

function send_response($success, $status, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'status' => $status
    ]);
    exit();
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
    if (empty($data)) {
        parse_str(file_get_contents('php://input'), $data);
    }
}

if (!is_array($data) || empty($data)) {
    send_response(false, 'No data received.', 400);
}

$full_name = trim($data['full_name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = (string)($data['password'] ?? '');

if ($full_name === '' || $email === '' || $phone === '' || $password === '') {
    send_response(false, 'All fields are required.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_response(false, 'Invalid email address.', 400);
}

if (strlen($password) < 6) {
    send_response(false, 'Password must be at least 6 characters.', 400);
}

$checkSql = 'SELECT email FROM users WHERE email = ? LIMIT 1';
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    send_response(false, 'Server error while checking user.', 500);
}

$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    send_response(false, 'Email already registered.', 409);
}
$checkStmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$language_pref = 'en';
$status = 'active';

$sql = 'INSERT INTO users (full_name, email, phone, password_hash, language_pref, status, created_at)
VALUES (?, ?, ?, ?, ?, ?, NOW())';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while preparing registration.', 500);
}

$stmt->bind_param('ssssss', $full_name, $email, $phone, $password_hash, $language_pref, $status);

if ($stmt->execute()) {
    $stmt->close();
    send_response(true, 'User registered successfully.', 200);
}

$stmt->close();
send_response(false, 'Registration failed.', 500);
?>
