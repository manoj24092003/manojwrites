<?php
require("../config/connection.php");
ob_clean();
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



error_reporting(E_ALL);
ini_set('display_errors', 1);

$slug = $_REQUEST['slug'] ?? '';
if (!$slug) die("Missing slug");

// ======================================================
// 1️⃣ ENSURE SERIES EXISTS
// ======================================================
$stmt = $con->prepare("SELECT id FROM series WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $i = $con->prepare("INSERT INTO series (slug, title) VALUES (?, ?)");
    $i->bind_param("ss", $slug, $slug);
    $i->execute();
    $seriesId = $i->insert_id;
    $i->close();
} else {
    $seriesId = (int)$row['id'];
}

$task = $_REQUEST['task'] ?? 'sync_series';
echo "Task: {$task}\n";
echo "Series: {$slug}\n\n";
flush();
echo "Series ready: {$slug} (ID {$seriesId})\n";
flush();

// ======================================================
// 2️⃣ SCAN R2 (SEASON → EPISODE)
// Cloudflare R2 DOES NOT list virtual folders
// ======================================================
$maxSeasons = 10; // safe upper bound

for ($seasonNum = 1; $seasonNum <= $maxSeasons; $seasonNum++) {

    //echo "Scanning season {$seasonNum}...\n";
    //flush();
    
    $seasonPrefix = "{$slug}/season-{$seasonNum}/";
    $seasonUrl = "https://video.manojwrites.xyz/list?prefix={$seasonPrefix}";

    $seasonData = json_decode(@file_get_contents($seasonUrl), true);


     //If season folder doesn't exist → skip
    if (empty($seasonData['objects'])) {
        //echo "  ✖ Season {$seasonNum} not found, skipping\n";
       // flush();
       //continue;
        break;
  }
   echo "Scanning season {$seasonNum}...\n";
   flush();

    // ==================================================
    // INSERT SEASON    // INSERT SEASON

    // ==================================================
    $s = $con->prepare("
        INSERT IGNORE INTO seasons (series_id, season_number)
        VALUES (?, ?)
    ");
   
    $s->bind_param("ii", $seriesId, $seasonNum);
    $s->execute();
    $s->close();
    echo "  ✔ Season {$seasonNum} detected & saved\n";
    flush();

    // Get season_id
    $q = $con->prepare("
        SELECT id FROM seasons 
        WHERE series_id = ? AND season_number = ?
    ");
    $q->bind_param("ii", $seriesId, $seasonNum);
    $q->execute();
    $seasonId = (int)$q->get_result()->fetch_assoc()['id'];
    $q->close();

    // ==================================================
    // SCAN EPISODES
    // ==================================================
    $seenEpisodes = [];
    foreach ($seasonData['objects'] as $obj) {

        if (preg_match('/episode-(\d+)/', $obj, $m)) {
            //echo "    ├─ Episode {$episodeNum} found\n";
            //flush();
            $episodeNum = (int)$m[1];
            if (isset($seenEpisodes[$episodeNum])) continue;
            $seenEpisodes[$episodeNum] = true;
            echo "    ├─ Episode {$episodeNum} found\n";
            flush();
            $ep = $con->prepare("
                INSERT IGNORE INTO episodes (season_id, episode_number)
                VALUES (?, ?)
            ");
            
            $ep->bind_param("ii", $seasonId, $episodeNum);
            $ep->execute();
            $ep->close();
        }
    }
}

echo "Series sync completed successfully\n";
flush();