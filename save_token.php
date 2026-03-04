<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require "config/connection.php";

$token = $_POST["token"] ?? null;

if (!$token) {
    die("No token");
}

// Insert if new, ignore if already exists
$stmt = $con->prepare("
    INSERT INTO fcm_tokens (token) 
    VALUES (?) 
    ON DUPLICATE KEY UPDATE token = token
");

$stmt->bind_param("s", $token);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "ERROR: " . $stmt->error;
}

$stmt->close();
$con->close();
?>