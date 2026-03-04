// main.js

import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";

// Firebase config
const firebaseConfig = {
  apiKey: "AIzaSyBIKsBWdgDJ12za2cbPFqeKC1PoAZ1JOLM",
  authDomain: "animewatch01.firebaseapp.com",
  projectId: "animewatch01",
  storageBucket: "animewatch01.appspot.com",
  messagingSenderId: "261269655638",
  appId: "1:261269655638:web:de3de7b794c9709418abf7",
  measurementId: "G-W6DC7JDBLL"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// Request permission + get token
//Register Firebase SW FIRST (IMPORTANT)
async function initPush() {
  console.log("Requesting notification permission...");

  const permission = await Notification.requestPermission();
  if (permission !== "granted") {
    console.log("Permission denied");
    return;
  }

  // Register ONLY firebase-messaging-sw.js for FCM
  const reg = await navigator.serviceWorker.register("/sw.js");
  console.log("Firebase SW registered:", reg);

  // Generate token using correct service worker
  const token = await getToken(messaging, {
    vapidKey: "BJ0zv2gpwroW8GPDJbQVs-ydsEQx202KVsV1ua03YmotmDwXFyRCFJ4gImddCEQAlI-l_V4TLvkESUrcMVQMHpY",
    serviceWorkerRegistration: reg
  });

  console.log("FCM TOKEN:", token);

  if (token) {
    fetch("/save_token.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "token=" + encodeURIComponent(token)
    })
      .then(r => r.text())
      .then(t => console.log("Server:", t));
      localStorage.setItem("fcm_token", token);
  }
}

document.addEventListener("click", () => {
    if (!window.__pushAsked) {
        window.__pushAsked = true;
        initPush();
    }
});

// Foreground message
onMessage(messaging, (payload) => {
  console.log("Foreground Notification:", payload);
  new Notification(payload.notification.title, {
    body: payload.notification.body,
    icon: payload.notification.icon
  });
});
//=================MUSIC ICON ============================

const AUDIO_URL = "https://YOUR_WORKER_URL/audio";
const audio = document.getElementById("dailyAudio");
const button = document.getElementById("music");

// load audio
audio.src = AUDIO_URL;

let isPlaying = false;

button.onclick = () => {
    if (!isPlaying) {
        audio.play();
        button.classList.add("active");
        isPlaying = true;
    } else {
        audio.pause();
        audio.currentTime = 0; // rewind to start
        button.classList.remove("active");
        isPlaying = false;
    }
};

// auto-reset when the audio ends
audio.onended = () => {
    button.classList.remove("active");
    isPlaying = false;
};

// =============== NOTIFICATION ICON HANDLER ===============

// Bell icon
const bell = document.getElementById("notifBell");

// Check DB ONLY for THIS DEVICE token
async function checkBellStatus() {
    const token = localStorage.getItem("fcm_token") || "";

    try {
        const res = await fetch("/check_token.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "token=" + encodeURIComponent(token)
        });

        const json = await res.json();

        if (json.valid === true) {
            bell.style.color = "#e51409";  // active
        } else {
            bell.style.color = "";         // default
        }

    } catch (err) {
        console.error("Token check failed:", err);
        bell.style.color = ""; // default color
    }
}

// Clicking the bell triggers initPush()
bell.addEventListener("click", async function (e) {
    e.preventDefault();

    if (typeof initPush === "function") {
        await initPush();        // ask permission + save token
        await checkBellStatus(); // update color
    }
});

// On page load
checkBellStatus();


// ==============================
// UNIVERSAL WAKE LOCK CONTROLLER
// ==============================
let wakeLock = null;

async function enableWake() {
    try {
        if ("wakeLock" in navigator) {
            wakeLock = await navigator.wakeLock.request("screen");
        }
    } catch (e) {
        console.log("WakeLock error:", e);
    }
}

function disableWake() {
    if (wakeLock) {
        wakeLock.release();
        wakeLock = null;
    }
}


// ==============================
// 1) NORMAL <video> SUPPORT
// ==============================
document.addEventListener("DOMContentLoaded", () => {

    const video = document.querySelector("video");
    if (video) {
        video.addEventListener("play", enableWake);
        video.addEventListener("pause", disableWake);
        video.addEventListener("ended", disableWake);
    }
});


// ==============================
// 2) YOUTUBE IFRAME SUPPORT
// ==============================

// This must exist globally for YT API
let ytPlayer = null;

// Called automatically by YouTube API
function onYouTubeIframeAPIReady() {
    ytPlayer = new YT.Player("ytFrame", {
        events: {
            onStateChange: onYTStateChange
        }
    });
}

function onYTStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING) {
        enableWake();
    }
    if (
        event.data === YT.PlayerState.PAUSED ||
        event.data === YT.PlayerState.ENDED
    ) {
        disableWake();
    }
}


// ==============================
// 3) OPTIONAL: WAKE RE-REQUEST
// If user switches apps and comes back
// ==============================
document.addEventListener("visibilitychange", () => {
    if (!document.hidden && wakeLock) {
        enableWake();
    }
});


// ================== LAZY LOAD .sk IMAGES ==================
document.querySelectorAll('.sk img:not(.no-lazy)').forEach(img => {
  if (!img.dataset.src && img.src) {
    img.dataset.src = img.src;
    img.removeAttribute('src');
  }
});

const io = new IntersectionObserver((entries, obs) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;

    const card = entry.target;
    const img  = card.querySelector('img');
    if (!img || !img.dataset.src) return;

    img.src = img.dataset.src;
    img.addEventListener('load', () => {
      card.classList.add('loaded');
    }, { once: true });

    obs.unobserve(card);
  });
}, { rootMargin: '150px 0px' });

document.querySelectorAll('.sk').forEach(card => io.observe(card));


// =============== BACK BTN ==============
function goBack() {
  if (document.referrer !== "") {
    history.back();
  } else {
    window.location.href = "index.php";
  }
}


// ================== SEARCH BAR ==================
const searchIcon  = document.querySelector(".rsearch");
const searchBox   = document.getElementById("search-box");
const searchInput = document.getElementById("searchInput");
const resultBox   = document.getElementById("searchResults");

let searchAbort = null;
let debounce = null;

if (searchIcon && searchBox && searchInput && resultBox) {

  searchIcon.addEventListener("click", () => {
    const isHidden =
      searchBox.style.display === "none" ||
      searchBox.style.display === "";

    if (isHidden) {
      searchBox.style.display = "flex";
      searchBox.style.alignItems = "center";
      searchBox.style.justifyContent = "space-between";
      searchIcon.classList.add("active");
      searchInput.focus();
    } else {
      searchBox.style.display = "none";
      searchIcon.classList.remove("active");
      resultBox.style.display = "none";
    }
  });

  // ---------- FIXED + CLEAN SEARCH ----------
  function runSearch(query) {
    query = query.trim();

    if (query.length === 0) {
      resultBox.innerHTML = "";
      resultBox.style.display = "none";
      if (searchAbort) searchAbort.abort();
      clearTimeout(debounce);
      return;
    }

    clearTimeout(debounce);

    debounce = setTimeout(() => {
      if (searchAbort) searchAbort.abort();
      searchAbort = new AbortController();

      fetch("search.php?q=" + encodeURIComponent(query), {
        signal: searchAbort.signal
      })
        .then(res => res.json())
        .then(data => {
         
  
          resultBox.innerHTML = "";
          resultBox.style.display = "block";

          if (!data.length) {
            resultBox.innerHTML =
              "<div class='search-item'>No results found</div>";
            return;
          }

          data.forEach(movie => {
            resultBox.innerHTML += `
              <a href="movies/${movie.slug}" style="color:black; text-decoration:none;">
                <div class="search-item">
                  <img src="https://images.manojwrites.xyz/${movie.slug}/postercard.jpg">
                  <div>
                    <h3>${movie.name}</h3>
                    <p>
                      <i class="bi bi-star-fill"></i> ${movie.imdb}
                      <i class="bi bi-hourglass-split"></i> ${movie.duration}
                      <i class="bi bi-film"></i> ${movie.genre}
                      <i class="bi bi-calendar2-event-fill"></i> ${movie.year}
                    </p>
                  </div>
                </div>
              </a>
            `;
          });
        })
        .catch(err => {
          if (err.name === "AbortError") return;
          console.error("Search error:", err);
        });
    }, 180);
  }

  searchInput.addEventListener("input", function () {
    runSearch(this.value);
  });

  searchInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      runSearch(this.value);
    }
  });

  searchInput.addEventListener("search", function () {
    runSearch(this.value);
  });

  document.addEventListener("click", function (e) {
  const clickedOutside =
    !searchBox.contains(e.target) &&
    !resultBox.contains(e.target) &&
    e.target !== searchIcon;

  if (clickedOutside) {
    resultBox.style.display = "none";
    searchBox.style.display = "none";   // hide search box
    searchIcon.classList.remove("active");
    searchInput.value = "";             // clear text
    }
   });
}


// ================== MOBILE MENU ==================
document.addEventListener("DOMContentLoaded", () => {
  const btn   = document.getElementById("menuBtn");
  const panel = document.getElementById("mobileMenu");
  if (!btn || !panel) return;

  function openMenu() {
    panel.classList.add("open");
    btn.classList.add("open");
    btn.setAttribute("aria-expanded", "true");
    panel.setAttribute("aria-hidden", "false");
  }

  function closeMenu() {
    panel.classList.remove("open");
    btn.classList.remove("open");
    btn.setAttribute("aria-expanded", "false");
    panel.setAttribute("aria-hidden", "true");
  }

  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const isOpen = panel.classList.contains("open");
    isOpen ? closeMenu() : openMenu();
  });

  document.addEventListener("click", (e) => {
    const isClickInside = panel.contains(e.target) || btn.contains(e.target);
    if (!isClickInside) closeMenu();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeMenu();
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 821) closeMenu();
  });
});


// ================== REVEAL ANIMATIONS ==================
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add("show");
    } else {
      entry.target.classList.remove("show");
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll(".hidden").forEach(el => observer.observe(el));


// ================== SERVICE WORKER ==================
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/sw.js")
        .then(() => console.log("SW Registered"))
        .catch(err => console.log("SW Register Failed", err));
}

