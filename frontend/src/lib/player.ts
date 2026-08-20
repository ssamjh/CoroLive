// The light build drops alternate audio, subtitles and DRM, none of which a
// single-rendition webcam stream uses - it is roughly a third of the size.
// Types come from src/types/hls-light.d.ts.
import Hls from 'hls.js/light';

// Live HLS playback plus the minimal control chrome, for a camera expected to
// stay up for years. The iframe embed in src/pages/embed.html.ts carries a
// hand-inlined copy of all of this - it ships as a bare fragment with no
// bundler, so it cannot import. Keep the two in step.
//
// There is deliberately no volume control and no scrubber: the stream is live
// and plays muted, so a seek bar would be meaningless and a volume slider
// would be dead UI. Play/stop and fullscreen are the only real actions.

/** How long to wait before re-arming a stream that dropped out. */
const RETRY_MS = 4000;

/** How long the chrome stays up after the pointer goes quiet. */
const HIDE_MS = 3000;

const NATIVE_HLS = 'application/vnd.apple.mpegurl';

const CONFIG = {
  // Report the stream as endless so nothing tries to present a duration or a
  // seekable range for what is a live feed.
  liveDurationInfinity: true,
};

// Same glyphs as the timelapse player chrome, so the two match.
export const ICONS = {
  play: '<svg viewBox="0 0 24 24" fill="currentColor" class="size-4" style="width:16px;height:16px"><path d="M7 4v16l13-8z"/></svg>',
  stop: '<svg viewBox="0 0 24 24" fill="currentColor" class="size-4" style="width:16px;height:16px"><path d="M6 6h12v12H6z"/></svg>',
  expand:
    '<svg viewBox="0 0 24 24" fill="currentColor" class="size-4" style="width:16px;height:16px"><path d="M4 4h6v2H6v4H4zm10 0h6v6h-2V6h-4zM4 14h2v4h4v2H4zm14 0h2v6h-6v-2h4z"/></svg>',
  compress:
    '<svg viewBox="0 0 24 24" fill="currentColor" class="size-4" style="width:16px;height:16px"><path d="M10 4v6H4V8h4V4zm10 4v2h-6V4h2v4zM4 14h6v6H8v-4H4zm12 2v4h-2v-6h6v2z"/></svg>',
};

/** Muted autoplay, which is the only kind browsers allow unprompted. */
function play(video: HTMLVideoElement) {
  video.muted = true;
  void video.play().catch(() => {
    // Autoplay refused - the poster stays up and the play button takes over.
  });
}

/**
 * A live feed has stop/start rather than pause/resume: stopping gives up the
 * position entirely and starting rejoins at the live edge, so the viewer never
 * watches stale footage while believing it is current.
 */
export type Stream = {
  stop: () => void;
  start: () => void;
};

/**
 * Hand the manifest straight to WebKit, which plays HLS natively - keeping
 * AirPlay working and holding the live edge better than MSE does there.
 */
function attachNative(video: HTMLVideoElement, source: string): Stream {
  let timer: ReturnType<typeof setTimeout> | null = null;
  let stopped = false;

  const load = () => {
    video.src = source;
    video.load();
    play(video);
  };

  const clearRetry = () => {
    if (timer) clearTimeout(timer);
    timer = null;
  };

  video.addEventListener('error', () => {
    // Tearing down the source on stop fires an error of its own; ignore it,
    // or the retry would immediately undo the stop.
    if (stopped || timer) return;
    timer = setTimeout(() => {
      timer = null;
      if (!stopped) load();
    }, RETRY_MS);
  });

  load();

  return {
    stop() {
      stopped = true;
      clearRetry();
      video.pause();
      // Drop the source so the connection closes rather than buffering on in
      // the background, and so the next start is a fresh load.
      video.removeAttribute('src');
      video.load();
    },
    start() {
      stopped = false;
      clearRetry();
      // Native HLS joins a live playlist at its live edge, so a plain reload
      // is already the correct "start from now".
      load();
    },
  };
}

function attachHlsJs(video: HTMLVideoElement, source: string): Stream {
  const hls = new Hls(CONFIG);
  let timer: ReturnType<typeof setTimeout> | null = null;
  let stopped = false;

  const clearRetry = () => {
    if (timer) clearTimeout(timer);
    timer = null;
  };

  hls.on(Hls.Events.ERROR, (_event, data) => {
    // hls.js retries non-fatal errors on its own.
    if (stopped || !data.fatal) return;

    if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
      hls.recoverMediaError();
      return;
    }

    // A fatal network error on a 24/7 camera usually means the stream is
    // restarting rather than gone, so keep re-arming the load.
    if (timer) return;
    timer = setTimeout(() => {
      timer = null;
      if (!stopped) hls.startLoad();
    }, RETRY_MS);
  });

  hls.on(Hls.Events.MANIFEST_PARSED, () => play(video));

  hls.loadSource(source);
  hls.attachMedia(video);

  return {
    stop() {
      stopped = true;
      clearRetry();
      hls.stopLoad();
      video.pause();
    },
    start() {
      stopped = false;
      clearRetry();
      // -1 restarts at the live edge rather than the stopped position. The
      // buffer still holds the old footage, so seek across to the new live
      // point as soon as a playlist arrives and tells us where that is.
      hls.startLoad(-1);
      hls.once(Hls.Events.LEVEL_UPDATED, () => {
        const live = hls.liveSyncPosition;
        if (live !== null && Number.isFinite(live)) video.currentTime = live;
        play(video);
      });
      play(video);
    },
  };
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
export function attachStream(video: HTMLVideoElement, source: string): Stream | null {
  if (typeof MediaSource !== 'undefined' && Hls.isSupported()) {
    return attachHlsJs(video, source);
  }
  if (video.canPlayType(NATIVE_HLS)) {
    return attachNative(video, source);
  }
  return null;
}

type FullscreenVideo = HTMLVideoElement & { webkitEnterFullscreen?: () => void };

/**
 * Fullscreen the whole stage so our own chrome stays usable inside it.
 *
 * An iPhone has no Element.requestFullscreen - only a video element can go
 * fullscreen there, handing over to WebKit's own native player - so it falls
 * back to that. iPad and desktop take the standard path.
 */
export function toggleFullscreen(stage: HTMLElement, video: HTMLVideoElement) {
  if (document.fullscreenElement) {
    void document.exitFullscreen().catch(() => {});
    return;
  }

  if (stage.requestFullscreen) {
    void stage.requestFullscreen().catch(() => {});
    return;
  }

  const native = video as FullscreenVideo;
  if (native.webkitEnterFullscreen) {
    try {
      native.webkitEnterFullscreen();
    } catch {
      // Needs loaded metadata; the next tap will land.
    }
  }
}

/**
 * Wire the two controls and the auto-hiding chrome.
 *
 * `setOpacity` is passed in rather than toggling a class, so the same logic
 * drives the Tailwind-styled site player and the unstyled iframe embed.
 */
export function wireControls(opts: {
  stage: HTMLElement;
  video: HTMLVideoElement;
  playBtn: HTMLButtonElement;
  fsBtn: HTMLButtonElement;
  stream: Stream | null;
  setOpacity: (visible: boolean) => void;
}) {
  const { stage, video, playBtn, fsBtn, stream, setOpacity } = opts;
  let hideTimer: ReturnType<typeof setTimeout> | null = null;

  const show = () => {
    setOpacity(true);
    if (hideTimer) clearTimeout(hideTimer);
    hideTimer = null;
    // Never hide the chrome while paused - if autoplay was refused, the play
    // button is the only way to start it.
    if (!video.paused) {
      hideTimer = setTimeout(() => setOpacity(false), HIDE_MS);
    }
  };

  stage.addEventListener('mousemove', show);
  stage.addEventListener('touchstart', show, { passive: true });
  stage.addEventListener('mouseleave', () => {
    if (!video.paused) setOpacity(false);
  });
  // Keyboard users need it back when focus lands on a control.
  stage.addEventListener('focusin', show);

  playBtn.addEventListener('click', () => {
    if (!stream) return;
    // Start rejoins the live edge rather than resuming where it stopped.
    if (video.paused) stream.start();
    else stream.stop();
  });

  // Track the element rather than the click, so the icon stays honest when
  // playback stops for any other reason.
  const syncPlayIcon = () => {
    playBtn.innerHTML = video.paused ? ICONS.play : ICONS.stop;
    playBtn.setAttribute('aria-label', video.paused ? 'Play' : 'Stop');
    show();
  };
  video.addEventListener('play', syncPlayIcon);
  video.addEventListener('pause', syncPlayIcon);
  syncPlayIcon();

  fsBtn.addEventListener('click', () => toggleFullscreen(stage, video));
  document.addEventListener('fullscreenchange', () => {
    const full = document.fullscreenElement === stage;
    fsBtn.innerHTML = full ? ICONS.compress : ICONS.expand;
    fsBtn.setAttribute('aria-label', full ? 'Exit fullscreen' : 'Fullscreen');
  });
}
