<?php
require "config/connection.php";

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'];
$movie_id = $data['movie_id'];
$progress = $data['progress'];

$sql = "INSERT INTO continue_watching (user_id, movie_id, progress)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE progress = VALUES(progress), updated_at = NOW()";

$stmt = $con->prepare($sql);
$stmt->bind_param("iii", $user_id, $movie_id, $progress);
$stmt->execute();

echo "ok";
?>