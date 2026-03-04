<?php

require("../config/connection.php");
require_once __DIR__ . "/tmdb.php";

// =====================================
// AUTH
// =====================================
$key = $_GET['key'] ?? '';
if (!hash_equals(SYNC_SECRET, $key)) {
    http_response_code(403);
    exit("Forbidden");
}

// =====================================
// ENSURE SERIES TMDB ID
// =====================================
function ensureSeriesTmdbId(mysqli $con, string $slug) {

    $q = $con->prepare("SELECT id, tmdb_id, title FROM series WHERE slug = ?");
    $q->bind_param("s", $slug);
    $q->execute();
    $res = $q->get_result();
    $series = $res->fetch_assoc();
    $q->close();

    if (!$series) return null;

    // already exists
    if (!empty($series['tmdb_id'])) {
        return (int)$series['tmdb_id'];
    }

    // fetch from TMDB
    $tmdb = tmdb_fetch($series['title'] ?: $slug, null, "");

    if ($tmdb && $tmdb['type'] === 'tv') {
        $u = $con->prepare("UPDATE series SET tmdb_id = ? WHERE id = ?");
        $u->bind_param("ii", $tmdb['id'], $series['id']);
        $u->execute();
        $u->close();

        return (int)$tmdb['id'];
    }

    return null;
}

// =====================================
// FETCH ALL SERIES SLUGS
// =====================================
$slugs = [];

// from series table
$res = $con->query("SELECT slug FROM series");
while ($row = $res->fetch_assoc()) {
    $slugs[] = $row['slug'];
}

// also include movies that are already marked as TV
$res2 = $con->query("SELECT slug FROM movie WHERE type = 'tv'");
while ($row = $res2->fetch_assoc()) {
    if (!in_array($row['slug'], $slugs)) {
        $slugs[] = $row['slug'];
    }
}

if (empty($slugs)) {
    exit("No series found");
}

// =====================================
// SYNC EACH SERIES
// =====================================
foreach ($slugs as $slug) {

    $tmdbId = ensureSeriesTmdbId($con, $slug);
    
// 1️⃣ Sync R2 structure (seasons + episodes)
$seriesResp = file_get_contents(
    "https://manojwrites.xyz/movies/sync_series.php?slug={$slug}&key={$key}"
);

if ($seriesResp === false) {
    echo "❌ Series failed: {$slug}<br>";
    continue; // DO NOT proceed to episodes
}

    // 2️⃣ Ensure TMDB ID exists
    $tmdbId = ensureSeriesTmdbId($con, $slug);

// 3️⃣ Sync episode metadata (title + duration)
if ($tmdbId) {
    $epResp = file_get_contents(
        "https://manojwrites.xyz/movies/sync_episode_meta.php?slug={$slug}&key={$key}"
    );

    if (!$epResp) {
        echo "⚠ Episode meta failed: {$slug}<br>";
    }
}

    /*echo "✔ Synced: {$slug}<br>";*/

//==============STILL PATH=========
if ($tmdbId) {

    $API = "https://video.manojwrites.xyz/tmdb?endpoint=";
    $IMG = "https://image.tmdb.org/t/p/w300";

    $seasons = $con->prepare("
        SELECT se.id, se.season_number
        FROM seasons se
        JOIN series s ON s.id = se.series_id
        WHERE s.slug = ?
    ");
    $seasons->bind_param("s", $slug);
    $seasons->execute();
    $res = $seasons->get_result();
    $seasons->close();

    while ($se = $res->fetch_assoc()) {

        $j = @file_get_contents($API."tv/$tmdbId/season/".$se['season_number']);
        if (!$j) continue;

        foreach (json_decode($j, true)['episodes'] ?? [] as $ep) {

            if (empty($ep['still_path'])) continue;

            $still = $IMG.$ep['still_path'];

            $u = $con->prepare("
                UPDATE episodes
                SET still = ?
                WHERE season_id = ? AND episode_number = ?
            ");
            $u->bind_param("sii", $still, $se['id'], $ep['episode_number']);
            $u->execute();
            $u->close();
        }
    }
}
    echo "✔ Fully synced (series + episodes): {$slug}<br>";
}
