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
// Because nothing here goes through the bundler, the playback logic is a
// hand-inlined copy of src/lib/hls.ts. Keep the two in step.

const streams = Object.fromEntries(CAMERAS.map((c) => [c.id, c.stream]));

// Light build: no alternate audio, subtitles or DRM, none of which a
// single-rendition webcam stream uses.
const HLS_JS = 'https://cdn.jsdelivr.net/npm/hls.js@1.7.1/dist/hls.light.min.js';

const body = `<div id="player" style="position:relative;width:100%;height:100%;background:#000">
    <video id="video" style="display:block;width:100%;height:100%" controls autoplay muted playsinline></video>
    <a id="watermark" href="https://corolive.nz" target="_blank" rel="noopener" style="position:absolute;right:10px;bottom:46px;opacity:0.8"><img src="https://corolive.nz/img/watermark.webp" alt="CoroLive" style="display:block;height:32px;width:auto;border:0"></a>
</div>

<script src="${HLS_JS}"></script>

<script>
    var API = '${API}';
    var STREAMS = ${JSON.stringify(streams)};
    var RETRY_MS = 4000;
    var NATIVE_HLS = 'application/vnd.apple.mpegurl';

    var camera = new URLSearchParams(location.search).get('camera');
    var player = document.getElementById('player');
    var video = document.getElementById('video');

    if (!Object.prototype.hasOwnProperty.call(STREAMS, camera)) {
        // Unknown camera: stay blank rather than showing empty player chrome.
        player.style.display = 'none';
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

        // hls.js needs Media Source Extensions. Desktop, Android and iPad have
        // them; an iPhone only exposes the Managed Media Source variant, where
        // native HLS is better tested and easier on the battery. canPlayType()
        // deliberately does not decide this - Chromium answers "maybe" for the
        // HLS MIME type without being able to play it, which would send every
        // Chrome user down the native path and show them a black box.
        var hasMse = typeof window.MediaSource !== 'undefined';

        if (!hasMse || !window.Hls || !window.Hls.isSupported()) {
            if (video.canPlayType(NATIVE_HLS)) {
                var load = function () { video.src = source; video.load(); play(); };
                video.addEventListener('error', function () { retry(load); });
                load();
            }
        } else {
            var Hls = window.Hls;
            // liveDurationInfinity makes the controls render a live stream
            // rather than a seekable VOD timeline.
            var hls = new Hls({ liveDurationInfinity: true });

            hls.on(Hls.Events.ERROR, function (_event, data) {
                if (!data.fatal) { return; }
                if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                    hls.recoverMediaError();
                    return;
                }
                retry(function () { hls.startLoad(); });
            });

            hls.on(Hls.Events.MANIFEST_PARSED, play);
            hls.loadSource(source);
            hls.attachMedia(video);
        }
    }
</script>
`;

export const GET: APIRoute = () =>
  new Response(body, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
