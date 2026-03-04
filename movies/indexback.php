<?php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="apple-touch-icon" href="./favicon.ico" type="image/x-icon">  
<meta charset="UTF-8">  
<meta name="viewport" content="width=device-width, initial-scale=1.0">  
<meta name="theme-color" content="#000">  
<title>Manoj Writes - <?= htmlspecialchars($movie['name']); ?></title>  
<meta name="title" content="ManojWrites">  

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">  
<link rel="preconnect" href="https://fonts.googleapis.com">  
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>  
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap" rel="stylesheet">  
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">  
<link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">  
<link rel="stylesheet" href="../style.css?v=7">  
<link rel="stylesheet" href="./movie.css?v=7">  
<link rel="stylesheet" href="../hero.css?v=7">
</head>

<body class="loading" style="background:#000;";>

<header class="header">
    <div class="logo back-redirect" onclick="location.href='../index.php'"><i class="bi bi-tv-fill tv-logo"></i></div>
    <div class="scontainer">
        <div class="back-container">
            <button class="back-btn" onclick="goBack()">
                <span class="arrow">←</span> Back
            </button>
        </div>
    </div>
</header>

<nav id="mobileMenu" class="mobile-panel" aria-hidden="true" aria-label="Mobile primary">
    <a href="../index.php"><i class="bi bi-house"></i> HOME</a>
    <a href="../books/index.php"><i class="bi bi-book"></i> BOOKS</a>
    <a href="../poems/index.php"><i class="bi bi-feather"></i> POEMS</a>
    <a href="../login/index.php"><i class="bi bi-person"></i> ADMIN</a>
</nav>

<main class="main">

<section class="hero" style="padding:1rem;min-height: 100% !important;">
<div>

<div class="poster-container sk">
    <img src="<?= htmlspecialchars($movie['poster']); ?>" class="poster-image">

    <?php if (!empty($movie['posterpng'])): ?>
        <img src="<?= htmlspecialchars($movie['posterpng']); ?>" class="poster-logo-bottom no-lazy">
    <?php endif; ?>

    <div id="ytPopup" class="yt-overlay" onclick="closeYT()">
        <div class="yt-box" onclick="event.stopPropagation()" id="ytBox">
            <div id="ytFrame"></div>
        </div>
    </div>

    <div class="hero-bg-overlay"></div>
    <div class="hero-shadow-bottom"></div>
</div>

<div class="watch-now-container">
    <a href="/movies/player/<?= urlencode($movie['slug']); ?>" class="watch-now-btn">
        <i class="bi bi-tv-fill"></i> Watch Now
    </a>

<?php if (!empty($movie['trailer'])):
    $tr = strtok($movie['trailer'], "?");
    $tr = str_replace("youtu.be/", "www.youtube.com/embed/", $tr);
    $tr = str_replace("watch?v=", "embed/", $tr);
?>
    <a href="#" class="watch-now-btn" style="margin-left:10px;" onclick="openTrailer('<?= $tr ?>')">
        <i class="bi bi-film"></i> Trailer
    </a>
<?php endif; ?>
</div>
    
<div class="con-others" id="con-others">
    <div class="con-element">
        
        <div class="con">
            <i class="bi bi-heart-fill"></i>
            <span>love</span>
        </div>

        <div class="con">
            <i class="bi bi-share-fill"></i>
            <span>share</span>
        </div>

        <div class="con">
            <i class="bi bi-view-list"></i>
            <span>Watchlist</span>
        </div>

    </div>
    </div>

<div style="font-size:1rem; color:#ffffffcc; padding:10px;font-weight:400;">
    <strong style='font-size:1.1rem;font-weight:500; color:#fff;opacity:0.8;'><?= htmlspecialchars($movie['name']); ?> (<?= htmlspecialchars($movie['year']); ?>)</strong><br>
    Genre: <span style="opacity:0.7;"><?= htmlspecialchars($movie['genre']); ?></span><br>
    Cast: <span style="opacity:0.7;"><?= htmlspecialchars($movie['cast']); ?></span><br>
    IMDb: <span style="color:yellow;"><?= htmlspecialchars($movie['imdb']); ?></span><br>
    Duration: <span style="color:orange;"><?= htmlspecialchars($movie['duration']); ?></span><br>
    Director: <span style="opacity:0.7;"><?= htmlspecialchars($movie['director']); ?></span><br>
</div>

<p style="color:#fff; line-height:1.6;"><?= nl2br(htmlspecialchars($movie['description'])); ?></p>

<?php
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
SELECT id, name, poster, genre,imdb, tags, slug,

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
?>

<?php if ($recResult && $recResult->num_rows > 0) { ?>
<div class="heroo" style="padding:1.5rem 0 1rem 0;">
<h4 style='font-weight:400;'>Similar Picks</h4>

<div class="slider-row">

<?php while ($rec = $recResult->fetch_assoc()) { ?>
    <a href="<?= htmlspecialchars($rec['slug']); ?>" style="text-decoration:none;">
        <div class="movie-card sk">
            <img src="<?= htmlspecialchars($rec['poster']); ?>" alt="Poster">
            <div class="badge-left"><?= htmlspecialchars($rec['genre']); ?></div>
            <div class="badge-right"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($rec['imdb']); ?></div>
            <div class="movie-title-bottom"><?= htmlspecialchars($rec['name']); ?></div>
        </div>
    </a>
<?php } ?>

</div>
</div>
<?php } ?>

<?php $stmt->close(); ?>

</div>
</section>
</main>
  <!--  <script src="../home/main.js"></script>-->
<script src="./movie.js"></script>
<script src="https://www.youtube.com/iframe_api"></script>

</body>
</html>