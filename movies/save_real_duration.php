<?php
require("../config/connection.php");

$movie_id = intval($_POST['movie_id'] ?? 0);
$duration = intval($_POST['real_duration'] ?? 0);

if ($movie_id > 0 && $duration > 0) {
    $stmt = $con->prepare("UPDATE movie SET real_duration = ? WHERE id = ?");
    $stmt->bind_param("ii", $duration, $movie_id);
    $stmt->execute();
}

echo "OK";
?>