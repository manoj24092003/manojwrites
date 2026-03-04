<?php
require "config/connection.php";

$slug = $_POST['slug'] ?? '';
$duration = intval($_POST['duration'] ?? 0);

if ($slug && $duration > 0) {
    $sql = "UPDATE movie SET real_duration = ? WHERE slug = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("is", $duration, $slug);
    $stmt->execute();
}

echo "OK";