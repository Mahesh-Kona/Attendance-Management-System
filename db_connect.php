<?php
$host = "sql102.infinityfree.com";  // MySQL Host Name
$user = "if0_39931739";             // MySQL User Name
$pass = "rguktn210163";     // Use your vPanel Password here
$db   = "if0_39931739_attendance_management_system"; // MySQL DB Name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
