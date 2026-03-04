document.querySelectorAll('.hero-bg-img').forEach(img => {
  if (img.complete) {
    img.classList.add('is-ready');
  } else {
    img.addEventListener('load', () => {
      img.classList.add('is-ready');
    });
  }
});


// TOP MOVIES HERO SLIDER
(function () {
  const slider = document.getElementById("heroSlider");
  const dotsContainer = document.getElementById("heroDots");
  if (!slider || !dotsContainer) return;

  const slides = Array.from(slider.querySelectorAll(".hero-slide"));
  const dots = Array.from(dotsContainer.querySelectorAll(".hero-dot"));
  if (!slides.length) return;

  let index = 0;

  function setSlide(i) {
    slides.forEach(s => s.classList.remove("is-active"));
    dots.forEach(d => d.classList.remove("is-active"));

    slides[i].classList.add("is-active");
    if (dots[i]) dots[i].classList.add("is-active");

    index = i;
  }

  function goNext() {
    setSlide((index + 1) % slides.length);
  }

  function goPrev() {
    setSlide((index - 1 + slides.length) % slides.length);
  }

  // dots click
  dots.forEach(d => {
    d.addEventListener("click", () => {
      const i = parseInt(d.dataset.index, 10);
      if (!Number.isNaN(i)) setSlide(i);
    });
  });

  // click banner -> go to movie page
  slides.forEach(s => {
    s.addEventListener("click", () => {
      const slug = s.dataset.slug;
      if (slug) {
        window.location.href = "movies/" + encodeURIComponent(slug);
      }
    });
  });

  // basic swipe
  let startX = 0;
  slider.addEventListener("touchstart", e => {
    startX = e.touches[0].clientX;
  });
  slider.addEventListener("touchend", e => {
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) < 40) return;
    if (dx < 0) goNext();
    else goPrev();
  });

  // auto-rotate (no hover pause)
  setSlide(0);
  setInterval(goNext, 4500);
})();


//================================================
// OPEN POPUP
document.getElementById("feedbackBtn").onclick = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
    document.getElementById("feedbackPopup").style.display = "flex";
};

// CLOSE POPUP
document.getElementById("closeFeedback").onclick = () => {
    document.getElementById("feedbackPopup").style.display = "none";
};

// SEND FEEDBACK
document.getElementById("sendFeedback").onclick = () => {
    let text = document.getElementById("fbText").value.trim();

    if (!text) {
        toast("Please enter feedback.");
        return;
    }

    fetch("/feedback_submit.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ feedback: text })
    })
    .then(r => r.text())
    .then(res => {
        toastSuccess("Thank you! Feedback submitted.");
        document.getElementById("feedbackPopup").style.display = "none";
        document.getElementById("fbText").value = "";
    })
    .catch(() => toast("Error sending feedback."));
};


// Toast helper — paste into global JS
(function () {
  const TOAST_ID = "site-toast";
  const AUTO_HIDE_MS = 2200; // visible time

  let hideTimer = null;

  window.toast = function (message, opts = {}) {
    // opts: {duration, pulse}
    const dur = typeof opts.duration === "number" ? opts.duration : AUTO_HIDE_MS;
    const pulse = !!opts.pulse;

    let el = document.getElementById(TOAST_ID);
    if (!el) {
      // fallback: create it (in case HTML wasn't pasted)
      el = document.createElement("div");
      el.id = TOAST_ID;
      el.setAttribute("role", "status");
      el.setAttribute("aria-live", "polite");
      el.setAttribute("aria-atomic", "true");
      document.body.appendChild(el);
    }

    // set text and show
    el.textContent = message;
    el.classList.remove("pulse");
    el.classList.add("show");
    if (pulse) el.classList.add("pulse");

    // reset hide timer
    if (hideTimer) clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      el.classList.remove("show");
      el.classList.remove("pulse");
      hideTimer = null;
    }, dur);
  };

  // convenience aliases
  window.toastSuccess = (msg) => window.toast(msg, { pulse: true });
  window.toastInfo = (msg) => window.toast(msg);
})();