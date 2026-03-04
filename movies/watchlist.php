<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require("../config/connection.php");

// If user is not logged in → no watchlist
if (!isset($_SESSION['user_email'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: /google_login.php");
    exit();
}

$user = $_SESSION['user_email'];

$imgBase = "https://images.manojwrites.xyz/";
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

<?php if (isset($_SESSION['user_email'])): ?>
<script>window.isLoggedIn = true;</script>
<?php else: ?>
<script>window.isLoggedIn = false;</script>
<?php endif; ?>
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

<section class="hero" style="padding:0.5rem;min-height: 100% !important;width:100% ! important;">
<div>

<h2 style="color:#fff; font-weight:400; margin-bottom:1rem;">
    Your Watchlist
</h2>

<?php
$user = $_SESSION['user_email'] ?? "";

$wl = $con->prepare("
    SELECT m.id, m.name, m.slug, m.genre, m.imdb,m.type,m.year 
    FROM watchlist w
    JOIN movie m ON w.movie_id = m.id
    WHERE w.user_email = ?
    ORDER BY w.id DESC
");
$wl->bind_param("s", $user);
$wl->execute();
$watchList = $wl->get_result();
?>

<?php if ($watchList->num_rows === 0): ?>
    <p style="color:#aaa;font-size:1rem;">No movies in watchlist.</p>
<?php endif; ?>

<div
     style="
display: flex;
flex-wrap: wrap;
gap: 7px;
padding: 0;
align-content: center;
     ">

<?php while ($row = $watchList->fetch_assoc()): ?>
    <a href="/movies/<?= htmlspecialchars($row['slug']); ?>" style="text-decoration:none;">
        <div class="movie-card sk" >
            <img src="<?= $imgBase.htmlspecialchars($row['slug'])?>/postercard.jpg" alt="Poster">
            <div class="badge-left"><?= htmlspecialchars($row['genre']); ?></div>
            <div class="badge-right"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($row['imdb']); ?></div>
            <div class="badge-year"><?= htmlspecialchars($row['year'] ?? ''); ?></div>
            <div class="badge-type"><?= (($row['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?></div>
            
        </div>
            <div class="movie-title-bottom"><?= htmlspecialchars($row['name']); ?></div>
        
    </a>
<?php endwhile; ?>

    </div>
    
<?php
/* ----------------------------------------------------
   BUILD USER TASTE PROFILE FROM WATCHLIST
-----------------------------------------------------*/

$user = $_SESSION['user_email'] ?? "";

$tasteGenres = [];
$tasteTags   = [];

// Fetch all genres + tags from watchlisted movies
if ($user) {
    $tstmt = $con->prepare("
        SELECT m.genre, m.tags
        FROM watchlist w
        JOIN movie m ON w.movie_id = m.id
        WHERE w.user_email = ?
    ");
    $tstmt->bind_param("s", $user);
    $tstmt->execute();
    $tres = $tstmt->get_result();

    while ($row = $tres->fetch_assoc()) {
        // Collect all GENRES
        $genres = explode(',', strtolower($row['genre'] ?? ''));
        foreach ($genres as $g) {
            $g = trim($g);
            if ($g) $tasteGenres[] = $g;
        }

        // Collect all TAGS
        $tags = explode(',', strtolower($row['tags'] ?? ''));
        foreach ($tags as $t) {
            $t = trim($t);
            if ($t) $tasteTags[] = $t;
        }
    }

    $tstmt->close();
}

// Remove duplicates but keep first few best matches
$genreTop = array_slice(array_unique($tasteGenres), 0, 3);
$tagTop   = array_slice(array_unique($tasteTags), 0, 3);

// Safely assign
$tag1 = $tagTop[0] ?? '';
$tag2 = $tagTop[1] ?? '';
$tag3 = $tagTop[2] ?? '';

$genre1 = $genreTop[0] ?? '';
$genre2 = $genreTop[1] ?? '';
$genre3 = $genreTop[2] ?? '';


/* --------------------------------------------
   RECOMMEND MOVIES BASED ON TASTE
-------------------------------------------- */

$recSQL = "
SELECT 
    id, name, genre, imdb, tags, slug,type,year,

    (
        (tags LIKE CONCAT('%', ?, '%')) * 20 +
        (tags LIKE CONCAT('%', ?, '%')) * 18 +
        (tags LIKE CONCAT('%', ?, '%')) * 16 +

        (genre LIKE CONCAT('%', ?, '%')) * 12 +
        (genre LIKE CONCAT('%', ?, '%')) * 9 +
        (genre LIKE CONCAT('%', ?, '%')) * 6
    )
    + (RAND() * 0.15) AS score

FROM movie

WHERE slug NOT IN (
    SELECT m.slug 
    FROM watchlist w
    JOIN movie m ON w.movie_id = m.id
    WHERE w.user_email = ?
)

ORDER BY score DESC
LIMIT 7
";

$stmt = $con->prepare($recSQL);
$stmt->bind_param(
    "sssssss",
    $tag1, $tag2, $tag3,
    $genre1, $genre2, $genre3,
    $user
);

$stmt->execute();
$recResult = $stmt->get_result();
?>




<?php if ($recResult && $recResult->num_rows > 0) { ?>
<div class="heroo" style="padding:1.5rem 0 1rem 0;">
<h4 style='font-weight:400;padding-bottom:5px;'>Based On Your Taste</h4>

<div class="slider-row" style="background:#1a1a1a; border-radius: 12px;">

<?php while ($rec = $recResult->fetch_assoc()) { ?>
    <a href="<?= htmlspecialchars($rec['slug']); ?>" style="text-decoration:none;">
        <div class="movie-card sk">
            <img src="<?= $imgBase.htmlspecialchars($rec['slug'])?>/postercard.jpg" alt="Poster">
            <div class="badge-left"><?= htmlspecialchars($rec['genre']); ?></div>
            <div class="badge-right"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($rec['imdb']); ?></div>
            <div class="badge-year"><?= htmlspecialchars($rec['year'] ?? ''); ?></div>
            <div class="badge-type"><?= (($rec['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?></div>
        </div>
            <div class="movie-title-bottom"><?= htmlspecialchars($rec['name']); ?></div>
        
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

</body>
</html>