<?php
require "config/connection.php";

$today = date("Y-m-d");

$sql = "SELECT COUNT(*) AS total FROM visitors WHERE visit_date = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo $row['total'];
?>