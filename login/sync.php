<?php
require_once("../config/connection.php");

//@ini_set('output_buffering', 'off');
//@ini_set('zlib.output_compression', false);
//while (ob_get_level()) ob_end_flush();
//ob_implicit_flush(true);

$task = $_POST['task'] ?? '';
$slug = $_POST['slug'] ?? '';
$key  = $_POST['key']  ?? '';

if (!hash_equals(SYNC_SECRET, $key)) {
    echo "Forbidden\n";
    exit;
}

if (!$task || !$slug) {
    echo "Invalid request\n";
    exit;
}

ob_end_clean();
ob_start();
//echo "Task: $task\n";
//echo "Series: $slug\n";
//flush();

if ($task === 'series') {
    unset($seasons, $series, $series_id, $stmt, $rows);
    require __DIR__ . '/../movies/sync_series.php';
}
elseif ($task === 'episode') {
    require __DIR__ . '/../movies/sync_episode_meta.php';
}
else {
    echo "Unknown task\n";
}

echo "\nDONE\n";
flush();