<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require("../config/connection.php");

$slug = $_GET['slug'] ?? '';
if ($slug === '') die("Error: Movie not provided.");

$sql = "SELECT * FROM movie WHERE slug = ?";
$stmt = $con->prepare($sql);
if (!$stmt) die("Database error preparing statement: " . $con->error);

$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();
$stmt->close();

if (!$movie) die("Movie not found");

//======================================

require_once "./tmdb.php";   // include TMDB file
//$apiKey = "1e783946481504d1a3fbb878694af384";
$apiKey = ""; // Worker handles TMDB key – this stays unused

// Fetch from TMDB only if DB is missing details
$needsUpdate =
    empty($movie['genre']) ||
    empty($movie['cast']) ||
    empty($movie['description']) ||
    empty($movie['imdb']) ||
    empty($movie['tags']) ||
    empty($movie['duration']) ||
    empty($movie['cast_full']);

$tmdb = $needsUpdate ? tmdb_fetch($movie['name'], $movie['year'], "") : false;


// Default: load cast_full from DB if exists
$castFull = !empty($movie['cast_full'])
    ? json_decode($movie['cast_full'], true)
    : [];

// If TMDB returned cast_full, override DB value
if ($tmdb && !empty($tmdb['cast_full'])) {
    $castFull = $tmdb['cast_full'];
}

if ($tmdb) {
// Override ONLY output values
$movie['genre'] = $tmdb['genre'];
$movie['imdb'] =number_format( $tmdb['rating'],1);
$movie['duration'] = $tmdb['duration'];
$movie['director'] = $tmdb['director'];
$movie['description'] = $tmdb['overview'];

// Cast text + Cast array  
$movie['cast'] = $tmdb['cast_text'];  
$castFull = $tmdb['cast_full'];  
    
    
$movie['trailer'] = getTrailerFromTMDB($tmdb['id'], $tmdb['type']);

}

if($tmdb){
$update = $con->prepare("
UPDATE movie SET
genre = ?,
cast = ?,
imdb = ?,
duration = ?,
director = ?,
description = ?,
trailer=?,
tags=?,
cast_full=?
WHERE id = ?
");

$ratingClean = number_format($tmdb['rating'], 1);

$castJSON = json_encode($castFull, JSON_UNESCAPED_UNICODE);
$update->bind_param(
"sssssssssi",
$tmdb['genre'],
$tmdb['cast_text'],
$ratingClean,          // <-- formatted rating
$tmdb['duration'],
$tmdb['director'],
$tmdb['overview'],
$movie['trailer'],
$tmdb['tags'],
$castJSON,
$movie['id']
);
$update->execute();
$update->close();
}

// Only load from DB if we did NOT get TMDB data
if (!$tmdb && !empty($movie['cast_full'])) {
    $castFull = json_decode($movie['cast_full'], true);
}



function getTrailerFromTMDB($id, $type = "movie") {
    $W = "https://video.manojwrites.xyz/tmdb?endpoint=";

    $data = json_decode(file_get_contents(
        $W . "$type/$id/videos"
    ), true);

    if (!$data || empty($data['results'])) return "";

    foreach ($data['results'] as $v) {
        if (($v['type'] ?? "") === "Trailer" && ($v['site'] ?? "") === "YouTube") {
            return "https://www.youtube.com/embed/" . $v['key'];
        }
    }
    return "";
}

// ------------------------- SERIES DETECTION -------------------------
$isSeries = false;
$seasons = [];

$chk = $con->prepare("
    SELECT s.season_number
    FROM seasons s
    JOIN series r ON r.id = s.series_id
    WHERE r.slug = ?
    AND s.season_number IS NOT NULL
    ORDER BY s.season_number ASC
");

$chk->bind_param("s", $slug);
$chk->execute();
$res = $chk->get_result();

while ($row = $res->fetch_assoc()) {
    $isSeries = true;
    $seasons[] = (int)$row['season_number'];
}

$chk->close();
// ================= SELECTED SEASON (SAFE) =================
$selectedSeason = null;

if (!empty($seasons)) {
    $selectedSeason = isset($_GET['season']) && in_array((int)$_GET['season'], $seasons)
        ? (int)$_GET['season']
        : $seasons[0];
}

// =========TBDB========
$seriesTmdbId = null;
$q = $con->prepare("SELECT tmdb_id FROM series WHERE slug = ?");
$q->bind_param("s", $slug);
$q->execute();
$r = $q->get_result()->fetch_assoc();
if ($r && !empty($r['tmdb_id'])) {
    $seriesTmdbId = (int)$r['tmdb_id'];
}
$q->close();

?>

<!DOCTYPE html>  <html lang="en">  
<head>  
<link rel="apple-touch-icon" href="./favicon.ico" type="image/x-icon">    
<meta charset="UTF-8">    
<meta name="viewport" content="width=device-width, initial-scale=1.0">    
<meta name="theme-color" content="#000">    
<title>Manoj Writes - <?= htmlspecialchars($movie['name']); ?></title>    
<meta name="title" content="ManojWrites">    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">    
<link rel="preconnect" href="https://fonts.googleapis.com">    
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>    
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap" rel="stylesheet">    
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">    
<link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">    
<link rel="stylesheet" href="../style.css?v=7">    
<link rel="stylesheet" href="./movie.css?v=7">    
<link rel="stylesheet" href="../hero.css?v=7">  <?php if (isset($_SESSION['user_email'])): ?>  <script>window.isLoggedIn = true;</script>  <?php else: ?>  <script>window.isLoggedIn = false;</script>  <?php endif; ?>  </head>  <body class="loading" style="background:#000;";>  <header class="header">  
    <div class="logo back-redirect" onclick="location.href='../index.php'"><i class="bi bi-tv-fill tv-logo"></i></div>  
    <div class="scontainer">  
        <div class="back-container">  
            <button class="back-btn" onclick="goBack()">  
                <span class="arrow">←</span> Back  
            </button>  
        </div>  
    </div>  
</header>  <nav id="mobileMenu" class="mobile-panel" aria-hidden="true" aria-label="Mobile primary">  
    <a href="../index.php"><i class="bi bi-house"></i> HOME</a>  
    <a href="../books/index.php"><i class="bi bi-book"></i> BOOKS</a>  
    <a href="../poems/index.php"><i class="bi bi-feather"></i> POEMS</a>  
    <a href="../login/index.php"><i class="bi bi-person"></i> ADMIN</a>  
</nav>  <main class="main">  <section class="hero" style="padding:1rem;min-height: 100% !important;">  
<div>  <div class="poster-container sk">  
    <img   
    src="https://images.manojwrites.xyz/<?= htmlspecialchars($movie['slug']) ?>/poster.jpg"   
    alt="<?= htmlspecialchars($movie['name']) ?>"   
    onerror="this.src='https://images.manojwrites.xyz/default.jpg'"  
    alt="<?= htmlspecialchars($movie['name']) ?>"  
    class="poster-image"  
>  <?php

$logo = "https://images.manojwrites.xyz/" . htmlspecialchars($movie['slug']) . "/posterlogo.png";
?>
<img
src="<?= $logo ?>"
alt="<?= htmlspecialchars($movie['name']) ?>"
class="poster-logo-bottom no-lazy"
onerror="this.style.display='none'"

> 

<div id="ytPopup" class="yt-overlay" onclick="closeYT()">  
    <div class="yt-box" onclick="event.stopPropagation()" id="ytBox">  
        <div id="ytFrame"></div>  
    </div>  
</div>  

<div class="hero-bg-overlay"></div>  
<div class="hero-shadow-bottom"></div>

</div>  

<div class="watch-now-container">

<?php if (($movie['type'] ?? 'movie') === 'tv' && $selectedSeason !== null): ?>
    <a href="/movies/player/<?= urlencode($movie['slug']); ?>/season-<?= $selectedSeason ?>/episode-1/"
       class="watch-now-btn">
        <i class="bi bi-tv-fill"></i> Watch Now
    </a>
<?php else: ?>
    <a href="/movies/player/<?= urlencode($movie['slug']); ?>"
       class="watch-now-btn">
        <i class="bi bi-tv-fill"></i> Watch Now
    </a>
<?php endif; ?>
        
        
        
    </a>  <?php if (!empty($movie['trailer'])):  
    $tr = strtok($movie['trailer'], "?");  
    $tr = str_replace("youtu.be/", "www.youtube.com/embed/", $tr);  
    $tr = str_replace("watch?v=", "embed/", $tr);  
?>  <a href="#" class="watch-now-btn" style="margin-left:10px;" onclick="openTrailer('<?= $tr ?>')">  
    <i class="bi bi-film"></i> Trailer  
</a>

<?php endif; ?>  </div>  <div class="con-others" id="con-others">  
    <div class="con-element">  <?php  
$isLoved = false;  
  
if (isset($_SESSION['user_email'])) {  
    $u = $_SESSION['user_email'];  
    $mid = $movie['id'];  
  
    $q = $con->prepare("SELECT id FROM loves WHERE user_email=? AND movie_id=?");  
    $q->bind_param("si", $u, $mid);  
    $q->execute();  
    $r = $q->get_result();  
    $isLoved = $r->num_rows > 0;  
}  
?>  <div class="con <?= $isLoved ? 'active' : '' ?>"   
     id="loveBtn"   
     onclick="loveClick(<?= $movie['id'] ?>)">  
    <i class="bi bi-heart-fill"></i>  
    <span><?= $isLoved ? 'Loved' : 'Love' ?></span>  
        </div>  <div class="con" id="shareBtn" onclick="shareButtonClick(  
    '<?= htmlspecialchars($movie['name']) ?>',  
    '<?= htmlspecialchars($movie['slug']) ?>',  
     'https://images.manojwrites.xyz/<?=htmlspecialchars($movie['slug']) ?>/poster.jpg')">  
    <i class="bi bi-share-fill" style="color:#e50914;"></i>  
    <span>Share</span>  
        </div>  <?php  
      
$isWatch = false;  
  
if (isset($_SESSION['user_email'])) {  
    $u = $_SESSION['user_email'];  
    $mid = $movie['id'];  
  
    $q = $con->prepare("SELECT id FROM watchlist WHERE user_email=? AND movie_id=?");  
    $q->bind_param("si", $u, $mid);  
    $q->execute();  
    $r = $q->get_result();  
    $isWatch = $r->num_rows > 0;  
    $q->close();  
}  
?>  <div class="con <?= $isWatch ? 'active' : '' ?>"  
     id="watchBtn"  
     onclick="watchToggle(<?= $movie['id'] ?>)">  
    <i class="bi bi-bookmarks-fill"></i>  
    <span><?= $isWatch ? 'Added' : 'Watchlist' ?></span>  
        </div>  </div>  
</div>

<div style="font-size:1rem; color:#ffffffcc; padding:10px;font-weight:400;background:#1a1a1a; border-radius:12px;">  
    <strong style='font-size:1.1rem;font-weight:500; color:#fff;opacity:0.8;'><?= htmlspecialchars($movie['name']); ?> (<?= htmlspecialchars($movie['year']); ?>)</strong><br>  
    Genre: <span style="opacity:0.7;"><?= htmlspecialchars($movie['genre']); ?></span><br>  
    Cast: <span style="opacity:0.7;"><?= htmlspecialchars($movie['cast']); ?></span><br>  
    IMDb: <span style="color:yellow;"><?= htmlspecialchars($movie['imdb']); ?></span><br>  
    Duration: <span style="color:orange;"><?= htmlspecialchars($movie['duration']); ?></span><br>  
    Director: <span style="opacity:0.7;"><?= htmlspecialchars($movie['director']); ?></span><br>  <p style="color:#fff; line-height:1;font-size:0.85rem; opacity:0.8;padding:7px 7px 7px 7px"><?= nl2br($movie['description']); ?></p>  
    </div>  
    
    
<!--SERIES SECTION -->
<?php if ($isSeries): ?>
<div class="series-container">

    <h3 class="series-title">Episodes</h3>

    <div class="series-season-block">
        <label for="seasonSelect" class="season-label"></label>
  <!---------->      
<div class="series-season-block">
    <button id="seasonBtn" class="season-btn" style="font-weight:400;">
        Season <?= $selectedSeason ?>
        <span class="season-arrow"><i class="bi bi-caret-down-fill"></i></span>
    </button>
    <div id="seasonList" class="season-list">
        <?php foreach ($seasons as $s): ?>
            <div class="season-item" data-season="<?= $s ?>">
                Season <?= $s ?>
            </div>
        <?php endforeach; ?>
    </div>
        </div>
        <!---------->
    </div>

<div id="episodeList" class="series-episode-list">
<?php
if ($selectedSeason !== null):
$eps = $con->prepare("
    SELECT e.episode_number, e.title, e.duration, e.still
    FROM episodes e
    JOIN seasons s ON s.id = e.season_id
    JOIN series r ON r.id = s.series_id
    WHERE r.slug = ? AND s.season_number = ?
    ORDER BY e.episode_number ASC
");
$eps->bind_param("si", $slug, $selectedSeason);
$eps->execute();
$epResult = $eps->get_result();

while ($ep = $epResult->fetch_assoc()):
?>
    <div class="episode-box"
         onclick="location.href='/movies/player/<?= $slug ?>/season-<?= $selectedSeason ?>/episode-<?= $ep['episode_number'] ?>/'">

        <?php if (!empty($ep['still'])): ?>
       <img
        src="<?= htmlspecialchars($ep['still']) ?>"
        style="width:90px;height:50px;border-radius:6px;object-fit:cover;"
        loading="lazy"
         >
         <?php endif; ?>
        <div class="ep-num">
            Episode <?= $ep['episode_number'] ?>
            <?php if (!empty($ep['title'])): ?>
                : <?= htmlspecialchars($ep['title']) ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($ep['duration'])): ?>
            <div class="ep-duration"><?= (int)$ep['duration'] ?> min</div>
        <?php endif; ?>

    </div>
<?php endwhile; ?>
<?php $eps->close(); ?>
<?php else: ?>
    
<div class="episode-box"
     onclick="location.href='/movies/player/<?= $slug ?>/season-<?= $selectedSeason ?>/episode-<?= $ep['episode_number'] ?>/'">
    <div class="ep-left">
        <div class="ep-num">Episode <?= $ep['episode_number'] ?></div>
        <?php if (!empty($ep['title'])): ?>
            <div class="ep-title"><?= htmlspecialchars($ep['title']) ?></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($ep['duration'])): ?>
        <div class="ep-duration"><?= (int)$ep['duration'] ?>m</div>
    <?php endif; ?>
    </div>
    
<?php endif; ?>
    </div>
    
    
    
  
<!------->
</div>
<?php endif; ?>
    
    
<!----------------CAST SECTION --------------->  
<?php if (!empty($castFull)) { ?>  
<div class="castfull" style="display:flex; overflow-x:auto; gap:15px; padding:10px 0;border-radius:12px; ">  
<?php foreach ($castFull as $c): ?>   <?php  
    $img = (!empty($c['img']) && strpos($c['img'], "null") === false)  
        ? $c['img']: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAAEsCAQAAACRXR/mAAAAFElEQVR42u3BMQEAAADCoPVPbQ0PoAAAAAAAAAC4G4UuAAEFTigJAAAAAElFTkSuQmCC";  
    ?>  <a href="https://www.google.com/search?q=<?= urlencode($c['name']) ?>"

style="text-decoration:none; color:#fff;border-radius:12px;background-color:transparent;"
target="_self">

<div class="cast-scroll" style="width:90px; text-align:center;scroll-snap-type:x mandatory;border-radius:12px;overflow: hidden !important;">  
       <div class="sk" style="overflow: hidden;width:90px; height:90px;border-radius:12px;background:#000;">  
        <img src="<?=$img?>"  
               
             onerror="this.onerror=null; this.style.background='#1b1c1f'; this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO3rFJQAAAAASUVORK5CYII=';"  
               
             style="width:90px; height:90px; border-radius:12px; object-fit:cover; background:#000;display:block;">  
    </div>  
        <div style="font-size:1rem;font-weight:400;height:100% !important; margin-top:5px;"><?= $c['name'] ?></div>  
        <div style="font-size:0.8rem; height=100% !important; opacity:0.8;"><?= $c['character'] ?></div>  
    </div>  
</a>

<?php endforeach; ?>  </div>  
<?php } ?>  <?php  
// ---------------------------------------------  
// ML-LIKE "YOU MAY ALSO LIKE"  
// ---------------------------------------------  
function tagTokens($text) {  
    $t = strtolower($text);  
    $t = preg_replace('/[^a-z0-9, ]/', ' ', $t);  
    $parts = array_filter(explode(',', $t));  
    $parts = array_map('trim', $parts);  
    return array_slice($parts, 0, 3); // take top 3 tags  
}  
// --- prepare token vars safely ---  
// --- TAGS ---  
$tagList = tagTokens($movie['tags'] ?? '');  
$tag1 = $tagList[0] ?? '';  
$tag2 = $tagList[1] ?? '';  
$tag3 = $tagList[2] ?? '';  
  
// --- GENRE ---  
$genreParts = explode(',', $movie['genre'] ?? '');  
$genreMain  = $genreParts[0] ?? '';  
  
// --- OTHER ---  
$desc  = $movie['description'] ?? '';  
$title = $movie['name'] ?? '';  
$slug  = $movie['slug'] ?? '';  
  
// --- SQL: ensure number of ? matches number of bound vars ---  
$recSQL = "  
SELECT id, name, genre,imdb, tags, slug,type, year,
  
(  
    (tags LIKE CONCAT('%', ?, '%')) * 20 +  
    (tags LIKE CONCAT('%', ?, '%')) * 18 +  
    (tags LIKE CONCAT('%', ?, '%')) * 16 +  
  
    (genre LIKE CONCAT('%', ?, '%')) * 10 +  
  
    (description LIKE CONCAT('%', ?, '%')) * 5 +  
    (name LIKE CONCAT('%', ?, '%')) * 4  
)  
+ (RAND() * 0.25) AS score  /* soft shuffle */  
FROM movie  
WHERE slug != ?  
ORDER BY score DESC  
LIMIT 12  
";  
  
$stmt = $con->prepare($recSQL);  
  
$stmt->bind_param(  
    "sssssss",   // 7 parameters  
    $tag1,  
    $tag2,  
    $tag3,  
    $genreMain,  
    $desc,  
    $title,  
    $slug  
);  
  
$stmt->execute();  
$recResult = $stmt->get_result();  
?>  <?php if ($recResult && $recResult->num_rows > 0) { ?>  <div class="heroo" style="padding:1.5rem 0 1rem 0;">  
<h4 style='font-weight:400;'>Similar Picks</h4>  <div class="slider-row">  <?php while ($rec = $recResult->fetch_assoc()) { ?>  <a href="<?= htmlspecialchars($rec['slug']); ?>" style="text-decoration:none;">  
    <div class="movie-card sk">

<?php  
$slug2 = $rec['slug'];  
$postercard = "https://images.manojwrites.xyz/$slug2/postercard.jpg";  
?>  <img src="<?= $postercard ?>" alt="<?= htmlspecialchars($rec['name']) ?>">  <div class="badge-left"><?= htmlspecialchars($rec['genre']); ?></div>  
        <div class="badge-right"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($rec['imdb']); ?></div>  
        <div class="badge-year"><?= htmlspecialchars($rec['year'] ?? ''); ?></div>
        <div class="badge-type"><?= (($rec['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?></div>
    </div>  
        <div class="movie-title-bottom"><?= htmlspecialchars($rec['name']); ?></div>  
      
</a>

<?php } ?>  </div>  
</div>  
<?php } ?>  <?php $stmt->close(); ?>  </div>  
</section>  
</main>  

    
<?php if ($isSeries): ?>
<script>
window.seriesSlug = "<?= $slug ?>";
window.seriesDefaultSeason = <?= $seasons[0] ?? 'null' ?>;
window.seriesSeasons = <?= json_encode($seasons) ?>;
window.tmdbId = <?= $seriesTmdbId ? $seriesTmdbId : 'null' ?>;
</script>
<?php endif; ?>
    
  <!--  <script src="../home/main.js"></script>-->  
<script src="./movie.js"></script> 
<script src="https://www.youtube.com/iframe_api"></script>  </body>  
</html>