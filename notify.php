<?php
    error_reporting(E_ALL);
ini_set("display_errors", 1);
require "config/connection.php";
// Load movies for dropdown
$movies = $con->query("SELECT id, name , slug FROM movie ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Send Notification</title>
    <style>
    
@import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap');
        
        *{
            font-family:"Josefin Sans";
        }
        body {
            font-family: "Josefin Sans";
            background: #0f0f0f;
            color: white;
            
            height:100dvh;
            width:100%;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .box {
            width: 90%;
            margin: auto;
            background: #1b1b1b;
            padding: 20px;
            border-radius: 12px;
            
        }

        h2 {
            margin-bottom: 15px;
            text-align: center;
        }

        select, button {
            width: 100%;
            padding: 1.7rem;
            border-radius: 8px;
            border: none;
            margin-top: 12px;
            font-size: 1.7rem;
        }

        select {
            background: #2c2c2c;
            color: white;
        }

        button {
            background: #e50914;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .poster {
            align-items:center;
            text-align: center;
            margin-top: 15px;
        }

        .poster img {
            width: 80%;
            border-radius: 8px;
            display: none;
            align-items:center;
        }
        h2{
            font-size:2.2rem;
        }
    </style>

    <script>
        function updatePoster() {
            const sel = document.getElementById("movie");
            const poster = sel.options[sel.selectedIndex].getAttribute("data-poster");
            const img = document.getElementById("poster-img");

            if (poster) {
                img.src = poster;
                img.style.display = "block";
            } else {
                img.style.display = "none";
            }
        }
    </script>
</head>
<body>

<div class="box">
    <h2>Send Movie Notification</h2>

    <form action="send.php" method="GET">
        <select name="id" id="movie" onchange="updatePoster()" required>
            <option value="">Select a movie</option>
            <?php while ($m = $movies->fetch_assoc()): ?>
                <option value="<?= $m['id'] ?>"
                  data-poster="https://images.manojwrites.xyz/<?= $m['slug'] ?>/postercard.jpg">
                    <?= $m['name'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div class="poster">
            <img id="poster-img">
        </div>

        <button type="submit">Send Notification</button>
    </form>
</div>

</body>
</html>