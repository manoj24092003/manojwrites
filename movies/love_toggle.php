<?php
ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json');
require_once "../config/connection.php";

if (!isset($_SESSION['user_email'])) {
    echo json_encode(["status" => "not_logged_in"]);
    exit;
}

$user = $_SESSION['user_email'];
$movieId = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

if ($movieId <= 0) {
    echo json_encode(["status" => "error", "message" => "invalid_movie_id"]);
    exit;
}

// Check if already loved
$check = $con->prepare("SELECT id FROM loves WHERE user_email=? AND movie_id=?");
$check->bind_param("si", $user, $movieId);
$check->execute();
$checkRes = $check->get_result();

if ($checkRes && $checkRes->num_rows > 0) {
    $row = $checkRes->fetch_assoc();
    $check->close();

    // Remove love
    $del = $con->prepare("DELETE FROM loves WHERE id = ?");
    $del->bind_param("i", $row['id']);
    $del->execute();
    $del->close();

    echo json_encode(["status" => "removed"]);
    exit;
} else {
    $check->close();

    // Add love
    $ins = $con->prepare("INSERT INTO loves (user_email, movie_id, created_at) VALUES (?, ?, NOW())");
    $ins->bind_param("si", $user, $movieId);
    $ins->execute();
    $ins->close();

    echo json_encode(["status" => "added"]);
    exit;
}