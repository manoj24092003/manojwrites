<?php

require("../config/connection.php");
error_reporting(E_ALL);
ini_set("display_errors", 1);

$slug = $_GET['slug'] ?? null;
$season = isset($_GET['season']) ? intval($_GET['season']) : null;
$episode = isset($_GET['episode']) ? intval($_GET['episode']) : null;

// If rewrite rule did not send slug (rare), extract from URL
if (!$slug) {
    $uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    $parts = explode('/', trim($uri, '/'));

    // URL: movies/player/yeh-meri-family/season-1/episode-1/
    if (isset($parts[2])) {
        $slug = $parts[2];
    }
    if (isset($parts[3]) && preg_match('/season-(\d+)/', $parts[3], $m)) {
        $season = intval($m[1]);
    }
    if (isset($parts[4]) && preg_match('/episode-(\d+)/', $parts[4], $m)) {
        $episode = intval($m[1]);
    }
}


$sql = "SELECT * FROM movie WHERE slug = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$res = $stmt->get_result();
$movie = $res->fetch_assoc();

/* =======================================================
   SERIES AUTO-REDIRECT (Watch Now button fix)
   ======================================================= */

$isSeries = false;

// Detect if this slug has season folders
$listURL = "https://video.manojwrites.xyz/list?prefix={$slug}/";
$data = json_decode(@file_get_contents($listURL), true);

$isSeries = false;

if (!empty($data["objects"])) {
    foreach ($data["objects"] as $obj) {
        if (strpos($obj, "season-") !== false) {
            $isSeries = true;
            break;
        }
    }
}

// If WATCH NOW was clicked (no season/episode in URL)
if ($isSeries && !$season && !$episode) {

    // Try resume last watched episode
    $device = $_COOKIE["device_id"] ?? "";
if ($device) {

    $resumeQ = $con->prepare("
        SELECT progress
        FROM continue_watching
        WHERE slug=? AND device_id=?
        LIMIT 1
    ");
    $resumeQ->bind_param("ss", $slug, $device);
    $resumeQ->execute();
    $saved = $resumeQ->get_result()->fetch_assoc();

    if ($saved) {
        // Always continue at Season 1 Episode 1 (since DB does not store season/episode)
        header("Location: /movies/player/$slug/season-1/episode-1/");
        exit;
    }
}

    // First time → Episode 1
    header("Location: /movies/player/$slug/season-1/episode-1/");
    exit;
}

// ======Fetch continue watching last progress============
$deviceId = $_COOKIE['device_id'] ?? null;
$resume_time = 0;

if ($deviceId) {
    $cw = $con->prepare("SELECT progress FROM continue_watching WHERE device_id=? AND movie_id=?");
    $cw->bind_param("si", $deviceId, $movie['id']);
    $cw->execute();
    $cwRes = $cw->get_result()->fetch_assoc();
    if ($cwRes) $resume_time = (int)$cwRes['progress'];
}
$stmt->close();

if (!$movie) {
    die("Movie not found");
}

/* ============================
   FETCH CONTINUE WATCHING
============================ */
$userDevice = $_COOKIE["device_id"] ?? $_SESSION["user_email"] ?? "";
$resume_time = 0;

if ($userDevice) {
    $q = $con->prepare("
        SELECT progress 
        FROM continue_watching
        WHERE slug = ? AND device_id = ?
        LIMIT 1
    ");
    $q->bind_param("ss", $slug, $userDevice);
    $q->execute();
    $cw = $q->get_result()->fetch_assoc();

    if ($cw) $resume_time = (int)$cw["progress"];
}



    // SERIES EPISODE MODE========
if ($season !== null && $episode !== null) {
    // SERIES
    $videoURL = "https://video.manojwrites.xyz/watch/$slug/season-$season/episode-$episode/index.m3u8";
} else {
    // MOVIE
    $videoURL = "https://video.manojwrites.xyz/watch/$slug/index.m3u8";
}
$type = "hls";

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="theme-color" content="#000"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"/>

<title><?= htmlspecialchars($movie['name']) ?></title>

<!-- Icons -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
<link rel="stylesheet" href="https://unpkg.com/@material-symbols/sharp/sharp.css">
    
<link href="https://fonts.googleapis.com/css2?
family-Josefin+Sans:ital,wght@0,100.700,1,100..700&display-swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@uswds/public-sans@latest/dist/fontface.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap'); </style>
 
    
<!-- CSS -->
<link rel="stylesheet" href="/movies/player.css?v=25">
<link rel="stylesheet" href="../style.css">

<!-- HLS for m3u8 -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

</head>
<body>

<div id="vp-container" class="vp-unlocked">
    <!-- HEADER -->
    <div id="vp-header" class="vp-controls">
        <button id="vp-back" class="vp-icon-btn">
            <i class="bi bi-arrow-left"></i>
        </button>
     
        
<div id="vp-title">
<?= htmlspecialchars($movie['name'] ?? '') ?>
<?php if ($season && $episode): ?>
 — S<?= $season ?>E<?= $episode ?>
<?php endif; ?>
    </div>
        
    </div>

    <!-- VIDEO -->
    <div id="vp-video-wrap">
        <div id="vp-overlay"></div>
        <video id="vp-video" preload="auto" playsinline webkit-playsinline crossorigin="anonymous">

<?php
if ($season && $episode) {
    $subBase = "https://video.manojwrites.xyz/watch/$slug/season-$season/episode-$episode";
} else {
    $subBase = "https://video.manojwrites.xyz/watch/$slug";
}
?>

<track
    kind="subtitles"
    src="<?=$subBase?>/en.vtt"
    srclang="en"
    label="English"
    default
     
>
 </video>   
 


        
        

        <!-- BUFFER LOADER -->
        <div id="vp-loader" class="vp-loader hidden"></div>
    </div>

    <!-- DOUBLE TAP ZONES -->
    <div id="vp-gesture-zone-left" class="vp-gesture-zone"></div>
    <div id="vp-gesture-zone-right" class="vp-gesture-zone"></div>
    <div id="vp-gesture-zone-center" class="vp-gesture-zone"></div>
    <!-- CENTER PLAY -->
    <div id="vp-center">
        <div id="vp-play" class="playpause"><div class="pp-ripple"></div></div>
    </div>

    <!-- SKIP ANIMATIONS -->
    <div id="vp-skip-left" class="vp-skip"><div class="vp-skip-circle"><span class="material-icons-sharp">
keyboard_double_arrow_left
        </span></div></div>
    <div id="vp-skip-right" class="vp-skip"><div class="vp-skip-circle"><span class="material-icons-sharp">
keyboard_double_arrow_right
        </span></div></div>

    <!-- TIMELINE -->
    <div id="vp-timeline" class="vp-controls vp-anim">
        <span class="vp-time-current">00:00 </span>

        <div class="vp-seek-area" id="vp-seek-area">

    <!-- MAIN LINE (THIS WAS MISSING) -->
    <div class="vp-track"style="gap:3px;";></div>

    <!-- BUFFERED -->
    <div id="vp-buffered" class="vp-buffered"></div>

    <!-- PROGRESS -->
    <div id="vp-progress" class="vp-progress"></div>

    <!-- THUMB -->
    <div id="vp-thumb" class="vp-thumb"></div>

        </div>

        <span class="vp-time-total"> 00:00</span>
    </div>

    <!-- BOTTOM MENU -->
    <div id="vp-menu" class="vp-controls vp-anim">
        <button id="vp-lock" class="vp-btn">
            <span class="material-icons-sharp">
lock_open
            </span>
            <span>Lock</span>
        </button>

        <button class="vp-btn" id="vp-resize">
            <span class="material-icons-outlined">
aspect_ratio
            </span>
            <span class="rtext">Fit</span>
        </button>

        <button class="vp-btn" id="vp-sync">
            <span class="material-icons-outlined">
subtitles
            </span>
            <span>Sync</span>
        </button>

        <button class="vp-btn" id="vp-source">
            <span class="material-icons-round">
settings_suggest
            </span>
            <span>Controls</span>
        </button>
       
        <button class="vp-btn" id="vp-rotate">
    <span class="material-icons-sharp">
screen_rotation
            </span>
    <span>Rotate</span>
        </button>
    </div>

<!-- ===================== -->
<!-- SYNC BOX (POPUP) -->
<!-- ===================== -->
<div id="vp-sync-box" class="hidden">
    <div class="vp-sync-header">
        <h3>Subtitle Delay</h3>
        <div id="vp-sync-sub">0 ms</div>
    </div>

    <div class="vp-sync-row">
        <button id="vp-sync-minus">–</button>
        <input id="vp-sync-edit" type="text" value="0">
        <button id="vp-sync-plus">+</button>
    </div>

    <div class="vp-sync-actions">
        <button id="vp-sync-apply">Apply</button>
        <button id="vp-sync-reset">Reset</button>
        <button id="vp-sync-cancel">Cancel</button>
    </div>
</div>


<!-- ===================== -->
<!-- SETTINGS MAIN MENU -->
<!-- ===================== -->
<div id="vp-settings" class="vp-settings hidden">
    <div class="vp-settings-box">
        <div class="vp-setting-row" id="vp-speed-row">
            <span>Playback Speed</span>
            <span class="vp-arrow"></span>
        </div>

        <div class="vp-setting-row" id="vp-quality-row">
            <span>Video Quality</span>
            <span class="vp-arrow"></span>
        </div>
        
        <div class="vp-setting-row" id="vp-audio-row">
             <span>Audio Track</span>
             <span class="vp-arrow"></span>
        </div>

        <div class="vp-setting-row" id="vp-sub-row">
            <span>Subtitles</span>
            <span class="vp-arrow"></span>
        </div>
        
        <div class="vp-sync-actions">
            <button id="vp-sub-cancel" >Cancel</button>
        </div>
    </div>
</div>


<!-- ===================== -->
<!-- SPEED MENU -->
<!-- ===================== -->
<div id="vp-speed-menu" class="vp-settings hidden">
    <div class="vp-settings-box">
        <div class="vp-setting-option" data-speed="0.5">0.5x</div>
        <div class="vp-setting-option" data-speed="1">1x (Normal)</div>
        <div class="vp-setting-option" data-speed="1.25">1.25x</div>
        <div class="vp-setting-option" data-speed="1.5">1.5x</div>
        <div class="vp-setting-option" data-speed="2">2x</div>
    </div>
</div>


<!-- ===================== -->
<!-- QUALITY MENU -->
<!-- ===================== -->
<div id="vp-quality-menu" class="vp-settings hidden">
    <div class="vp-settings-box" id="vp-quality-list">
        <!-- Filled dynamically -->
    </div>
</div>

<!-- ===================== -->
<!-- AUDIO TRACK MENU -->
<!-- ===================== -->
<div id="vp-audio-menu" class="vp-settings hidden">
    <div class="vp-settings-box" id="vp-audio-list">
        <!-- Filled by JS -->
    </div>
    </div>

<!-- ===================== -->
<!-- SUBTITLE MENU -->
<!-- ===================== -->
<div id="vp-subtitles-menu" class="vp-settings hidden">
    <div class="vp-settings-box" id="vp-sub-list">
        <!-- Filled dynamically -->
    </div>
</div>

    <!-- RED SMALL LOCK WHEN LOCKED -->
    <!------------------------------->
    <button id="vp-lock-small" class="vp-lock-small">
        <span class="material-icons-sharp lbtext">
lock_outline
        </span>
        <span class="lstext">Locked</span>
    </button>

</div>
    <div id="vp-toast"></div>
<script>
window.moviePlayer = {
    movie_id: <?= (int)$movie['id'] ?>,
    slug: <?= json_encode($movie['slug']) ?>,
    title_slug: <?= json_encode($movie['slug']) ?>,
    resume_time: <?= (int)$resume_time ?>,
    video_url: <?= json_encode($videoURL) ?>,
    video_type: <?= json_encode($type) ?>,
  season: <?= $season !== null ? (int)$season : 'null' ?>,
    episode: <?= $episode !== null ? (int)$episode : 'null' ?>
};
</script>

<script src="/movies/player.js" defer></script>


<!-- DEVICE ID SYSTEM (paste this) -->
<script>
if (!localStorage.getItem("device_id")) {
    const id = "dev_" + Math.random().toString(36).substring(2) + Date.now();
    localStorage.setItem("device_id", id);
}
    </script>
    
<!-- SAVE WATCH PROGRESS -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("vp-video");
    const movieId = <?= (int)$movie['id'] ?>;
    const slug = <?= json_encode($movie['slug']) ?>;
    const deviceId = localStorage.getItem("device_id");

    setInterval(() => {
        if (!video || isNaN(video.currentTime)) return;

        fetch("/movies/save_progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                device_id: deviceId,
                movie_id: movieId,
                slug: slug,
                progress: Math.floor(video.currentTime)
            })
        });
    }, 5000);
});
    </script>
    
<script>
document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("vp-video");
    const movieId = <?= (int)$movie['id'] ?>;

    if (!video) return;

    video.onloadedmetadata = () => {
        const realDuration = Math.floor(video.duration);

        fetch("/movies/save_real_duration.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                movie_id: movieId,
                real_duration: realDuration
            })
        });
    };
});
    </script> 
    
    <video id="wakeHelper" autoplay muted loop playsinline
style="position:fixed;width:1px;height:1px;opacity:0;pointer-events:none;bottom:0;left:0">
    </video>
</body>
</html>