<?php
require "config/connection.php";

$ip = $_SERVER['REMOTE_ADDR'];
$today = date("Y-m-d");

// Insert once per day
$sql = "INSERT IGNORE INTO visitors (ip, visit_date, visited_at)
        VALUES (?, ?, NOW())";

$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $ip, $today);
$stmt->execute();

echo "OK";
?>