<?php

function tmdb_fetch($name, $year, $apiKey) {

    // ---- Worker Endpoint (FAST, CACHED TMDB PROXY) ----
    $W = "https://video.manojwrites.xyz/tmdb?endpoint=";

    // ---- Helper Functions ---------------------------------------------------------
    $normalize = function($s) {
        $s = trim(mb_strtolower($s, 'UTF-8'));
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    };

    $title_similarity = function($a, $b) use ($normalize) {
        $a = $normalize($a);
        $b = $normalize($b);
        if ($a === '' || $b === '') return 0;
        similar_text($a, $b, $perc);
        return $perc / 100; 
    };

    $year_score = function($candYear, $dbYear) {
        if (!$candYear || !$dbYear) return 0;
        $c = intval(substr($candYear,0,4));
        $d = intval($dbYear);

        if ($c === $d) return 1.0;
        if (abs($c - $d) === 1) return 0.7;
        if (abs($c - $d) <= 3) return 0.4;
        return 0.0;
    };

    // ---------------------------------------------------------------------------
    // STEP 1 — SEARCH MOVIE + TV (via Cloudflare Worker)
    // ---------------------------------------------------------------------------
    $candidates = [];

    // MOVIE search
    $urlM = $W . "search/movie&query=" . urlencode($name);
    if (!empty($year)) $urlM .= "&year=" . urlencode($year);
    $resM = json_decode(@file_get_contents($urlM), true);

    if (!empty($resM['results'])) {
        foreach ($resM['results'] as $r) {
            $r['_type'] = 'movie';
            $candidates[] = $r;
        }
    }

    // TV search
    $urlT = $W . "search/tv&query=" . urlencode($name);
    $resT = json_decode(@file_get_contents($urlT), true);

    if (!empty($resT['results'])) {
        foreach ($resT['results'] as $r) {
            $r['_type'] = 'tv';
            $candidates[] = $r;
        }
    }

    // NOTHING FOUND → fallback movie search
    if (empty($candidates)) {
        $fallback = json_decode(@file_get_contents(
            $W . "search/movie&query=" . urlencode($name)
        ), true);

        if (empty($fallback['results'])) return false;

        $chosen = $fallback['results'][0];
        $id = $chosen['id'];
        $type = "movie";
    }

    // ---------------------------------------------------------------------------
    // STEP 2 — SCORE ALL CANDIDATES (maximum accuracy)
    // ---------------------------------------------------------------------------
    $best = null;
    $bestScore = -1;
    $dbName = $name;

    foreach ($candidates as $cand) {

        $candTitle = $cand['title'] ?? $cand['name'] ?? '';
        $candYear  = $cand['release_date'] ?? $cand['first_air_date'] ?? '';

        $tScore = $title_similarity($candTitle, $dbName);
        $yScore = $year_score($candYear, $year);

        $popScore  = min(1.0, floatval($cand['popularity'] ?? 0) / 100.0);
        $votes     = intval($cand['vote_count'] ?? 0);
        $voteScore = min(1.0, log($votes + 1) / 6.0);

        $score = ($tScore * 0.60) + ($yScore * 0.25) + ($popScore * 0.10) + ($voteScore * 0.05);

        if (mb_strtolower($candTitle) === mb_strtolower($dbName)) {
            $score += 0.05;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $cand;
        }
    }

    $THRESHOLD = 0.50;

    if ($bestScore >= $THRESHOLD) {
        $id = $best['id'];
        $type = $best['_type'];
    } else {
        $fallback = null;
        foreach ($candidates as $c) if ($c['_type'] === 'movie') { $fallback = $c; break; }
        if (!$fallback) $fallback = $candidates[0];

        $id = $fallback['id'];
        $type = $fallback['_type'];
    }
    
    // ---------------------------------------------------------------------------
    // STEP 3 — FETCH FULL DETAILS (via Worker cached TMDB)
    // ---------------------------------------------------------------------------
    
    $detailsUrl = $W . "$type/$id?append_to_response=credits,keywords,videos";
    
    $d = json_decode(file_get_contents($detailsUrl), true);

    $data = [];
    $data["id"] = $id;
    $data["type"] = $type;

    // Poster
    $data["poster"] = "https://image.tmdb.org/t/p/w1280" . ($d["poster_path"] ?? "");

    // Rating
    $data["rating"] = $d["vote_average"] ?? 0;

    // Overview
    $data["overview"] = htmlspecialchars_decode($d["overview"] ?? "", ENT_QUOTES);

    // Genre
    $data["genre"] = implode(", ", array_column($d["genres"] ?? [], "name"));

    // ====== MOVIE ======
    if ($type === "movie") {

        $data["year"] = substr($d["release_date"] ?? "", 0, 4);

        $min = intval($d["runtime"] ?? 0);
        $data["duration"] = floor($min/60) . "h " . ($min%60) . "m";

        $dir = array_filter($d["credits"]["crew"] ?? [], fn($c) => $c["job"] === "Director");
        $data["director"] = $dir ? array_values($dir)[0]["name"] : "";

        $kw = $d["keywords"]["keywords"] ?? [];

    } 

    // ====== TV SHOW (mapped for movie compatibility) ======
    else {

        $data["year"] = substr($d["first_air_date"] ?? "", 0, 4);

        $rt = $d["episode_run_time"][0] ?? 0;
        $data["duration"] = $rt . "m";

        $data["director"] = "Various";

        $kw = $d["keywords"]["results"] ?? [];
    }

    // Tags
    $data["tags"] = implode(", ", array_slice(array_column($kw, "name"), 0, 10));

    // Cast
    $castList = $d["credits"]["cast"] ?? [];
$castList = array_slice($castList, 0, 12); // limit to 12 items

$castTextParts = [];
$castFull = [];

foreach ($castList as $c) {

    // ---- Cast Text ----
    if (!empty($c["name"])) {
        $castTextParts[] = $c["name"];
    }

    // ---- Cast Image ----
    $img = "";
    if (!empty($c["profile_path"]) && $c["profile_path"] !== "null") {
        $img = "https://image.tmdb.org/t/p/w185" . $c["profile_path"];
    }

    // ---- Cast Full Array ----
    $castFull[] = [
        "name"      => $c["name"] ?? "",
        "character" => $c["character"] ?? "",
        "img"       => $img
    ];
}

// Build clean cast text string
$data["cast_text"] = implode(", ", $castTextParts);

// Cast full with images
$data["cast_full"] = $castFull;
    
    

    return $data;
}


?>