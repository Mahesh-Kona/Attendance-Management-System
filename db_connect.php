<?php
$host = getenv("DB_HOST");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$port = getenv("DB_PORT");
$conn = new mysqli($host, $user, $pass, $db, $port);


if($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
?>
