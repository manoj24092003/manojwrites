<?php
$id = $_GET["id"] ?? null;
if (!$id) die("Movie ID missing");

$_GET["id"] = $id;   // Pass ID to next file

include "send_notification.php";
?>