<?php session_start(); ?>

<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);  
ini_set('display_errors', 1);  
require_once("./config/connection.php");  // Assuming this file defines $con (the database connection object)

?>  



<!DOCTYPE html>  
<html lang="en">  
<head>  
    <link rel="apple-touch-icon" href="./favicon.ico" type="image/x-icon">  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <meta name="theme-color" content="#000000">  
    <title>Manoj Writes</title>  
    <meta name="title" content="ManojWrites">  

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">  
    <link rel="preconnect" href="https://fonts.googleapis.com">  
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>  
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">  
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&display=swap" rel="stylesheet">  
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">  
    <link rel="stylesheet" href="./style.css?v=7">  
    <link rel="manifest" href="./manifest.json">  
    <link rel="stylesheet" href="./hero.css?v=7">
    
    <script>
if (!localStorage.getItem("device_id")) {
    const id = "dev_" + Math.random().toString(36).substring(2) + Date.now();
    localStorage.setItem("device_id", id);
}
document.cookie = "device_id=" + localStorage.getItem("device_id") + "; path=/";
    </script>

</head>  
<body class="loading">  
<header class="header">

    <div class="logo" onclick="location.href='/index.php'"><i class="bi bi-tv-fill tv-logo"></i></div>  
    <div class="search-box" id="search-box">  
        <i class="bi bi-search search-icon"></i>  
        <input type="search" id="searchInput" placeholder="Search..." autocomplete="off">  
    </div>  
    <div id="searchResults"></div>  
    <div class="rcontainer">  
        
<?php if (!isset($_SESSION['user_email'])): ?>

    <!-- LOGIN BUTTON -->
    <button 
        onclick="location.href='/google_login.php';" 
        class="login-btn"
        style="
            background:none;
            border:1px solid #e50914;
            padding:4px 10px;
            border-radius:20px;
            color:#fff;
            font-size:0.8rem;
            margin-right:12px;
        ">
        <img src='https://developers.google.com/identity/images/g-logo.png'
             width="16" style="vertical-align:middle;margin-right:4px;border-radius:50%;">
        Login
    </button>

<?php else: ?>

    <!-- PROFILE BUTTON -->
    <div class="profile-btn " style="margin-right:12px; display:flex;flex-direction: row ; align-items:center; gap:6px;">

       <!-- <img src="<?= $_SESSION['user_pic'] ?>" 
             style="width:16px; height:16px; border-radius:12px; object-fit:cover;">-->

        <span style="color:#fff; font-size:0.7rem; font-weight:400;">Hi, <?= explode(" ", $_SESSION['user_name'])[0] ?>
        </span>

    </div>

<?php endif; ?>
        
        
        
        <i class="bi bi-search search-icon rsearch"></i>  
        <div>

        <nav class="nav-desktop" aria-label="Primary">  
            <ul>  
                <li>  
                    <a href="index.php">  
                        <i class="bi bi-house-fill"></i> HOME  
                    </a>  
                </li>  
                <li>
                    <a href="/movies/watchlist.php">
                        <i class="bi bi-bookmarks-fill"></i> WATCHLIST
                    </a>
                </li>
                <li>  
                    <a href="./books/index.php">  
                        <i class="bi bi-book-half"></i> BOOKS  
                    </a>  
                </li>  
                <li>  
                    <a href="./poems/index.php">  
                        <i class="bi bi-vector-pen"></i> POEMS  
                    </a>  
                </li>  
                <li>  
                    <a href="login/index.php">  
                        <i class="bi bi-person-fill"></i> ADMIN  
                    </a>  
                </li> 
                <li>
                    <?php if (isset($_SESSION['user_email'])): ?>
    <a href="/logout.php"><i class="bi bi-box-arrow-right"></i> LOGOUT</a>
<?php endif; ?>
                </li>
            </ul>  
        </nav>    
        <button
            id="menuBtn"
            class="menu-btn"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="mobileMenu"
        > 
            <span class="bar"></span>
        </button>  
        </div>  
    </div>  
</header>  
<nav  
    id="mobileMenu"  
    class="mobile-panel"  
    aria-hidden="true"  
    aria-label="Mobile primary"  
>  
    <a href="index.php"><i class="bi bi-house-fill"></i> HOME</a>  
    <a href="/movies/watchlist.php"><i class="bi bi-bookmarks-fill"></i> WATCHLIST</a>
    <a href="./books/index.php"><i class="bi bi-book-half"></i> BOOKS</a>  
    <a href="./poems/index.php"><i class="bi bi-vector-pen"></i> POEMS</a>  
    <a href="login/index.php"><i class="bi bi-person-fill"></i> ADMIN</a>  
    <?php if (isset($_SESSION['user_email'])): ?>
    <a href="/logout.php"><i class="bi bi-box-arrow-right"></i> LOGOUT</a>
<?php endif; ?>
</nav>  

<main class="main">  
<section class="hero" style="padding: 1rem;">  
    <?php  
    // pick 5 random movies each refresh  
    $topMovies = [];  
    $sql = "  
        SELECT id, name, imdb, year, duration, genre, description, slug ,type   
        FROM movie
        ORDER BY RAND()  
        LIMIT 5  
    ";  
    // Changed to use $con->query() for consistency if $con is a mysqli object
    $result = $con->query($sql);  
    if ($result) {  
        while ($row = $result->fetch_assoc()) {  
            $topMovies[] = $row;  
        }  
    }  
    ?>  

    <section class="top-hero">  
        <div class="top-hero-header">  
            <span class="top-hero-label"></span>  
            </div>  

        <div class="hero-slider" id="heroSlider">

        <?php foreach ($topMovies as $i => $m): ?>  
            <article  
                class="hero-slide <?= $i === 0 ? 'is-active' : '' ?>"  
                data-index="<?= $i ?>"  
                data-slug="<?= htmlspecialchars($m['slug']); ?>"
            >  
                  <div class="hero-image-wrapper">
                <img  
                    src="https://images.manojwrites.xyz/<?= htmlspecialchars($m['slug']) ?>/poster.jpg" 
                    alt="<?= htmlspecialchars($m['name']) ?>"  
                    class="hero-bg-img"> 
                </div>
            

                <div class="hero-bg-overlay"></div>  
                <div class="hero-shadow-bottom"></div>  
                <div class="hero-shadow-left"></div>  

                <div class="hero-banner-content">  
    
    <!-- Try loading posterlogo.png always -->
    <img
        src="https://images.manojwrites.xyz/<?= htmlspecialchars($m['slug']) ?>/posterlogo.png"
        alt="<?= htmlspecialchars($m['name']) ?>"
        class="hero-logo"
        onerror="this.style.display='none';
                 this.insertAdjacentHTML(
                     'afterend',
                     '<h2 class=\'hero-title-fallback\' style=\'font-weight:400;\'><?= htmlspecialchars($m['name']) ?></h2>');">

                    <div class="hero-tags-row">  
                        <?php foreach (explode(',', $m['genre'] ?? '') as $g): ?> 
                            <span><?= htmlspecialchars(trim($g)) ?></span>  
                        <?php endforeach; ?>  
                    </div>  

                    <div class="hero-info-row">  
                        <div class="hero-meta-row">  
                            <span>  
                                <i class="bi bi-calendar2-event-fill"></i>  
                                <?= htmlspecialchars($m['year']) ?>  
                            </span>  
                            <span>  
                                <i class="bi bi-hourglass-split"></i>  
                                <?= htmlspecialchars($m['duration'] ?? '') ?>  
                            </span>  
                            <span>  
                                <i class="bi bi-star-fill"></i>  
                                <?= htmlspecialchars($m['imdb'] ?? '')?>  
                            </span>  
                            <span>
                                <i class="bi bi-tv"></i>
                                <?= (($m['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?>
                            </span>
                        </div>  
                            
                        <?php 
                            $rawDesc = $m['description'] ?? '';  
                            if (function_exists('mb_strimwidth')) {
                                $shortDesc = mb_strimwidth($rawDesc, 0, 120, '...');
                            } else {
                                // Basic fallback if mbstring is not enabled
                                $shortDesc = (strlen($rawDesc) > 120) ? substr($rawDesc, 0, 117) . '...' : $rawDesc;
                            }
                        ?>
                        <p class="hero-desc">
                            <?= htmlspecialchars($shortDesc) ?>
                        </p>
                    </div>
                </div>
            </article>

        <?php endforeach; ?>  
        </div>  
        <div class="hero-dots" id="heroDots">  
            <?php foreach ($topMovies as $i => $m): ?>  
                <button  
                    class="hero-dot <?= $i ===0? 'is-active':'' ?>"  
                    data-index="<?= $i ?>"  
                ></button>  
            <?php endforeach; ?>  
        
        <div>
           <span class="music" id ="music">
               <i class="bi bi-music-note-list"></i>
            </span>
            <audio id="dailyAudio" preload="auto"></audio>
        </div>
        </div>
    </section>  
    
    
    
    
    
    
    <h4 style='font-weight:400;' onclick="location.href='/movies/content.php?type=recent'">Recently Added</h4>  
        
    <?php
$sql = "SELECT * FROM movie ORDER BY id DESC LIMIT 12";
$result = $con->query($sql);
?>
    <div class="slider-row">  
    <?php 
    // Check if results exist
    if ($result && $result->num_rows > 0) {
        while($m = $result->fetch_assoc()) { 
    ?>  
        <a href="movies/<?= htmlspecialchars($m['slug']); ?>" style="text-decoration:none;"> 
            <div class="movie-card sk">  
                <img 
                  src="https://images.manojwrites.xyz/<?= htmlspecialchars($m['slug']) ?>/postercard.jpg">
                
                <div class="badge-left">  
                    <?= htmlspecialchars($m['genre'] ?? ''); ?>  
                </div>  

                <div class="badge-right">  
                    <i class="bi bi-star-fill"></i>  
                    <?= htmlspecialchars($m['imdb'] ?? ''); ?>  
                </div>  
                <div class="badge-year">
                    <?= htmlspecialchars($m['year'] ?? ''); ?>
                </div>
                <div class="badge-type">
                   <?= (($m['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?>
                </div>
            </div>
                <div class="movie-title-bottom ">  
                    <?= htmlspecialchars($m['name']); ?>  
                </div>  

           
        </a>  
    <?php 
        } 
    }
    ?>  
    </div>  
    <!----------------------------->
    <?php  
// Get device ID from cookie  
$device_id = $_COOKIE['device_id'] ?? '';

if ($device_id) {

    $sqlCW = "SELECT m.*, cw.progress 
              FROM continue_watching cw
              JOIN movie m ON m.id = cw.movie_id
              WHERE cw.device_id = ?
              ORDER BY cw.updated_at DESC
              LIMIT 12";

    $stmtCW = $con->prepare($sqlCW);
    $stmtCW->bind_param("s", $device_id);
    $stmtCW->execute();
    $resCW = $stmtCW->get_result();

    if ($resCW->num_rows > 0) {
        echo "<h4 style='margin-top:20px;font-weight:400;'>Continue Watching</h4>";
        echo '<div class="slider-row ">';
        
        while ($cw = $resCW->fetch_assoc()) {
            

// 1) Use real duration if available
$duration = intval($cw['real_duration']);

// 2) Fallback to old duration system until updated
if ($duration < 10) {
    $raw = trim($cw['duration']);

    if (preg_match('/(\d+)h\s*(\d*)m?/', $raw, $m)) {
        $h = (int)$m[1];
        $mn = isset($m[2]) ? (int)$m[2] : 0;
        $duration = ($h * 3600) + ($mn * 60);
    } 
    else if (preg_match('/(\d+):(\d+):(\d+)/', $raw, $m)) {
        $duration = ($m[1] * 3600) + ($m[2] * 60) + $m[3];
    }
    else if (preg_match('/(\d+)\s*min/', $raw, $m)) {
        $duration = (int)$m[1] * 60;
    }
    else {
        $duration = ((int)$raw) * 60;
    }
}


            
              
            $watched = (int)$cw['progress'];
            // avoid division by zero
if ($duration <= 0) {
    $percent = 0;
} else {
    $percent = min(100, ($watched / $duration) * 100);
}
?>

            <a href="movies/<?= htmlspecialchars($cw['slug']); ?>" style="text-decoration:none;">
                <div class="movie-card sk" style="position:relative;">

                    <img src="https://images.manojwrites.xyz/<?= htmlspecialchars($cw['slug']); ?>/postercard.jpg" alt="Poster">

                    <!-- Left badge (genre) -->
                    <div class="badge-left">
                        <?= htmlspecialchars($cw['genre'] ?? ''); ?>
                    </div>

                    <!-- Right badge (IMDB) -->
                    <div class="badge-right">
                        <i class="bi bi-star-fill"></i>
                        <?= htmlspecialchars($cw['imdb'] ?? ''); ?>
                    </div>
                    <div class="badge-year">
                        <?= htmlspecialchars($cw['year'] ?? ''); ?>
                    </div>
                    <div class="badge-type">
                    
                     <?= (($cw['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?>
                    </div>

                   
                    <!-- Progress Bar -->
                    <div class="sk" style="
                        position:absolute;
                        bottom:0;
                        left:0;
                        width:100%;
                        height:4px;
                        background:#333;
                    ">
                        <div class="sk" style="
                            width: <?= $percent ?>%;
                            height:4px;
                            background:#e50914;
                            
                        "></div>
                        <!-- Title -->
                        <div class="movie-title-bottom ">
                        <?= htmlspecialchars($cw['name'] ?? ''); ?>
                        </div>
                    </div>

                    <!-- Time left label -->
<?php
$leftSeconds = max(0, $duration - $watched);

// Convert leftSeconds → readable
$h = floor($leftSeconds / 3600);
$m = floor(($leftSeconds % 3600) / 60);
$s = $leftSeconds % 60;  // seconds

if ($h > 0) {
    $leftText = "{$h}h {$m}m left";
}
else if ($m > 0) {
    $leftText = "{$m}m left";
}
else {
    $leftText = "{$s}s left";   // NEW → show seconds when under 1 min
}
?>
<div class="sk" style="
position:absolute;
    inset:0; /* covers entire card */
    background:rgba(0,0,0,0.45);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:0.9rem;
    font-weight:600;
    text-shadow:0 0 4px rgba(0,0,0,0.6);
    
">
    <?= $leftText ?>
                    </div>
                    
                </div>
            </a>
<?php
        }

        echo "</div>";
    }

    $stmtCW->close();
}
?>
    
    <!------------------------------>
    <?php  
    
    // All genre sections you want  
    $genres = [
    "Anime/Animation" => [
        "label" => "Anime/Animation",
        "match" => ["Anime", "Animation","cartoon"]
    ],

    "Romance" => [
        "label" => "Romance Bloom",
        "match" => ["Romance"]
    ],

    "Adventure" => [
        "label" => "Adventure Arc",
        "match" => ["Adventure"]
    ],

    "Mystery/Thriller" => [
        "label" => "Mystery & Thrillers",
        "match" => ["Mystery", "Thriller","suspense"]
    ],

    "Action" => [
        "label" => "Action Time",
        "match" => ["Action"]
    ],

    "Drama/Comedy" => [
        "label" => "Drama/Comedy",
        "match" => ["Drama", "family","comedy"]
    ],

    "Science Fiction" => [
        "label" => "Sci-Fi Horizon",
        "match" => ["Sci-Fi", "science fiction"]
    ]
];
    
    foreach ($genres as $key =>$g) {  
    
        // Print heading  
        echo "<h4 style='margin-top:20px;font-weight:400;' onclick=\"location.href='/movies/content.php?genre={$key}'\">{$g['label']}</h4>"; 
        
       // Build dynamic search conditions
        $conditions = [];
        $params = [];
        $types = "";
    
        foreach ($g['match'] as $m) {
        $conditions[] = "LOWER(genre) LIKE ?";
        $params[] = "%" . strtolower($m) . "%";
        $types .= "s";
    }

    // Final SQL with multiple OR conditions
    $sql = "SELECT id, genre, imdb, name, slug , type , year 
            FROM movie 
            WHERE " . implode(" OR ", $conditions) . "
            ORDER BY RAND() LIMIT 12";

    $stmt = $con->prepare($sql);
    if (!$stmt) continue; 


        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $q = $stmt->get_result(); // Get the result set

        echo '<div class="slider-row">';  
    
        while ($m = $q->fetch_assoc()) {  
            ?>  
            <a href="movies/<?= htmlspecialchars($m['slug']); ?>" style="text-decoration:none;">
                <div class="movie-card sk">  
                    <img src="https://images.manojwrites.xyz/<?= htmlspecialchars($m['slug'])?>/postercard.jpg"  alt="Poster Card">
                    <div class="badge-left">  
                        <?= htmlspecialchars($m['genre']); ?>  
                    </div>  
                    <div class="badge-right">  
                        <i class="bi bi-star-fill"></i>  
                        <?= htmlspecialchars($m['imdb']); ?>  
                    </div>  
                    <div class="badge-year">
                        <?= htmlspecialchars($m['year'] ?? ''); ?>
                    </div>
                    <div class="badge-type">
                   
                    <?= (($m['type'] ?? 'movie') === 'tv') ? 'TV' : 'Movie' ?>
                    </div>
                </div>
                    <div class="movie-title-bottom"><?= htmlspecialchars($m['name']); ?></div>  
                
            </a>  

        <?php  
        }  

        echo '</div>';
        $stmt->close(); // Close the statement
    }
    ?>

</section>  
<div>  
<section class="about">  
        <div class="aboutbox">  
            
            <div class="aboutheading hidden hiddenright">  
                <article><h2 style="color:#fff;">BEHIND THE SCENES</h2></article>  
            </div>  
            
            <div class="abouttext hidden hiddenbottom">  
                                
                <p>  
                    <span style="font-style: BOLD;">EVERY</span> story opens a door to another world. Characters lead the way, and readers follow — wandering through emotions and moments where getting lost feels just right.
                    
                </p>  
            </div>  
        </div>
    </section>  
</div>  
<div class="footer">  
    <div class="copy">  
        <!--<p>&copy2025.</p>-->
        <button id="feedbackBtn">feedback</button>
    </div>
    
<div id="feedbackPopup">
    <div class="feedback-box">
        <h3>Submit Feedback</h3>
         <div class="fb-wrapper">
        <textarea id="fbText" placeholder="Write your feedback..."></textarea>
             <span class="anon-note">just you & your thoughts</span>
        </div>
        <button id="sendFeedback">Submit</button>
        <button id="closeFeedback">Cancel</button>
    </div>
</div>
        
    
    <div class="social">  
        <a href="#" id="notifBell"><i class="bi bi-bell"></i></a>
        <a id="visitCount">
    <i class="bi bi-eyeglasses"></i> <span id="visitors" style="font-size:0.6rem;">0</span>
        </a>
    </div>  
</div>  
</main>  
    <script type="module" src="./home/main.js?v=2"></script> 
<script src="./hero.js"></script>  
    <script>
navigator.serviceWorker.register("/sw.js");
navigator.serviceWorker.register("/firebase-messaging-sw.js");
    </script>
    
    
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js"></script>
    
 
<script>
if ("serviceWorker" in navigator) {
  navigator.serviceWorker.register("/firebase-messaging-sw.js")
    .then(() => console.log("SW Registered"))
    .catch(err => console.error("SW Register Failed", err));
}
    </script>
    
    <script>
fetch("/log_visitor.php");
function updateVisitors() {
    fetch("/get_visitors.php")
        .then(res => res.text())
        .then(count => {
            document.getElementById("visitors").textContent = count;
        });
}

// Update instantly on load
updateVisitors();

// Update every 5 seconds
setInterval(updateVisitors, 5000);
    </script>
    <!-- Toast (global) -->
    <div id="site-toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>  
</html>
