<?php
$host = "byfmwzkfsnddf53znklo-mysql.services.clever-cloud.com";  // MySQL Host Name
$user = "urj4gm5ldodmfe3p";             // MySQL User Name
$pass = "X1EbFa5zIgJdkpvz9eaO";     // Use your vPanel Password here
$db   = "byfmwzkfsnddf53znklo"; // MySQL DB Name
$port="3306";



$conn = new mysqli($host, $user, $pass, $db,$port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

