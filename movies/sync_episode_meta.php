<?php
    error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once("../config/connection.php");

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

// PRIVATE SYNC AUTH
/*$key = $_GET['key'] ?? '';
if (!hash_equals(SYNC_SECRET, $key)) {
    http_response_code(403);
    exit("Forbidden");
}*/


$slug = $_REQUEST['slug'] ?? '';
if (!$slug) die("Missing slug");

// 1. Get series + tmdb_id
$s = $con->prepare("
    SELECT s.id, s.tmdb_id
    FROM series s
    WHERE s.slug = ?
");
$s->bind_param("s", $slug);
$s->execute();
$series = $s->get_result()->fetch_assoc();
$s->close();

if (!$series || !$series['tmdb_id']) {
    die("TMDB ID missing for series");
}

$task = $_REQUEST['task'] ?? 'sync_episode_meta';
$seriesId = (int)$series['id'];
$tmdbId   = (int)$series['tmdb_id'];

echo "Task: {$task}\n";
echo "Series: {$slug}\n\n";
flush();
echo "Series loaded: {$slug} (TMDB {$tmdbId})\n";
flush();

$W = "https://video.manojwrites.xyz/tmdb?endpoint=";

// 2. Get seasons
$seasons = $con->query("
    SELECT id, season_number
    FROM seasons
    WHERE series_id = $seriesId
");

while ($season = $seasons->fetch_assoc()) {

    echo "Scanning season {$season['season_number']}...\n";
    flush();
    
    $seasonId  = (int)$season['id'];
    $seasonNum = (int)$season['season_number'];

    $json = @file_get_contents(
        $W . "tv/$tmdbId/season/$seasonNum"
    );
    if (!$json) {
    echo "  ✖ TMDB fetch failed for season {$seasonNum}\n";
    flush();
    continue;
    }

    $data = json_decode($json, true);
    if (empty($data['episodes'])) {
    echo "  ✖ No episodes found in TMDB\n";
    flush();
    continue;
    }

    foreach ($data['episodes'] as $ep) {
        
        echo "    ├── Episode {$ep['episode_number']} meta syncing...\n";
        flush();

        $epNum   = (int)$ep['episode_number'];
        $title   = $ep['name'] ?? null;
        $runtime = $ep['runtime'] ?? null;
        $runtime = is_numeric($runtime) ? (int)$runtime : null;
        $still = null;
        if (!empty($ep['still_path'])) {
        $still = "https://image.tmdb.org/t/p/w300".$ep['still_path'];
        }

        $u = $con->prepare("
            UPDATE episodes
            SET title = ?, duration = ?, still= ? 
            WHERE season_id = ? AND episode_number = ?
        ");
        $u->bind_param(
            "sisii",
            $title,
            $runtime,
            $still,
            $seasonId,
            $epNum
        );
        $u->execute();
        $u->close();
        echo "    ├─ Episode {$epNum} updated\n";
        flush();
    }
}

echo "Episode meta sync completed successfully\n";
flush();