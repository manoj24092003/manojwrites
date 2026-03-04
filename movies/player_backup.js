(() => {

window.videoQualities = null;

document.querySelectorAll('.vp-btn').forEach(btn => {

const add = () => btn.classList.add('touch-effect');
const remove = () => btn.classList.remove('touch-effect');

btn.addEventListener('pointerdown', add);

btn.addEventListener('pointerup', () => {
setTimeout(remove, 100);
});

btn.addEventListener('pointerleave', remove);
btn.addEventListener('pointercancel', remove);
});

const P = window.moviePlayer || {};

const video = document.getElementById("vp-video");
const playBtn = document.getElementById("vp-play");

const gestureLeft = document.getElementById("vp-gesture-zone-left");
const gestureRight = document.getElementById("vp-gesture-zone-right");

const skipLeftEl = document.getElementById("vp-skip-left");
const skipRightEl = document.getElementById("vp-skip-right");

const loader = document.getElementById("vp-loader");

const progress = document.getElementById("vp-progress");
const buffered = document.getElementById("vp-buffered");
const thumb = document.getElementById("vp-thumb");

const timeCurrent = document.querySelector(".vp-time-current");
const timeTotal = document.querySelector(".vp-time-total");

const seekArea = document.getElementById("vp-seek-area");

const container = document.getElementById("vp-container");
const lockBtn = document.getElementById("vp-lock");
const lockSmall = document.getElementById("vp-lock-small");

const syncBtn = document.getElementById("vp-sync");
const syncBox = document.getElementById("vp-sync-box");

let locked = false;
let dragging = false;
let disableAutoHide = false;
let settingsHideTimer = null;
let autoQualityEnabled = true;
let lastManualQuality = null;
let isLandscape = false;
    
    

async function checkNetworkSpeed() {
const start = performance.now();

try {
await fetch("https://www.gstatic.com/generate_204");

const end = performance.now();
const duration = end - start;

/* FIX: Prevent wrong speed (because file = 0 bytes) */
const speedMbps = 5;
return speedMbps;

} catch (e) {
console.error(e);
return 1;
}
}

/*------ DISABLE DOWNLOAD ---------*/
// Disable long-press download / open menu on video
const vpVideo = document.getElementById("vp-video");

vpVideo.addEventListener("contextmenu", (e) => {
e.preventDefault();
e.stopPropagation();
});

// Disable long-press behaviour on mobile
vpVideo.addEventListener("touchstart", (e) => {
// prevents long-press actions
if (e.touches.length === 1) {
e.preventDefault();
}
}, { passive: false });

// Disable saving in Chrome
vpVideo.setAttribute("controlsList", "nodownload nofullscreen noremoteplayback");
vpVideo.setAttribute("disablePictureInPicture", true);
vpVideo.setAttribute("playsinline", true);

/* ------------------------------
TOAST MESSAGE
------------------------------ */
function showToast(msg) {
const toast = document.getElementById("vp-toast");
toast.textContent = msg;
toast.classList.add("show");

setTimeout(() => {
toast.classList.remove("show");
}, 1500);
}

/*-------LANDSCAPE--------------*/
document.addEventListener("visibilitychange", () => {
if (!document.hidden) {
// Sync internal state with actual orientation
if (screen.orientation.type.includes("landscape")) {
isLandscape = true;
} else {
isLandscape = false;
}
}
});
/* ------------------------------
TIME FORMAT
------------------------------ */
function fmt(sec) {
if (!isFinite(sec)) return "00:00";

const h = Math.floor(sec / 3600);
const m = Math.floor((sec % 3600) / 60);
const s = Math.floor(sec % 60);

// If video is longer than 1 hour:
if (video.duration >= 3600) {

// If hours is 0 → show only MM:SS      
if (h === 0) {      
    return (      
        String(m).padStart(2, "0") + ":" +      
        String(s).padStart(2, "0")      
    );      
}      

// If hour > 0 → H:MM:SS (no leading zero on hour)      
return (      
    h + ":" +      
    String(m).padStart(2, "0") + ":" +      
    String(s).padStart(2, "0")      
);

}

// For short videos (<1 hour), always MM:SS
return (
String(m).padStart(2, "0") + ":" +
String(s).padStart(2, "0")
);

}

/* ------------------------------
SHOW SKIP ANIMATION
------------------------------ */
function showSkip(side) {
const el =side === "left" ? skipLeftEl: skipRightEl;
el.classList.remove("show");
void el.offsetWidth;
el.classList.add("show");
setTimeout(() => el.classList.remove("show"), 600);
}
/*------------------------------
UPDATE TIMELINE
------------------------------ */
function updateTimeline() {
if (!video.duration) return;

const pct = (video.currentTime / video.duration) * 100;
progress.style.width = pct + "%";
thumb.style.left = pct + "%";

timeCurrent.textContent = fmt(video.currentTime);
timeTotal.textContent = fmt(video.duration);

/*if (video.buffered.length > 0) {
const end = video.buffered.end(video.buffered.length - 1);
buffered.style.width = (end / video.duration * 100) + "%";
}  */

// ===== BUFFERED BAR (FIXED FOR HLS + MULTI AUDIO) =====
if (video.buffered.length && video.duration) {

let bufferEnd = video.currentTime;

for (let i = 0; i < video.buffered.length; i++) {
const start = video.buffered.start(i);
const end   = video.buffered.end(i);

// pick the range that contains currentTime    
if (video.currentTime >= start && video.currentTime <= end) {    
    bufferEnd = end;    
    break;    
}

}

const pct = Math.min(100, (bufferEnd / video.duration) * 100);
buffered.style.width = pct + "%";

   }

}

/* ------------------------------
SEEK
------------------------------ */
function seekPercent(pct) {
video.currentTime = (pct / 100) * video.duration;
updateTimeline();
}

/* ------------------------------
SKIP FUNCTION
------------------------------ */
function skip(sec) {
video.currentTime = Math.max(0, Math.min(video.duration, video.currentTime + sec));
updateTimeline();
}

/* ------------------------------
LOAD VIDEO SOURCE (FIXED)
------------------------------ */
function loadVideo() {

const slug = window.moviePlayer.slug;
const season = window.moviePlayer.season ?? null;
const episode = window.moviePlayer.episode ?? null;

let apiURL = "/api/getfile.php?slug=" + slug;

// Add season + episode for series
if (season != null && episode != null) {
apiURL += "&season=" + season + "&episode=" + episode;
}

fetch(apiURL)
.then(res => res.json())
.then(data => {
const url = data.filename;
    
if (!url) {    
        console.error("No video URL returned from getfile.php");    
        return;    
    }    

    // HLS (m3u8)    
    if (url.includes(".m3u8")) {    
        if (Hls.isSupported()) {    
            //window.hlsInstance = new Hls();
            window.hlsInstance = new Hls();
            window.hlsInstance.config.enableWorker = true;
            window.hlsInstance.loadSource(url);    
            window.hlsInstance.attachMedia(video); 
            window.hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play();
            });   
      
        } else if (video.canPlayType("application/vnd.apple.mpegurl")) {    
            video.src = url;    
        }    
    }    
    // MP4 fallback    
    else {    
        video.src = url;    
       
    }    
})    
.catch(err => console.error("Video load error:", err));

}
    
    
/* ============================
RESUME WATCH PROGRESS
============================ */
video.addEventListener("loadedmetadata", () => {
const resumeTime = window.moviePlayer.resume_time || 0;

if (resumeTime > 5 && resumeTime < video.duration - 5) {

// Smooth seek    
video.currentTime = resumeTime;    

// Show a toast message    
showToast("Resumed from " + fmt(resumeTime));

}

});

/* ------------------------------
DOUBLE TAP LOGIC
------------------------------ */
let lastTapLeft = 0;
let lastTapRight = 0;

gestureLeft.addEventListener("pointerdown", () => {

if (locked) return;

const now = Date.now();
if (now - lastTapLeft < 300) {
skip(-10);
showSkip("left");
}
lastTapLeft = now;

});

gestureRight.addEventListener("pointerdown", () => {

if (locked) return;

const now = Date.now();
if (now - lastTapRight < 300) {
skip(10);
showSkip("right");
}
lastTapRight = now;

});

/* ===========================================================
FIX: FULL NO-BLINK DOUBLE-TAP SYSTEM

Single tap → not blocked

Double tap or more → skip only, NO UI blink

Controls stay until finger is lifted
=========================================================== */

let tapLeft = 0;
let tapRight = 0;
let freezeControls = false;   // freeze UI toggle during double/multi tap

function detectMultiTap(side) {
const now = Date.now();
let last = side === "left" ? tapLeft : tapRight;

const isDouble = (now - last) < 300; // double or more taps

if (side === "left") tapLeft = now;
else tapRight = now;

return isDouble;

}

function blockEvent(e) {
e.stopPropagation();
e.stopImmediatePropagation();
e.preventDefault();
}

/* ----- Apply to both gesture zones ----- */
["vp-gesture-zone-left", "vp-gesture-zone-right"].forEach(id => {
const zone = document.getElementById(id);
if (!zone) return;

const side = id.includes("left") ? "left" : "right";

/* ------ pointerdown ------ */
zone.addEventListener("pointerdown", (e) => {
if (detectMultiTap(side)) {
freezeControls = true;  // freeze UI during double/multi tap
blockEvent(e);
}
});

/* ------ pointerup (unfreeze after finger lift) ------ */
zone.addEventListener("pointerup", () => {
setTimeout(() => {
freezeControls = false;
}, 60);
});

/* ------ click ------ */
zone.addEventListener("click", (e) => {
if (freezeControls) blockEvent(e);
});

});

/* ------------------------------
PLAY / PAUSE
------------------------------ */
const ripple = document.querySelector(".pp-ripple");

function triggerRipple() {
ripple.classList.remove("active");
void ripple.offsetWidth; // force reflow
ripple.classList.add("active");

setTimeout(() => {
ripple.classList.remove("active");
}, 350);

}

playBtn.addEventListener("click", () => {
triggerRipple();
if (video.paused) video.play();
else video.pause();
});

// When video starts → fade to pause icon
video.addEventListener("play", () => {
playBtn.classList.add("playing");
});

// When video pauses → fade to play icon
video.addEventListener("pause", () => {
playBtn.classList.remove("playing");
});
// LONG PRESS RIPPLE BEHAVIOR
playBtn.addEventListener("pointerdown", () => {
ripple.classList.add("active");
});

playBtn.addEventListener("pointerup", () => {
ripple.classList.remove("active");
});

playBtn.addEventListener("pointercancel", () => {
ripple.classList.remove("active");
});
/* ------------------------------
LOADER
------------------------------ */
video.addEventListener("waiting", () => loader.classList.remove("hidden"));
video.addEventListener("playing", () => loader.classList.add("hidden"));

/* -------- SCRUB SYSTEM -------- */

let isScrubbing = false;
let wasPaused = false;

function getPctFromEvent(e) {
const rect = seekArea.getBoundingClientRect();
let x = e.clientX - rect.left;
x = Math.max(0, Math.min(rect.width, x));
return (x / rect.width) * 100;
}

seekArea.addEventListener("pointerdown", (e) => {
if (locked) return;

isScrubbing = true;
wasPaused = video.paused;

video.pause();
showControls();
disableAutoHide = true;
e.stopPropagation();

const pct = getPctFromEvent(e);
seekPercent(pct);

});

document.addEventListener("pointermove", (e) => {
if (!isScrubbing) return;

const pct = getPctFromEvent(e);
seekPercent(pct);

});

document.addEventListener("pointerup", () => {
if (!isScrubbing) return;
isScrubbing = false;
disableAutoHide = false;
if (!wasPaused) video.play();

showControls();
});

/* ------------------------------
LOCK
------------------------------ */
lockBtn.addEventListener("click", () => {
locked = true;
container.classList.add("vp-locked");
document.getElementById("vp-overlay").style.opacity = "0";
});

lockSmall.addEventListener("click", () => {

if (locked) {
locked = false;
container.classList.remove("vp-locked");
showControls();
return;
}

if (container.classList.contains("controls-hidden")) {
showControls();
} else {
hideControlsImmediately();
}

});

/* ------------------------------
VIDEO EVENTS
------------------------------ */
video.addEventListener("timeupdate", updateTimeline);
video.addEventListener("loadedmetadata", updateTimeline);
video.addEventListener("durationchange", updateTimeline);

/* ------------------------------
RESIZE
------------------------------ */
const resizeBtn = document.getElementById("vp-resize");

let resizeMode = 0;

function applyResizeMode() {
video.classList.remove("fit", "fill");

if (resizeMode === 0) {
video.classList.add("fit");
resizeBtn.querySelector(".rtext").textContent = "Fit";
}
else if (resizeMode === 1) {
video.classList.add("fill");
resizeBtn.querySelector(".rtext").textContent = "Fill";
}
}

resizeBtn.addEventListener("click", () => {
resizeMode = (resizeMode + 1) % 2;
applyResizeMode();
});

applyResizeMode();

/* ------------------------------
FULLSCREEN
------------------------------ */
async function enterImmersiveFullscreen() {
try {
document.exitFullscreen?.();
const el = document.documentElement;

if (!document.fullscreenElement) {
await document.body.requestFullscreen({ navigationUI: "hide" });
}

if (screen.orientation && screen.orientation.lock) {
await screen.orientation.lock("landscape");
}

} catch (e) {
console.error(e);
}
}

/* ------------------------------
ROTATION
------------------------------ */
const rotateBtn = document.getElementById("vp-rotate");

rotateBtn.addEventListener("click", async () => {
await enterImmersiveFullscreen();
try {
if (!document.fullscreenElement) {
await document.documentElement.requestFullscreen();
}

if (!isLandscape) {
await screen.orientation.lock("landscape");
isLandscape = true;

} else {
await screen.orientation.lock("portrait");
isLandscape = false;
}

} catch (e) {
console.error(e);
}

});

/* ------------------------------
BACK BUTTON
------------------------------ */
document.getElementById("vp-back").addEventListener("click", async () => {
try {
if (screen.orientation && screen.orientation.unlock) {
screen.orientation.unlock();
}

if (document.fullscreenElement) {
await document.exitFullscreen();
setTimeout(() => window.history.back(), 150);
}

setTimeout(() => {
window.history.back();
}, 150);

} catch (e) {
console.error(e);
window.history.back();
}

});

/* ------------------------------
INIT
------------------------------ */
loadVideo();
/* FIX: Remove forced fullscreen — autoplay blocker */
setTimeout(() => {
console.log("Fullscreen auto-request disabled.");
}, 300);

function hidePlayControls() {
playBtn.style.opacity = "0";
}

function showPlayControls() {
playBtn.style.opacity = "1";
}

video.addEventListener("waiting", () => {
loader.classList.remove("hidden");
hidePlayControls();
});

video.addEventListener("play", hidePlayControls);
video.addEventListener("canplay", showPlayControls);
video.addEventListener("playing", showPlayControls);

/* ------------------------------
AUTO HIDE CONTROLS
------------------------------ */

let hideTimer = null;
const AUTO_HIDE_DELAY = 3000;

function showControls() {

document.getElementById("vp-overlay").style.opacity = "1";

video.classList.remove("sub-normal");
video.classList.add("sub-up");
container.classList.remove("controls-hidden");
if (hideTimer) clearTimeout(hideTimer);

// STOP AUTO HIDE if any popup is open
if (isPopupOpen()) {
container.classList.remove("controls-hidden");
return;
}

if (!video.paused && !disableAutoHide) {
hideTimer = setTimeout(() => {

container.classList.add("controls-hidden");
video.classList.remove("sub-up");
video.classList.add("sub-normal");

document.getElementById("vp-overlay").style.opacity = "0";

}, AUTO_HIDE_DELAY);

}

}

function hideControlsImmediately() {

video.classList.remove("sub-up");
video.classList.add("sub-normal");

if (hideTimer) clearTimeout(hideTimer);
container.classList.add("controls-hidden");
video.classList.remove("sub-up");
video.classList.add("sub-normal");
document.getElementById("vp-overlay").style.opacity = "0";

}

container.addEventListener("click", (e) => {
if (e.target.closest("#vp-sync")) return;
if (locked) return;
if (!syncBox.classList.contains("hidden")) return;
if (e.target.closest("#vp-play")) return;
if (e.target.closest("#vp-thumb")) return;
if(e.target.closest("#vp-lock-small")) return;
if (isScrubbing) return;

if (isSettingsOpen()) {
hideControlsImmediately();  // keep controls visible
return;
}

if (container.classList.contains("controls-hidden")) {
showControls();
}
else {
hideControlsImmediately();
}
});

video.addEventListener("play", () => {
showControls();
});

video.addEventListener("pause", () => {
container.classList.remove("controls-hidden");
if (hideTimer) clearTimeout(hideTimer);
});

container.addEventListener("transitionend", () => {
if (container.classList.contains("controls-hidden")) {
video.classList.remove("sub-up");
video.classList.add("sub-normal");
document.getElementById("vp-overlay").style.opacity = "0";

}

});
/* -------------------------------------------------------------------
SHOW / HIDE SMALL LOCK WHEN SCREEN IS TOUCHED (LOCKED MODE)
------------------------------------------------------------------- */

let lockHintTimer = null;     // timer for auto hide when locked
const LOCK_HINT_DELAY = 3000; // 3 sec delay

container.addEventListener("click", (e) => {
// Only run this logic when player is locked
if (!locked) return;

// Prevent interfering with main control toggles
e.stopPropagation();

const smallLock = document.querySelector("#vp-lock-small");

// If small lock is hidden → show immediately
if (smallLock.classList.contains("hide")) {
smallLock.classList.remove("hide");

// Reset auto-hide      
if (lockHintTimer) clearTimeout(lockHintTimer);      
lockHintTimer = setTimeout(() => {      
    smallLock.classList.add("hide");      
}, LOCK_HINT_DELAY);      

return;

}

// If small lock is visible → hide immediately
if (!smallLock.classList.contains("hide")) {
if (lockHintTimer) clearTimeout(lockHintTimer);
smallLock.classList.add("hide");
return;
}

});
/* ------------------------------
BLOCK BACK WHEN LOCKED
------------------------------ */
document.addEventListener("fullscreenchange", () => {
if (locked && !document.fullscreenElement) {
document.documentElement.requestFullscreen();
}
});

function pushBlockState() {
window.history.pushState({ block: true }, "");
window.history.pushState({ block2: true }, "");
}

pushBlockState();

lockBtn.addEventListener("click", () => {
pushBlockState();
});

lockSmall.addEventListener("click", () => {
pushBlockState();
});

window.addEventListener("popstate", () => {

if (locked) {
pushBlockState();
if (navigator.vibrate) navigator.vibrate(50);
return;
}

if (document.fullscreenElement) {
document.exitFullscreen();
return;
}

window.history.back();

});

/* ------------------------------
SETTINGS
------------------------------ */

const settingsBtn = document.getElementById("vp-source");

const settingsMenu = document.getElementById("vp-settings");
const speedMenu = document.getElementById("vp-speed-menu");
const qualityMenu = document.getElementById("vp-quality-menu");
const subtitleMenu = document.getElementById("vp-subtitles-menu");
const subMenu = document.getElementById("vp-subtitles-menu");
const speedRow = document.getElementById("vp-speed-row");
const qualityRow = document.getElementById("vp-quality-row");
const subRow = document.getElementById("vp-sub-row");

const qualityList = document.getElementById("vp-quality-list");
const subList = document.getElementById("vp-sub-list");

//------- EXCEPTION AUTO HIDE: Detect ANY popup open ---------
function isPopupOpen() {
return (
!settingsMenu.classList.contains("hidden") ||
!speedMenu.classList.contains("hidden") ||
!qualityMenu.classList.contains("hidden") ||
!subMenu.classList.contains("hidden") ||
!syncBox.classList.contains("hidden")
);
}

[
"#vp-settings",
"#vp-speed-menu",
"#vp-quality-menu",
"#vp-subtitles-menu"
].forEach(sel => {
document.querySelectorAll(sel).forEach(menu => {
menu.addEventListener("click", (e) => {
e.stopPropagation();
});
});
});

function hideAllMenus() {
settingsMenu.classList.add("hidden");
speedMenu.classList.add("hidden");
qualityMenu.classList.add("hidden");
subMenu.classList.add("hidden");
}

function isSettingsOpen() {
return !settingsMenu.classList.contains("hidden") ||
!speedMenu.classList.contains("hidden") ||
!qualityMenu.classList.contains("hidden") ||
!subMenu.classList.contains("hidden");
}

settingsBtn.addEventListener("click", (e) => {
e.stopPropagation();

// CLOSE SYNC BOX FIRST
syncBox.classList.add("hidden");

if (settingsMenu.classList.contains("hidden")) {
hideAllMenus();
settingsMenu.classList.remove("hidden");

} else {
hideAllMenus();

}

});

// ===============================
// CANCEL BUTTON (Settings Section)
// ===============================

(function () {
const cancelBtn = document.getElementById("vp-sub-cancel");
const settingsMenu = document.getElementById("vp-settings");
const speedMenu = document.getElementById("vp-speed-menu");
const qualityMenu = document.getElementById("vp-quality-menu");
const subtitleMenu = document.getElementById("vp-subtitles-menu"); // If exists

if (!cancelBtn) return;

cancelBtn.addEventListener("click", () => {
// Hide all sub-menus
speedMenu?.classList.add("hidden");
qualityMenu?.classList.add("hidden");
subtitleMenu?.classList.add("hidden");

// Show main settings menu    
settingsMenu.classList.add("hidden");

});

})();

//--------- SPEED --------------
speedRow.addEventListener("click", () => {
hideAllMenus();
speedMenu.classList.remove("hidden");

});

document.querySelectorAll("#vp-speed-menu .vp-setting-option").forEach(opt => {
/* AUTO-SELECT DEFAULT SPEED 1× */
if (opt.dataset.speed === "1") {
opt.classList.add("active");

}

opt.addEventListener("click", () => {
let spd = parseFloat(opt.dataset.speed);
video.playbackRate = spd;

document.querySelectorAll("#vp-speed-menu .vp-setting-option")
.forEach(o => o.classList.remove("active"));

opt.classList.add("active");
showToast(`Speed: ${spd}×`);
hideAllMenus();

});
});

//--------- QUALITY -------------------
qualityRow.addEventListener("click", () => {
hideAllMenus();
qualityMenu.classList.remove("hidden");
populateQualityMenu();
});

/* ---------------------------------------------------
QUALITY MENU
--------------------------------------------------- */
window.mp4RealRes = {};

function populateQualityMenu() {
qualityList.innerHTML = "";
updateAutoQualityLabel();
let foundQuality = false;
//---AUTO----
const autoItem = document.createElement("div");
autoItem.className = "vp-setting-option active auto";

// Default
autoItem.textContent = "Auto";

// ----- HLS AUTO LABEL -----
if (window.hlsInstance) {
const lvl = window.hlsInstance.levels[window.hlsInstance.currentLevel];
if (lvl) {
autoItem.textContent = `Auto (${lvl.width}×${lvl.height})`;
}
}

// ----- MP4 AUTO LABEL -----
else if (window.videoQualities) {
const labels = Object.keys(window.videoQualities).map(x => parseInt(x));
const highest = Math.max(...labels);

if (window.mp4RealRes[highest]) {
const { w, h } = window.mp4RealRes[highest];
autoItem.textContent =`Auto (${w}×${h})`;
}

}
//------------------

autoItem.onclick = () => {
highlightQuality(autoItem);

if (window.hlsInstance) {
window.hlsInstance.currentLevel = -1;
window._lastHlsLevel = -1;
}

showToast("Quality: Auto");
hideAllMenus();

};

qualityList.appendChild(autoItem);

/* HLS QUALITY */
if (window.hlsInstance) {
window.hlsInstance.on(Hls.Events.MANIFEST_PARSED, (evt, data) => {

populateQualityMenu();
if (!data.levels || data.levels.length <= 1) {
qualityList.innerHTML = "";
qualityList.appendChild(autoItem);

const msg = document.createElement("div");        
    msg.className = "vp-setting-option disabled";        
    msg.style.opacity = "0.5";        
    msg.textContent = "No other qualities";        
    qualityList.appendChild(msg);        
    return;        
}        

qualityList.innerHTML = "";        
qualityList.appendChild(autoItem);        

data.levels.forEach((lvl, i) => {        
    const item = document.createElement("div");        
    item.className = "vp-setting-option";        
    // Show full resolution (Example: 720p (1280×720))      
    item.textContent = `${lvl.height}p (${lvl.width}×${lvl.height})`;      

    item.onclick = () => {        
        window.hlsInstance.currentLevel = i;        
        highlightQuality(item);        
        showToast(`Quality: ${lvl.height}×${lvl.width}`);      
        hideAllMenus();        
    };        

    qualityList.appendChild(item);        
});

});

foundQuality = true;

}

if (window.hlsInstance) {
window.hlsInstance.on(Hls.Events.LEVEL_SWITCHED, () => {
const autoOption = document.querySelector("#vp-quality-list .auto");
const lvl = window.hlsInstance.levels[window.hlsInstance.currentLevel];

if (autoOption && lvl) {      
    autoOption.textContent = `Auto (${lvl.width}×${lvl.height})`;      
}

});
}

if (window.hlsInstance) {
window.hlsInstance.on(Hls.Events.LEVEL_SWITCHED, () => {
updateAutoQualityLabel();
});
}

/* MP4 QUALITY */
if (window.videoQualities && !window.hlsInstance) {

const autoMP4 = document.createElement("div");
autoMP4.className = "vp-setting-option";
autoMP4.textContent = "Auto";

autoMP4.onclick = () => {
const highest = Object.keys(window.videoQualities)
.sort((a, b) => parseInt(b) - parseInt(a))[0];

const t = video.currentTime;        
const wasPlaying = !video.paused;

video.src = window.videoQualities[highest];
video.load();

video.onloadedmetadata = () => {
const w = video.videoWidth;
const h = video.videoHeight;

window.mp4RealRes[highest] = { w, h };

showToast(`Quality: ${h}×${w}`);

};

video.currentTime = t;
if (wasPlaying) video.play();

highlightQuality(autoMP4);
showToast("Quality: Auto");
hideAllMenus();
};

qualityList.appendChild(autoMP4);

for (let label in window.videoQualities) {
const item = document.createElement("div");
item.className = "vp-setting-option";
const h = parseInt(label);
const w = Math.round((h / 9) * 16); // assume 16:9
item.textContent = `${h}×${w}`;

item.onclick = () => {        
    const t = video.currentTime;        
    const wasPlaying = !video.paused;        

    video.src = window.videoQualities[label];        
    video.load();        
    video.currentTime = t;        
    if (wasPlaying) video.play();        

    highlightQuality(item);

let display = label;

// If we already detected real resolution, show it
if (window.mp4RealRes[label]) {
const { w, h } = window.mp4RealRes[label];
display = `${h}×${w}`;
} else {
// Fallback: show label until real detected
const h = parseInt(label);
const w = Math.round((h / 9) * 16);
display = `${h}×${w}`;
}
item.textContent = display;

hideAllMenus();
};

qualityList.appendChild(item);

}

foundQuality = true;

}

if (!foundQuality) {
const msg = document.createElement("div");
msg.className = "vp-setting-option disabled";
msg.style.opacity = "0.5";
msg.textContent = "No qualities available";
qualityList.appendChild(msg);
}
}

function highlightQuality(selected) {
document.querySelectorAll("#vp-quality-menu .vp-setting-option")
.forEach(x => x.classList.remove("active"));
selected.classList.add("active");

if (selected.textContent.trim().toLowerCase() === "auto") {
autoQualityEnabled = true;
lastManualQuality = null;
} else {
autoQualityEnabled = false;
lastManualQuality = selected.textContent.trim();
}
}

video.addEventListener("loadedmetadata", () => {
const w = video.videoWidth;
const h = video.videoHeight;

if (!window.videoQualities) return;

// find which quality this matches
for (let q in window.videoQualities) {
const url = window.videoQualities[q];
if (video.src.includes(url)) {
window.mp4RealRes[q] = { w, h };
}
}

});
//----real quality -----
function updateAutoQualityLabel() {
if (!window.hlsInstance) return;

const current = window.hlsInstance.currentLevel;
const level = window.hlsInstance.levels[current];

if (!level) return;

const autoOption = document.querySelector("#vp-quality-list .auto");
if (autoOption) {
autoOption.textContent = `Auto (${level.width}×${level.height})`;
}

}

/* ------------------ CUSTOM TOAST ------------------ */
function showToast(msg) {
let toast = document.getElementById("vp-toast");

if (!toast) {
toast = document.createElement("div");
toast.id = "vp-toast";
toast.style.position = "absolute";
toast.style.left = "50%";
toast.style.bottom = "20%";
toast.style.transform = "translateX(-50%)";
toast.style.background = "rgba(0,0,0,0.7)";
toast.style.color = "white";
toast.style.padding = "10px 18px";
toast.style.borderRadius = "8px";
toast.style.fontSize = "16px";
toast.style.zIndex = "300";
toast.style.opacity = "0";
toast.style.transition = "opacity .3s";

container.appendChild(toast);

}

toast.textContent = msg;
toast.style.opacity = "1";

setTimeout(() => toast.style.opacity = "0", 1200);
}

/* ===========================================================
AUDIO TRACK MENU SYSTEM  (FULL PATCH)

Appears inside Settings menu like Speed / Quality / Subtitles

Auto-detects audio tracks from HLS source

Matches your existing UI styles (vp-setting-option, vp-settings-box)


---

REQUIREMENTS:

window.hlsInstance must be created when loading an .m3u8 file

Insert this after subtitle settings block in your JS file
=========================================================== */

/* ----------- 1. SELECTORS -------------- */
const audioRow   = document.getElementById("vp-audio-row");   // New row in settings
const audioMenu  = document.getElementById("vp-audio-menu");  // New menu box
const audioList  = document.getElementById("vp-audio-list");  // Container for audio items

/* Safety check */
if (!audioRow || !audioMenu || !audioList) {
console.warn("AudioTrack Menu elements not found in HTML. Did you add the HTML patch?");
}

/* ----------- 2. OPEN AUDIO MENU -------------- */
if (audioRow) {
audioRow.addEventListener("click", () => {
hideAllMenus();                 // hide speed/quality/subtitles
audioMenu.classList.remove("hidden");
buildAudioTrackMenu();          // fill menu with track options
});
}

/* ----------- 3. CREATE AUDIO MENU CONTENT -------------- */
function buildAudioTrackMenu() {
audioList.innerHTML = ""; // clear previous entries

// Check if HLS instance exists
if (!window.hlsInstance) {
const msg = document.createElement("div");
msg.className = "vp-setting-option disabled";
msg.textContent = "Audio not available";
audioList.appendChild(msg);
return;
}

const tracks = window.hlsInstance.audioTracks;

// If no audio tracks found
if (!tracks || tracks.length === 0) {
const msg = document.createElement("div");
msg.className = "vp-setting-option disabled";
msg.textContent = "No extra audio tracks";
msg.style.color="#e50914";
audioList.appendChild(msg);
return;
}

// Create an option for every audio track
tracks.forEach((track, index) => {
const item = document.createElement("div");
item.className = "vp-setting-option";

// Show name/label if available    
item.textContent = track.name || track.lang || ("Track " + (index + 1));    
    
    
// Highlight active track    
if (window.hlsInstance.audioTrack === index) {    
    item.classList.add("active");    
    item.style.color = "#e51409";    
}    

// When clicked → switch audio    
item.onclick = () => {    
        
    window.hlsInstance.audioTrack = index;    

    [...audioList.children].forEach(x => {    
        x.classList.remove("active");    
        x.style.color = "";    
    });    

    item.classList.add("active");    
    item.style.color = "#e51409";    

    showToast("Audio Track: " + item.textContent);    
    hideAllMenus();    
    audioMenu.classList.add("hidden");    
};    

audioList.appendChild(item);

});

}

/* ----------- 4. SUPPORT CLICK-OUTSIDE-TO-CLOSE -------------- */
document.addEventListener("click", (e) => {
const insideMenu =
e.target.closest("#vp-audio-menu") ||
e.target.closest("#vp-audio-row") ||
e.target.closest("#vp-settings");

if (insideMenu) return; // do not close

audioMenu.classList.add("hidden");

});

//===============================
// AUTO LOAD SUBTITLE FROM DATABASE
// =============================
//--------load subtitles --------

subRow.addEventListener("click", () => {
hideAllMenus();
subMenu.classList.remove("hidden");

subList.innerHTML = "";

/* ----- OFF OPTION ----- */
const off = document.createElement("div");
off.className = "vp-setting-option";
off.textContent = "Off";
off.onclick = () => {
[...video.textTracks].forEach(t => t.mode = "disabled");
highlightActiveSubtitle(null);
showToast("Off");
hideAllMenus();
};
subList.appendChild(off);

/* ----- LOAD FROM DEVICE OPTION ----- */
const loadLocal = document.createElement("div");
loadLocal.className = "vp-setting-option";
loadLocal.textContent = "Load from device…";

loadLocal.onclick = () => {
const input = document.createElement("input");
input.type = "file";
input.accept = ".vtt,.srt";

input.onchange = () => {      
    const file = input.files[0];      
    if (!file) return;      

    const isSrt = /\.srt$/i.test(file.name);      

    const reader = new FileReader();      
    reader.onload = () => {      
        let blob;      
        if (isSrt) {      
            let vttText = "WEBVTT\n\n" + String(reader.result)      
                .replace(/\r/g, "")      
                .replace(/(\d{2}:\d{2}:\d{2}),(\d{3})/g, "$1.$2")      
                .replace(/^\d+\s*$/gm, "");      

            blob = new Blob([vttText], { type: "text/vtt" });      
        } else {      
            blob = new Blob([reader.result], { type: "text/vtt" });      
        }      

        const url = URL.createObjectURL(blob);      

        const track = document.createElement("track");      
        track.kind = "subtitles";      
        track.label = file.name.replace(/\.(srt|vtt)$/i, "");      
        track.src = url;      
        track.default = true;      

        video.appendChild(track);      

        [...video.textTracks].forEach(t => t.mode = "disabled");      
        const last = video.textTracks[video.textTracks.length - 1];      
        if (last) last.mode = "showing";      

        showToast("Subtitles loaded!");      
        hideAllMenus();      
    };      

    reader.readAsText(file);      
};      

input.click();

};

subList.appendChild(loadLocal);

/* ----- LOAD FROM URL OPTION ----- */
const loadUrl = document.createElement("div");
loadUrl.className = "vp-setting-option";
loadUrl.textContent = "Load from URL…";

loadUrl.onclick = () => {
const url = prompt("Enter subtitle URL (.vtt or .srt):");

if (!url) return;      

fetch(url)      
    .then(res => res.text())      
    .then(text => {      
        let blob;      

        if (/\.srt$/i.test(url)) {      
            let vttText = "WEBVTT\n\n" + text      
                .replace(/\r/g, "")      
                .replace(/(\d{2}:\d{2}:\d{2}),(\d{3})/g, "$1.$2")      
                .replace(/^\d+\s*$/gm, "");      

            blob = new Blob([vttText], { type: "text/vtt" });      
        } else {      
            blob = new Blob([text], { type: "text/vtt" });      
        }      

        const blobUrl = URL.createObjectURL(blob);      

        const track = document.createElement("track");      
        track.kind = "subtitles";      
        track.label = url.split("/").pop();      
        track.src = blobUrl;      
        track.default = true;      

        video.appendChild(track);      

        [...video.textTracks].forEach(t => t.mode = "disabled");      
        const last = video.textTracks[video.textTracks.length - 1];      
        if (last) last.mode = "showing";      

        showToast("Subtitle loaded from URL!");      
        hideAllMenus();      
    })      
    .catch(err => {      
        console.error(err);      
        alert("Failed to load subtitle.");      
    });

};

subList.appendChild(loadUrl);

/* ----- EXISTING SUBTITLES FROM VIDEO ----- */

// STEP: Check which subtitle URLs actually exist
const subtitleTracks = document.querySelectorAll("track.sub-check");

subtitleTracks.forEach(tr => {
const url = tr.src;

fetch(url, { method: "HEAD" })
.then(res => {
if (!res.ok) tr.remove();   // remove missing file
})
.catch(() => tr.remove());      // also remove if network error

});

const tracks = [...video.textTracks];

if (!tracks.length) {
const msg = document.createElement("div");
msg.className = "vp-setting-option disabled";
msg.style.color = "#e50914";
msg.textContent = "No subtitles available";
subList.appendChild(msg);
return;
}

/* SORT: Active subtitle first */

const sortedTracks = [...tracks].sort((a, b) => {
return (b.mode === "showing") - (a.mode === "showing");
});

sortedTracks.forEach((t, i) => {
const item = document.createElement("div");
item.className = "vp-setting-option";
item.textContent = t.label || "Subtitle " + (i + 1);

// Mark active track
if (t.mode === "showing") {
item.classList.add("active");
item.style.color = "#e51409";
}

item.onclick = () => {      
    [...video.textTracks].forEach(x => x.mode = "disabled");      
    t.mode = "showing";      
    highlightActiveSubtitle(item);      
    hideAllMenus();      
    showToast(`Subtitle: ${item.textContent}`);     
};      

subList.appendChild(item);

});

/** Highlight helper */
function highlightActiveSubtitle(selected) {
document.querySelectorAll("#vp-sub-list .vp-setting-option")
.forEach(el => {
el.classList.remove("active");
el.style.color = "";
});

if (selected) {      
    selected.classList.add("active");      
    selected.style.color = "#e51409";      
}

}

});

/* ============================

SYNC BOX (SHOW / HIDE)
============================ */

/* OPEN sync box */
syncBtn.onclick = (e) => {
e.stopPropagation();
e.stopImmediatePropagation();  // ADD THIS

hideAllMenus();          // close settings menus
syncBox.classList.remove("hidden");
container.classList.remove("controls-hidden");   // keep controls visible

};

/* Do NOT close when clicking inside the box */
syncBox.addEventListener("click", (e) => {
e.stopPropagation();
});

/* Prevent auto-hide when sync box is open */
video.addEventListener("play", () => {
if (!syncBox.classList.contains("hidden")) {
container.classList.remove("controls-hidden");
}
});

/* ===============================
UNIVERSAL POPUP / SETTINGS CLOSE
(Works for Sync + Settings)
================================ */

document.addEventListener("click", (e) => {

const clickedInsideAnyMenu =
e.target.closest("#vp-sync-box") ||
e.target.closest("#vp-settings") ||
e.target.closest("#vp-speed-menu") ||
e.target.closest("#vp-quality-menu") ||
e.target.closest("#vp-subtitles-menu");

const clickedMenuButton =
e.target.closest("#vp-sync") ||
e.target.closest("#vp-source");

// If click is inside popup → do nothing
if (clickedInsideAnyMenu) return;

// If click is on buttons → do nothing
if (clickedMenuButton) return;

// If click is on controls area → do nothing
if (e.target.closest(".vp-controls")) return;

// CLICKED OUTSIDE → CLOSE ALL
hideAllMenus();
syncBox.classList.add("hidden");

// Restart auto-hide if video is playing
if (!video.paused && !disableAutoHide) {

if (hideTimer) clearTimeout(hideTimer);      
hideTimer = setTimeout(() => {      
    container.classList.add("controls-hidden");      
}, AUTO_HIDE_DELAY);

}

});

/* ============================
SUBTITLE DELAY LOGIC
============================ */

let subtitleDelay = 0;

const syncEdit = document.getElementById("vp-sync-edit");   // editable 0
const syncSub  = document.getElementById("vp-sync-sub");    // "0 ms"

/* Update both displays */
function updateDelayDisplay() {
syncSub.textContent  = subtitleDelay + " ms";
syncEdit.value       = subtitleDelay;
}

/* –100ms */
document.getElementById("vp-sync-minus").onclick = () => {
subtitleDelay -= 100;
updateDelayDisplay();
};

/* +100ms */
document.getElementById("vp-sync-plus").onclick = () => {
subtitleDelay += 100;
updateDelayDisplay();
};

/* Manual typing */
syncEdit.oninput = () => {
const v = parseInt(syncEdit.value);
subtitleDelay = isNaN(v) ? 0 : v;
updateDelayDisplay();
};

/* ============================
APPLY DELAY TO SUBTITLE TRACK
============================ */

function getActiveSubtitleTrack() {
return [...video.textTracks].find(t => t.mode === "showing");
}

document.getElementById("vp-sync-apply").onclick = () => {
const track = getActiveSubtitleTrack();
if (!track) {
syncBox.classList.add("hidden");
return;
}

for (let c of track.cues) {
c.startTime += subtitleDelay / 1000;
c.endTime   += subtitleDelay / 1000;
}
showToast(`Delay applied: ${subtitleDelay} ms`);
syncBox.classList.add("hidden");   // close box

};

/* ============================
RESET (RELOAD VIDEO)
============================ */
document.getElementById("vp-sync-reset").onclick = () => {
subtitleDelay = 0;
updateDelayDisplay();
showToast("Subtitle delay reset");
};

/* ============================
CANCEL
============================ */
document.getElementById("vp-sync-cancel").onclick = () => {
syncBox.classList.add("hidden");
};

/* -------- REAL VIDEO DURATION (Option B) -------- */
video.addEventListener("loadedmetadata", () => {
const realDuration = Math.floor(video.duration);
const slug = window.moviePlayer.title_slug || window.location.pathname.split("/").pop();

// Send to backend only if not saved or too small
if (realDuration > 60) { // Only if longer than 1 minute
fetch("/update_duration.php", {
method: "POST",
headers: { "Content-Type": "application/x-www-form-urlencoded" },
body: "slug=" + slug + "&duration=" + realDuration
});
}

});

/* ==========================================
SUBTITLE POSITION BASED ON SMALL LOCK ONLY
========================================== */

/* =========================
ADAPTIVE QUALITY FIXED
========================= */

async function adaptiveQualityController() {
if (!autoQualityEnabled) return;

const speed = await checkNetworkSpeed();
const buffer = video.buffered.length ?
(video.buffered.end(video.buffered.length - 1) - video.currentTime)
: 0;

/* HLS AUTO QUALITY */
if (window.hlsInstance) {

// Track previous level so toast only appears once
if (typeof window._lastHlsLevel === "undefined") {
window._lastHlsLevel = -1;
}

const current = window.hlsInstance.currentLevel;
const levels = window.hlsInstance.levels;

// If HLS has selected a new quality level → show toast ONCE
if (current !== window._lastHlsLevel && current >= 0 && levels[current]) {

const lvl = levels[current];      

showToast(`Quality: ${lvl.height}×${lvl.width}`);      
updateAutoQualityLabel();      

window._lastHlsLevel = current;  // Update tracker

}

return;

}

/* MP4 AUTO QUALITY */
if (window.videoQualities) {
let qualities = Object.keys(window.videoQualities)
.map(q => parseInt(q));

qualities.sort((a, b) => a - b);

let chosen = qualities[0];

qualities.forEach(q => {
if (speed > q / 100) chosen = q;
});

if (buffer < 3) chosen = qualities[0];

const selected = chosen.toString();

/* FIX: prevent infinite reloading */
if (video.src.includes(window.videoQualities[selected])) {
return;
}

const t = video.currentTime;
const playing = !video.paused;

video.src = window.videoQualities[selected];
video.load();
video.currentTime = t;
if (playing) video.play();

video.onloadedmetadata = () => {
const w = video.videoWidth;
const h = video.videoHeight;
window.mp4RealRes[selected] = { w, h };
showToast(`Quality: ${h}×${w}`);
};

video.addEventListener("loadedmetadata", () => {
const w = video.videoWidth;
const h = video.videoHeight;

});
}

}
setInterval(adaptiveQualityController, 4000);

/* ============================
REAL ALWAYS-ON SCREEN WAKE (HIDDEN VIDEO)
============================ */
document.addEventListener("DOMContentLoaded", () => {
const wakeHelper = document.getElementById("wakeHelper");
if (!wakeHelper) return;

wakeHelper.src = "/tiny.mp4";

function startWakeHelper() {
wakeHelper.play().catch(()=>{});
}

// Required by Chrome
document.addEventListener("pointerdown", startWakeHelper, { once: true });

});

/* ======================================
UNIVERSAL ALWAYS-ON WAKE (WebAudio)
Works on all Android phones
====================================== */

let audioCtx = null;
let silentNode = null;

function startAlwaysAwake() {
if (audioCtx) return; // already running

audioCtx = new (window.AudioContext || window.webkitAudioContext)();
silentNode = audioCtx.createOscillator();
const gain = audioCtx.createGain();

gain.gain.value = 0;        // mute completely
silentNode.connect(gain);
gain.connect(audioCtx.destination);

silentNode.start();

}

// Chrome requires 1 tap before AudioContext starts
document.addEventListener("pointerdown", () => {
startAlwaysAwake();
}, { once: true });

// If the page regains focus, resume the audio
document.addEventListener("visibilitychange", () => {
if (!document.hidden && audioCtx && audioCtx.state === "suspended") {
audioCtx.resume();
}
});

})();