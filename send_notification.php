<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require "config/connection.php";

// -----------------------------
// Load service account JSON
// -----------------------------
$firebaseKeyPath = __DIR__ . "/service-account.json";
$serviceAccount = json_decode(file_get_contents($firebaseKeyPath), true);

if (!$serviceAccount) {
    die("service-account.json missing or invalid");
}

// -----------------------------
// Build JWT for OAuth
// -----------------------------
$header = ["alg" => "RS256", "typ" => "JWT"];
$now = time();

$payload = [
    "iss"   => $serviceAccount["client_email"],
    "scope" => "https://www.googleapis.com/auth/firebase.messaging",
    "aud"   => $serviceAccount["token_uri"],
    "iat"   => $now,
    "exp"   => $now + 3600
];

function b64url($data) {
    return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
}

$jwtHeader  = b64url($header);
$jwtPayload = b64url($payload);

// Sign JWT
openssl_sign("$jwtHeader.$jwtPayload", $signature, $serviceAccount["private_key"], "SHA256");
$jwtSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

$assertion = "$jwtHeader.$jwtPayload.$jwtSignature";

// -----------------------------
// Request access token
// -----------------------------
$tokenRequest = [
    "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
    "assertion"  => $assertion
];

$ch = curl_init($serviceAccount["token_uri"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenRequest));

$resp = curl_exec($ch);
curl_close($ch);

$auth = json_decode($resp, true);
if (empty($auth["access_token"])) {
    die("Cannot get access token: " . $resp);
}

$accessToken = $auth["access_token"];

// -----------------------------
// Fetch movie data
// -----------------------------
$id = $_GET["id"] ?? null;
if (!$id) die("Movie ID missing");

$stmt = $con->prepare("SELECT name, slug FROM movie WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) die("Movie not found");

$titles = [

    "🎬 Now Streaming: {$movie['name']}",
    "🔥 {$movie['name']} Just Dropped!",
    "🍿 Your Next Watch: {$movie['name']}",
    "✨ New Release: {$movie['name']}",
    "🌟 Watch {$movie['name']} Today!",
    "📢 Fresh Upload: {$movie['name']}",
    "🚀 It's Here — {$movie['name']}",
    "💫 Discover {$movie['name']}",
    "🎞️ Presenting: {$movie['name']}",
    "⚡ Instant Watch: {$movie['name']}",

    "🌌 Dive Into {$movie['name']}",
    "📺 Ready to Watch: {$movie['name']}",
    "⭐ Trending: {$movie['name']}",
    "🔥 Just Added: {$movie['name']}",
    "🎉 New Arrival: {$movie['name']}",
    "📽️ Stream Now: {$movie['name']}",
    "🚨 Update: {$movie['name']} is Live!",
    "🎥 Your Movie Pick: {$movie['name']}",
    "🆕 Uploaded: {$movie['name']}",
    "▶️ Play Now: {$movie['name']}",

];

$bodies = [

    "Bingo! Your movie is ready — tap to play.",
    "Boom! Hit play and dive in.",
    "Let’s go! Start watching now.",
    "Your movie’s waiting — tap to roll!",
    "Ready when you are — tap to begin.",
    "Tap and enjoy the show!",
    "All set — hit play and relax.",
    "Movie time! Click to start.",
    "One tap away from watching!",
    "Press play and vibe on!",

    "Instant play! Let the fun begin.",
    "Tap to launch your viewing!",
    "Lights on — movie starts with one tap.",
    "Time to watch — tap to go!",
    "Here we go! Start streaming.",
    "Your click = instant play.",
    "Showtime! Tap to start.",
    "Tap in — your movie awaits.",
    "Ready, set, play!",
    "Jump in — tap to watch now.",

];

$title  = $titles[array_rand($titles)];
$body   = $bodies[array_rand($bodies)];
$icon = "https://images.manojwrites.xyz/" . $movie["slug"]. "/postercard.jpg";
$poster = "https://manojwrites.xyz/home/assets/images/logod512.png";
$url    = "https://manojwrites.xyz/movies/" . urlencode($movie["slug"]);

// -----------------------------
// Send notification to all tokens
// -----------------------------
$tokens = $con->query("SELECT token FROM fcm_tokens");

while ($row = $tokens->fetch_assoc()) {

    $message = [
        "message" => [
            "token" => $row["token"],

            // Desktop Chrome uses this
            "notification" => [
                "title" => $title,
                "body"  => $body,
                "image" => $icon
            ],
            
             "data" => [
            "url" => $url  // REQUIRED FOR CLICK
        ],

            // Web push (Android + click action)
            "webpush" => [
                "notification" => [
                    "title" => $title,
                    "body"  => $body,
                    "icon"  => $icon
                    
                ],
                "fcm_options" => [
                    "link" => $url
                ]
            ]
        ]
    ];

    $ch = curl_init("https://fcm.googleapis.com/v1/projects/animewatch01/messages:send");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "<b>Token:</b> {$row['token']}<br>";
    echo "<b>Response:</b> $response<br><br>";
    
    // -------------------------
// AUTO DELETE INVALID TOKENS (FCM v1)
// -------------------------
$__json = json_decode($response, true);

if (isset($__json["error"]["status"])) {

    $__error = $__json["error"]["status"];

    // FCM v1 invalid token errors
    $__invalid_errors = [
        "UNREGISTERED",
        "NOT_FOUND",
        "INVALID_ARGUMENT"
    ];

    if (in_array($__error, $__invalid_errors)) {

        $__del = $con->prepare("DELETE FROM fcm_tokens WHERE token = ?");
        $__del->bind_param("s", $row['token']);
        $__del->execute();

        echo "<div style='color:red;'>⛔ Invalid Token Removed: {$row['token']}</div>";
    }
  }
}

echo "<h2>Done!</h2>";
?>