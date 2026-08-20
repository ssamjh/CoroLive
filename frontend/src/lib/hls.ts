// The light build drops alternate audio, subtitles and DRM, none of which a
// single-rendition webcam stream uses - it is roughly a third of the size.
// Types come from src/types/hls-light.d.ts.
import Hls from 'hls.js/light';

// Live HLS playback for a camera that is expected to stay up for years. The
// iframe embed in src/pages/embed.html.ts carries a hand-inlined copy of this
// logic - it ships as a bare fragment with no bundler, so it cannot import.
// Keep the two in step.

/** How long to wait before re-arming a stream that dropped out. */
const RETRY_MS = 4000;

const NATIVE_HLS = 'application/vnd.apple.mpegurl';

const CONFIG = {
  // Report the stream as endless so the controls render a live stream instead
  // of a seekable VOD timeline with a bogus duration.
  liveDurationInfinity: true,
};

/** Muted autoplay, which is the only kind browsers allow unprompted. */
function play(video: HTMLVideoElement) {
  video.muted = true;
  void video.play().catch(() => {
    // Autoplay refused - the poster stays up and the controls still work.
  });
}

/**
 * Hand the manifest straight to WebKit, which plays HLS natively - keeping
 * AirPlay and picture-in-picture working and holding the live edge better
 * than MSE does on those devices.
 */
function attachNative(video: HTMLVideoElement, source: string) {
  let timer: ReturnType<typeof setTimeout> | null = null;

  const load = () => {
    video.src = source;
    video.load();
    play(video);
  };

  video.addEventListener('error', () => {
    if (timer) return;
    timer = setTimeout(() => {
      timer = null;
      load();
    }, RETRY_MS);
  });

  load();
}

function attachHlsJs(video: HTMLVideoElement, source: string) {
  const hls = new Hls(CONFIG);
  let timer: ReturnType<typeof setTimeout> | null = null;

  hls.on(Hls.Events.ERROR, (_event, data) => {
    // hls.js retries non-fatal errors on its own.
    if (!data.fatal) return;

    if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
      hls.recoverMediaError();
      return;
    }

    // A fatal network error on a 24/7 camera usually means the stream is
    // restarting rather than gone, so keep re-arming the load.
    if (timer) return;
    timer = setTimeout(() => {
      timer = null;
      hls.startLoad();
    }, RETRY_MS);
  });

  hls.on(Hls.Events.MANIFEST_PARSED, () => play(video));

  hls.loadSource(source);
  hls.attachMedia(video);
}

/**
 * Play `source` in `video`, picking whichever playback path the device has.
 *
 * hls.js needs Media Source Extensions. Desktop browsers, Android and iPad all
 * expose them, so they get hls.js. An iPhone only ever exposes the Managed
 * Media Source variant, where native HLS is better tested and easier on the
 * battery - those get the manifest handed straight to WebKit.
 *
 * canPlayType() deliberately does not decide this: Chromium answers "maybe"
 * for the HLS MIME type without being able to play it, so trusting it would
 * send every Chrome user down the native path and show them a black box.
 */
export function attachStream(video: HTMLVideoElement, source: string) {
  if (typeof MediaSource !== 'undefined' && Hls.isSupported()) {
    attachHlsJs(video, source);
  } else if (video.canPlayType(NATIVE_HLS)) {
    attachNative(video, source);
  }
}
