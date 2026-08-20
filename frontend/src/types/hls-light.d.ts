// hls.js exports a `light` build - the same player minus alternate audio,
// subtitles and DRM - but ships no type declarations for it. The API we use is
// identical to the full build, so re-export those types.
declare module 'hls.js/light' {
  export * from 'hls.js';
  export { default } from 'hls.js';
}
