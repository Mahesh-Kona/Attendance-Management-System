<?php
$host = "byfmwzkfsnddf53znklo-mysql.services.clever-cloud.com";
$db   = "byfmwzkfsnddf53znklo";
$user = "urj4gm5ldodmfe3p";
$pass = "XlEbFaSzIgJdkpvz9ea0";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully to Clever Cloud MySQL!";
?>
