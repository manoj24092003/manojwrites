// ================== BACK BUTTON ==================
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
                  <img src="${movie.poster}">
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
      searchBox.style.display = "none";
      searchIcon.classList.remove("active");
      searchInput.value = "";
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


// ================== WAKE LOCK ==================
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

document.addEventListener("DOMContentLoaded", () => {
  const video = document.querySelector("video");
  if (video) {
    video.addEventListener("play", enableWake);
    video.addEventListener("pause", disableWake);
    video.addEventListener("ended", disableWake);
  }
});


// ================== YOUTUBE IFRAME SUPPORT ==================
let ytPlayer = null;

function onYouTubeIframeAPIReady() {
  ytPlayer = new YT.Player("ytFrame", {
    events: {
      onStateChange: onYTStateChange
    }
  });
}

function onYTStateChange(event) {
  if (event.data === YT.PlayerState.PLAYING) enableWake();
  if (
    event.data === YT.PlayerState.PAUSED ||
    event.data === YT.PlayerState.ENDED
  ) disableWake();
}


document.addEventListener("visibilitychange", () => {
  if (!document.hidden && wakeLock) {
    enableWake();
  }
});


// ================== LAZY LOAD IMAGES ==================
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