<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "turf_booking_system";

$conn = new mysqli("localhost", "root", "", "turf_booking_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>