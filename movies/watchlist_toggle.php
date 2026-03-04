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
$movieId = intval($_POST['movie_id'] ?? 0);

if ($movieId <= 0) {
    echo json_encode(["status" => "error", "message" => "invalid_movie_id"]);
    exit;
}

// Check if already saved
$check = $con->prepare("SELECT id FROM watchlist WHERE user_email=? AND movie_id=?");
$check->bind_param("si", $user, $movieId);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Remove
    $row = $res->fetch_assoc();
    $del = $con->prepare("DELETE FROM watchlist WHERE id=?");
    $del->bind_param("i", $row['id']);
    $del->execute();
    echo json_encode(["status" => "removed"]);
} else {
    // Add
    $add = $con->prepare("INSERT INTO watchlist (user_email, movie_id) VALUES (?, ?)");
    $add->bind_param("si", $user, $movieId);
    $add->execute();
    echo json_encode(["status" => "added"]);
}