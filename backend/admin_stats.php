<?php
header("Content-Type: application/json");
include "db_connection.php";

$stats = [];

$stats['users'] = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$stats['owners'] = $conn->query("SELECT COUNT(*) AS c FROM turf_owners")->fetch_assoc()['c'];
$stats['bookings'] = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$stats['refunds'] = $conn->query("SELECT COUNT(*) AS c FROM refund_requests WHERE status='pending'")->fetch_assoc()['c'];

echo json_encode([
  "success" => true,
  "stats" => $stats
]);