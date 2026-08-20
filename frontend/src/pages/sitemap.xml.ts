import type { APIRoute } from 'astro';
import { CAMERAS } from '../config/cameras';

// Hand-rolled rather than @astrojs/sitemap: the timelapse and image-only pages
// are only reachable with a ?camera= query string, which the integration can't
// know about. Generating from CAMERAS keeps it in sync when a camera is added.
const SITE = 'https://corolive.nz';

const entries: [string, string][] = [
  ['/', '1.00'],
  ...CAMERAS.flatMap((c): [string, string][] => [
    [`/${c.id}`, '0.80'],
    [`/timelapse?camera=${c.id}`, '0.50'],
    [`/image-only?camera=${c.id}`, '0.10'],
  ]),
  ['/embedding', '0.05'],
];

const body = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries
  .map(
    ([path, priority]) =>
      `  <url>\n    <loc>${SITE}${path.replace(/&/g, '&amp;')}</loc>\n    <priority>${priority}</priority>\n  </url>`,
  )
  .join('\n')}
</urlset>
`;

export const GET: APIRoute = () =>
  new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
