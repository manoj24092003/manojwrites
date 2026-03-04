<?php
$url = $_GET['url'] ?? '';
if (!$url) exit('Missing URL');

$url = urldecode($url);

// forward headers
header("Content-Type: video/mp4");
header("Content-Disposition: inline");
header("Accept-Ranges: bytes");

// Use curl passthrough (works on all hosts)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_exec($ch);
curl_close($ch);
?>