//===========SEASON SELECT (DELEGATED)============

document.addEventListener("click", (e) => {

    const seasonBtn = e.target.closest("#seasonBtn");
    const seasonItem = e.target.closest(".season-item");

    // Toggle dropdown
    if (seasonBtn) {
        const list = document.getElementById("seasonList");
        if (!list) return;

        seasonBtn.classList.toggle("active");
        list.classList.toggle("show");
        return;
    }

// Select season
if (seasonItem) {
    const season = seasonItem.dataset.season;
    const btn = document.getElementById("seasonBtn");
    const list = document.getElementById("seasonList");

    if (!btn || !list) return;

    btn.innerHTML =
        `Season ${season} <span class="season-arrow"><i class="bi bi-caret-down-fill"></i></span>`;

    btn.classList.remove("active");
    list.classList.remove("show");

    // reload page with season
    location.href = `?season=${season}`;
   
    }
});


//========LOVE SHARE WATCHLIST========
async function loveClick(movieId) {

    if (!window.isLoggedIn) {
        window.location.href = "/google_login.php";
        return;
    }

    const btn = document.getElementById("loveBtn");
    const icon = btn.querySelector("i");
    const text = btn.querySelector("span");

    try {
        const res = await fetch("../movies/love_toggle.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "movie_id=" + movieId
        });

        const data = await res.json();

        if (data.status === "added") {
            btn.classList.add("active");     // red heart
            text.textContent = "Loved";      
        } 
        else if (data.status === "removed") {
            btn.classList.remove("active");  // white heart
            text.textContent = "Love";       
        }

    } catch (err) {
        console.error("Love error:", err);
    }
}


//====================WATCHLIST TOGGLE===============
async function watchToggle(movieId) {

    if (!window.isLoggedIn) {
        window.location.href = "/google_login.php";
        return;
    }

    const btn = document.getElementById("watchBtn");
    const text = btn.querySelector("span");

    try {
        const res = await fetch("/movies/watchlist_toggle.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "movie_id=" + movieId
        });

        const data = await res.json();

        if (data.status === "added") {
            btn.classList.add("active");
            text.textContent = "Added";
        } 
        else if (data.status === "removed") {
            btn.classList.remove("active");
            text.textContent = "Watchlist";
        }

    } catch (err) {
        console.error("Watchlist failed:", err);
    }
}
//---------------------------------------------

function shareButtonClick(title, slug, poster) {
    const btn = document.getElementById("shareBtn");

    // Add active class
    btn.classList.add("active");

    // Call your original function
    shareMovie(title, slug, poster)
        .finally(() => {
            // Remove active after share popup closes
            setTimeout(() => {
                btn.classList.remove("active");
            }, 300);
        });
}


async function shareMovie(title, slug, poster) {

    const movieUrl = "https://manojwrites.xyz/movies/" + slug;
    
    try {
        const posterUrl = `https://images.manojwrites.xyz/${slug}/postercard.jpg`;
        const res = await fetch(posterUrl);
        const blob = await res.blob();

        const file =new File([blob], title + ".jpg", { type: blob.type });    
        const shareData = {
            title: title,
            text: "Watch " + title,
            url: movieUrl,
            files: [file]
        };

        // Check if device can share with image
        if (navigator.canShare && navigator.canShare(shareData)) {
            await navigator.share(shareData);
            return;
        }

    } catch (e) {
        console.log("Poster share failed, fallback:", e);
    }

    // Fallback → Share without image
    if (navigator.share) {
        navigator.share({
            title: title,
            text: "Watch " + title,
            url: movieUrl
        });
    } else {
        navigator.clipboard.writeText(movieUrl);
        alert("Link copied!");
    }
}

// =====================================
// YOUTUBE TRAILER PLAYER + POPUP
// =====================================
let ytPlayer = null;
let pendingVideoId = null;

// Auto-close when ended
function onYTStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING) {
        enableWake();
    }
    if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
        disableWake();
    }

    if (event.data === YT.PlayerState.ENDED) {
        closeYT();
    }
}

// Single unified YouTube API init
function onYouTubeIframeAPIReady() {
    ytPlayer = new YT.Player("ytFrame", {
        width: "100%",
        height: "100%",
        playerVars: {
            autoplay: 0,
            controls: 1,
            modestbranding: 1,
            rel: 0
        },
        events: {
            onStateChange: onYTStateChange
        }
    });

    // Load if queued before API was ready
    if (pendingVideoId) {
        ytPlayer.loadVideoById(pendingVideoId);
        pendingVideoId = null;
    }
}

// Open popup and play trailer
function openTrailer(url) {
    const popup = document.getElementById("ytPopup");
    popup.style.display = "flex";
    popup.style.opacity = "1";

    enableWake();
    // Extract video ID
    let id = "";
    if (url.includes("embed/")) {
        id = url.split("embed/")[1].split("?")[0];
    }

    // If player is ready → play
    if (ytPlayer && ytPlayer.loadVideoById) {
        ytPlayer.loadVideoById(id);
    } else {
        pendingVideoId = id;
    }
}

// Close popup and stop video
function closeYT() {
    const popup = document.getElementById("ytPopup");

    if (ytPlayer && ytPlayer.stopVideo) {
        ytPlayer.stopVideo();
    }

    popup.style.opacity = "0";
    setTimeout(() => {
        popup.style.display = "none";
    }, 150);
}



// =====================================
// SCREEN WAKE LOCK (video-only)
// =====================================
let wakeLock = null;

async function enableWake() {
    try {
        if ("wakeLock" in navigator) {
            wakeLock = await navigator.wakeLock.request("screen");

            // If browser drops it, re-request only if video is playing
            if (wakeLock) {
                wakeLock.addEventListener("release", () => {
                    if (normalVideo && !normalVideo.paused) {
                        enableWake();
                    }
                    if (ytPlayer && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
                        enableWake();
                    }
                });
            }
        }
    } catch (e) {
        console.log("WakeLock error:", e);
    }
}

function disableWake() {
    if (wakeLock) {
        wakeLock.release().catch(() => {});
        wakeLock = null;
    }
}



// =====================================
// NORMAL <video> SUPPORT
// =====================================
let normalVideo = null;

document.addEventListener("DOMContentLoaded", () => {
    normalVideo = document.querySelector("video");

    if (normalVideo) {
        normalVideo.addEventListener("play", enableWake);
        normalVideo.addEventListener("pause", disableWake);
        normalVideo.addEventListener("ended", disableWake);
    }
});



// =====================================
// RE-REQUEST WAKE LOCK WHEN RETURNING
// =====================================
document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
        // If normal video playing
        if (normalVideo && !normalVideo.paused) {
            enableWake();
        }
        // If YouTube is playing
        if (ytPlayer && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
            enableWake();
        }
    }
});



// =====================================
// LAZY LOAD .sk IMAGES
// =====================================
document.querySelectorAll(".sk img:not(.no-lazy)").forEach(img => {
    if (!img.dataset.src && img.src) {
        img.dataset.src = img.src;
        img.removeAttribute("src");
    }
});

const io = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;

        const card = entry.target;
        const img = card.querySelector("img");
        if (!img || !img.dataset.src) return;

        img.src = img.dataset.src;
        img.addEventListener("load", () => {
            card.classList.add("loaded");
        }, { once: true });

        obs.unobserve(card);
    });
}, { rootMargin: "150px 0px" });

document.querySelectorAll(".sk").forEach(card => io.observe(card));



// =====================================
// BACK BUTTON
// =====================================
function goBack() {
    if (document.referrer !== "") {
        history.back();
    } else {
        window.location.href = "https://manojwrites.xyz/index.php";
    }
}



// ===============================
// WAKELOCK USER GESTURE TRIGGER
// REQUIRED BY CHROME
// ===============================
document.addEventListener("click", () => {
    // If something is already playing → enable wake
    if (normalVideo && !normalVideo.paused) enableWake();
    if (ytPlayer && ytPlayer.getPlayerState && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
        enableWake();
    }
}, { once: true });

document.addEventListener("touchstart", () => {
    // Same for touch
    if (normalVideo && !normalVideo.paused) enableWake();
    if (ytPlayer && ytPlayer.getPlayerState && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
        enableWake();
    }
}, { once: true });



