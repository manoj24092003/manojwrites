
<?php
require "./config/connection.php";
header("Content-Type: application/json");

$q = trim($_GET['q'] ?? '');
if ($q === "") { echo json_encode([]); exit; }

$qLower = mb_strtolower($q);

// detect numbers (year / imdb)
$isNumber = ctype_digit($q);
$isFloat  = is_numeric($q);

// Base SQL (no filters applied here)
$sql = "SELECT 
        id, name, genre, imdb, duration, year, slug, description
        FROM movie
        LIMIT 200";

$res = $con->query($sql);

$out = [];

// Fuzzy helper
function fuzzy($text, $q) {
    $t = mb_strtolower($text);
    $q = mb_strtolower($q);

    if (strpos($t, $q) !== false) return true;
    foreach (explode(" ", $t) as $w) {
        if (levenshtein($w, $q) <= 2) return true;
    }
    return false;
}

while ($row = $res->fetch_assoc()) {

    $match = false;

    // text-based fuzzy
    if (
        fuzzy($row['name'], $qLower) ||
        fuzzy($row['genre'], $qLower) ||
        fuzzy($row['description'], $qLower) ||
        fuzzy($row['cast'], $qLower ?? '')
    ) { 
        $match = true; 
    }

    // year search (typo tolerant)
    if ($isNumber && levenshtein($row['year'], $q) <= 1) {
        $match = true;
    }

    // IMDb search (e.g. "8.1" or "81")
    if ($isFloat) {
        $imdbClean = str_replace(".", "", $row['imdb']);
        $qClean    = str_replace(".", "", $q);

        if (levenshtein($imdbClean, $qClean) <= 1) {
            $match = true;
        }
    }

    if ($match) $out[] = $row;
}

echo json_encode($out);
?>
