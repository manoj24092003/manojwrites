<?php
session_start();
require("config/connection.php");

if (!isset($_SESSION['anon_id'])) {
    http_response_code(403);
    exit("No ID");
}

$data = json_decode(file_get_contents("php://input"), true);
$feedback = trim($data['feedback'] ?? '');

if ($feedback == '') {
    exit("Empty");
}

// Prepare secure insert
$stmt = $con->prepare("
    INSERT INTO feedback (anon_id, message, created_at)
    VALUES (?, ?, NOW())
");

$stmt->bind_param("ss", $_SESSION['anon_id'], $feedback);
$stmt->execute();

echo "OK";
?>