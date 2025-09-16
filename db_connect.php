<?php
$mysqli = @new mysqli(
    getenv('DB_HOST'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD'),
    getenv('DB_DATABASE'),
    getenv('DB_PORT')
);

if ($mysqli->connect_errno) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("Database connection failed. Check logs for details.");
}

echo "Connected successfully!";
