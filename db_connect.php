<?php
// $host = "sql102.infinityfree.com";  // MySQL Host Name
// $user = "if0_39931739";             // MySQL User Name
// $pass = "rguktn210163";     // Use your vPanel Password here
// $db   = "if0_39931739_attendance_management_system"; // MySQL DB Name

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
