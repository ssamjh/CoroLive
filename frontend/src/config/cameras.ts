// Single source of truth for camera config - add a new camera here only.
// Used at build time (routes, sitemap) and shipped to the browser via bundled
// <script> imports, so the client and the build can never drift apart.

export const API = 'https://api.corolive.nz';

export type Camera = {
  /** URL slug and archive directory name. */
  id: string;
  name: string;
  /** Restreamer stream UUID backing the HLS manifest. */
  stream: string;
  /** First day with an archive, as a Pacific/Auckland Y-m-d date. */
  start: string;
};

export const CAMERAS: Camera[] = [
  {
    id: 'whitianga',
    name: 'Whitianga',
    stream: '283795aa-816e-4d95-8ac0-05cabb67c05e',
    start: '2019-03-17',
  },
  {
    id: 'whangamata',
    name: 'Whangamata',
    stream: 'd456ab62-df95-4872-a96a-734ff455996e',
    start: '2021-03-17',
  },
  {
    id: 'thames',
    name: 'Thames',
    stream: 'afb4677d-4dc4-44e9-94dd-de5fb0b68c29',
    start: '2021-05-06',
  },
];

/** Lookup by slug, for pages that resolve a camera from ?camera=. */
export const BY_ID: Record<string, Camera> = Object.fromEntries(
  CAMERAS.map((c) => [c.id, c]),
);

export const m3u8 = (c: Camera) => `${API}/memfs/${c.stream}.m3u8`;
export const snap = (c: Camera) => `${API}/${c.id}/snap.webp`;
export const archive = (c: Camera) => `${API}/${c.id}/archive/`;

/** Camera from ?camera= on the current URL, or null when absent/unknown. */
export function queryCamera(): Camera | null {
  const id = new URLSearchParams(location.search).get('camera');
  return (id && BY_ID[id]) || null;
}
