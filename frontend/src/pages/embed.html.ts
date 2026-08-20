import type { APIRoute } from 'astro';
import { CAMERAS, API } from '../config/cameras';

// NOTE: deliberately a bare fragment - no doctype, no stylesheet, no layout.
// Third-party iframes rely on this quirks-mode layout, so adding either would
// resize the player on sites we don't control. Leave it as-is.
//
// It is a build-time endpoint rather than a .astro page purely because Astro
// injects a doctype into every .astro page, which would switch the iframe into
// standards mode and change how existing embeds render.

const streams = Object.fromEntries(CAMERAS.map((c) => [c.id, c.stream]));

const body = `<div id="player"></div>

<script src="https://cdn.jsdelivr.net/npm/@clappr/player@0.14.3/dist/clappr.min.js"></script>

<script>
    var API = '${API}';
    var STREAMS = ${JSON.stringify(streams)};

    var camera = new URLSearchParams(location.search).get('camera');

    if (Object.prototype.hasOwnProperty.call(STREAMS, camera)) {
        var player = new window.Clappr.Player({
            source: API + '/memfs/' + STREAMS[camera] + '.m3u8',
            poster: API + '/' + camera + '/snap.webp?rand=' + Math.random(),
            watermark: 'https://corolive.nz/img/watermark.webp',
            watermarkLink: 'https://corolive.nz',
            parentId: '#player',
            position: 'bottom-right',
            mute: true,
            autoPlay: true,
            actualLiveTime: true,
            hideVolumeBar: true,
            width: '100%',
            height: '100%',
            events: {
                onReady: function () {
                    var plugin = this.getPlugin('click_to_pause');
                    plugin && plugin.disable();
                },
                // Clappr reads live-vs-VOD off the HLS manifest, which lands after the
                // media control has already rendered - so it renders the VOD seek bar and
                // never revisits it. Re-render once playback starts to get the LIVE badge.
                onPlay: function () {
                    this.core && this.core.mediaControl && this.core.mediaControl.render();
                },
            },
        });
    }
</script>
`;

export const GET: APIRoute = () =>
  new Response(body, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
