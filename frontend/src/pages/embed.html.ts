import type { APIRoute } from 'astro';
import { CAMERAS, API } from '../config/cameras';

// NOTE: deliberately a bare fragment - no doctype, no stylesheet, no layout.
// Third-party iframes rely on this quirks-mode layout, so adding either would
// resize the player on sites we don't control. Leave it as-is.
//
// It is a build-time endpoint rather than a .astro page purely because Astro
// injects a doctype into every .astro page, which would switch the iframe into
// standards mode and change how existing embeds render.
//
// Because nothing here goes through the bundler, the playback and control
// logic is a hand-inlined copy of src/lib/player.ts. Keep the two in step.

const streams = Object.fromEntries(CAMERAS.map((c) => [c.id, c.stream]));

// Light build: no alternate audio, subtitles or DRM, none of which a
// single-rendition webcam stream uses.
const HLS_JS = 'https://cdn.jsdelivr.net/npm/hls.js@1.7.1/dist/hls.light.min.js';

const icon = (path: string) =>
  `<svg viewBox="0 0 24 24" fill="currentColor" style="display:block;width:16px;height:16px"><path d="${path}"/></svg>`;

const ICONS = {
  play: icon('M7 4v16l13-8z'),
  stop: icon('M6 6h12v12H6z'),
  expand: icon('M4 4h6v2H6v4H4zm10 0h6v6h-2V6h-4zM4 14h2v4h4v2H4zm14 0h2v6h-6v-2h4z'),
  compress: icon('M10 4v6H4V8h4V4zm10 4v2h-6V4h2v4zM4 14h6v6H8v-4H4zm12 2v4h-2v-6h6v2z'),
};

const BUTTON_STYLE = [
  'display:inline-flex',
  'align-items:center',
  'justify-content:center',
  'padding:6px 10px',
  'border:1px solid rgba(255,255,255,0.25)',
  'border-radius:8px',
  'background:rgba(255,255,255,0.05)',
  'color:#fff',
  'cursor:pointer',
  'font:inherit',
  'line-height:0',
].join(';');

// Only #id-scoped rules, so nothing here can reach the host page's layout or
// the player box. Quirks mode depends on the missing doctype alone, which this
// does not change.
const styles = `<style>
    #corolive-controls button:hover { background: rgba(255,255,255,0.15) !important; }
    #corolive-controls button:focus-visible { outline: 2px solid #0dcaf0; outline-offset: 2px; }
</style>`;

const body = `<div id="player" style="position:relative;width:100%;height:100%;background:#000">
    <video id="video" style="display:block;width:100%;height:100%" autoplay muted playsinline></video>
    <a id="watermark" href="https://corolive.nz" target="_blank" rel="noopener" style="position:absolute;right:10px;bottom:58px;opacity:0.8"><img src="https://corolive.nz/img/watermark.webp" alt="CoroLive" style="display:block;height:32px;width:auto;border:0"></a>
    <div id="corolive-controls" style="position:absolute;left:0;right:0;bottom:0;display:flex;align-items:center;padding:32px 12px 12px;opacity:0;transition:opacity 0.3s;background:linear-gradient(to top,rgba(0,0,0,0.8),rgba(0,0,0,0))">
        <button type="button" id="play" aria-label="Play" style="${BUTTON_STYLE}"></button>
        <button type="button" id="fullscreen" aria-label="Fullscreen" style="${BUTTON_STYLE};margin-left:auto"></button>
    </div>
</div>

${styles}

<script src="${HLS_JS}"></script>

<script>
    var API = '${API}';
    var STREAMS = ${JSON.stringify(streams)};
    var ICONS = ${JSON.stringify(ICONS)};
    var RETRY_MS = 4000;
    var HIDE_MS = 3000;
    var NATIVE_HLS = 'application/vnd.apple.mpegurl';

    var camera = new URLSearchParams(location.search).get('camera');
    var stage = document.getElementById('player');
    var video = document.getElementById('video');
    var controls = document.getElementById('corolive-controls');
    var playBtn = document.getElementById('play');
    var fsBtn = document.getElementById('fullscreen');

    if (!Object.prototype.hasOwnProperty.call(STREAMS, camera)) {
        // Unknown camera: stay blank rather than showing empty player chrome.
        stage.style.display = 'none';
    } else {
        var source = API + '/memfs/' + STREAMS[camera] + '.m3u8';
        video.poster = API + '/' + camera + '/snap.webp?rand=' + Math.random();

        var play = function () {
            video.muted = true;
            var p = video.play();
            if (p) { p.catch(function () {}); }
        };

        var timer = null;
        var retry = function (fn) {
            if (timer) { return; }
            timer = setTimeout(function () { timer = null; fn(); }, RETRY_MS);
        };

        // ---- Controls -------------------------------------------------------
        // No volume and no scrubber: the feed is live and muted, so both would
        // be dead UI. Play/stop and fullscreen are the only real actions.
        var hideTimer = null;
        var show = function () {
            controls.style.opacity = '1';
            if (hideTimer) { clearTimeout(hideTimer); }
            hideTimer = null;
            // Never hide the chrome while paused - if autoplay was refused,
            // the play button is the only way to start it.
            if (!video.paused) {
                hideTimer = setTimeout(function () { controls.style.opacity = '0'; }, HIDE_MS);
            }
        };

        stage.addEventListener('mousemove', show);
        stage.addEventListener('touchstart', show, { passive: true });
        stage.addEventListener('focusin', show);
        stage.addEventListener('mouseleave', function () {
            if (!video.paused) { controls.style.opacity = '0'; }
        });

        // Assigned once a playback path is chosen below.
        var stream = null;

        playBtn.addEventListener('click', function () {
            if (!stream) { return; }
            // Start rejoins the live edge rather than resuming where it stopped.
            if (video.paused) { stream.start(); } else { stream.stop(); }
        });

        var syncPlayIcon = function () {
            playBtn.innerHTML = video.paused ? ICONS.play : ICONS.stop;
            playBtn.setAttribute('aria-label', video.paused ? 'Play' : 'Stop');
            show();
        };
        video.addEventListener('play', syncPlayIcon);
        video.addEventListener('pause', syncPlayIcon);
        syncPlayIcon();

        fsBtn.innerHTML = ICONS.expand;
        fsBtn.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                return;
            }
            if (stage.requestFullscreen) {
                stage.requestFullscreen();
                return;
            }
            // An iPhone has no Element.requestFullscreen - only the video can
            // go fullscreen there, via WebKit's own native player.
            if (video.webkitEnterFullscreen) {
                try { video.webkitEnterFullscreen(); } catch (e) {}
            }
        });
        document.addEventListener('fullscreenchange', function () {
            var full = document.fullscreenElement === stage;
            fsBtn.innerHTML = full ? ICONS.compress : ICONS.expand;
            fsBtn.setAttribute('aria-label', full ? 'Exit fullscreen' : 'Fullscreen');
        });

        // ---- Playback -------------------------------------------------------
        // hls.js needs Media Source Extensions. Desktop, Android and iPad have
        // them; an iPhone only exposes the Managed Media Source variant, where
        // native HLS is better tested and easier on the battery. canPlayType()
        // deliberately does not decide this - Chromium answers "maybe" for the
        // HLS MIME type without being able to play it, which would send every
        // Chrome user down the native path and show them a black box.
        var hasMse = typeof window.MediaSource !== 'undefined';
        var stopped = false;
        var clearRetry = function () {
            if (timer) { clearTimeout(timer); }
            timer = null;
        };

        if (!hasMse || !window.Hls || !window.Hls.isSupported()) {
            if (video.canPlayType(NATIVE_HLS)) {
                var load = function () { video.src = source; video.load(); play(); };
                // Tearing down the source on stop fires an error of its own;
                // ignore it, or the retry would undo the stop.
                video.addEventListener('error', function () {
                    if (stopped) { return; }
                    retry(function () { if (!stopped) { load(); } });
                });
                load();
                stream = {
                    stop: function () {
                        stopped = true;
                        clearRetry();
                        video.pause();
                        video.removeAttribute('src');
                        video.load();
                    },
                    start: function () {
                        stopped = false;
                        clearRetry();
                        // Native HLS joins a live playlist at its live edge.
                        load();
                    }
                };
            }
        } else {
            var Hls = window.Hls;
            // liveDurationInfinity stops anything presenting a duration or a
            // seekable range for what is a live feed.
            var hls = new Hls({ liveDurationInfinity: true });

            hls.on(Hls.Events.ERROR, function (_event, data) {
                if (stopped || !data.fatal) { return; }
                if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                    hls.recoverMediaError();
                    return;
                }
                retry(function () { if (!stopped) { hls.startLoad(); } });
            });

            hls.on(Hls.Events.MANIFEST_PARSED, play);
            hls.loadSource(source);
            hls.attachMedia(video);

            stream = {
                stop: function () {
                    stopped = true;
                    clearRetry();
                    hls.stopLoad();
                    video.pause();
                },
                start: function () {
                    stopped = false;
                    clearRetry();
                    // -1 restarts at the live edge rather than the stopped
                    // position; the buffer still holds the old footage, so seek
                    // across once a playlist says where live now is.
                    hls.startLoad(-1);
                    hls.once(Hls.Events.LEVEL_UPDATED, function () {
                        var live = hls.liveSyncPosition;
                        if (live !== null && isFinite(live)) { video.currentTime = live; }
                        play();
                    });
                    play();
                }
            };
        }
    }
</script>
`;

export const GET: APIRoute = () =>
  new Response(body, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
