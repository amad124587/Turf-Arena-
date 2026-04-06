<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'turf_booking_system';
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_init();
    if (!$conn) {
        throw new Exception('Failed to initialize MySQL connection.');
    }

    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    mysqli_real_connect($conn, $host, $username, $password, $database, $port);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 'Database connection failed.',
        'error' => $e->getMessage()
    ]);
    exit();
}
?>
