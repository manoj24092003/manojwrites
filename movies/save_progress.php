<?php
require("../config/connection.php");

// READ RAW JSON BODY
$input = json_decode(file_get_contents("php://input"), true);

$device_id = $input['device_id'] ?? '';
$movie_id  = intval($input['movie_id'] ?? 0);
$slug      = $input['slug'] ?? '';
$progress  = intval($input['progress'] ?? 0);

if (!$device_id || !$movie_id) {
    http_response_code(400);
    exit("Invalid");
}

// Get real duration
$check = $con->prepare("SELECT real_duration FROM movie WHERE id=?");
$check->bind_param("i", $movie_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$duration = $row['real_duration'] ?? 0;

// Auto remove continue watching if >95% watched
if ($duration > 0 && $progress >= ($duration * 0.95)) {
    $del = $con->prepare("DELETE FROM continue_watching WHERE device_id=? AND movie_id=?");
    $del->bind_param("si", $device_id, $movie_id);
    $del->execute();
    echo "removed";
    exit;
}

// Insert or update progress
$sql = "INSERT INTO continue_watching (device_id, movie_id, slug, progress)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE progress = VALUES(progress), updated_at = NOW()";

$stmt = $con->prepare($sql);
$stmt->bind_param("sisi", $device_id, $movie_id, $slug, $progress);
$stmt->execute();

echo "ok";
?>