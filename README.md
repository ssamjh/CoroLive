# CoroLive

The Coromandel's live webcam network — [corolive.nz](https://corolive.nz)

```
frontend/   the website: Astro + Tailwind static build, deployed to Cloudflare Pages
backend/    camera ingest and restreaming (see backend/README.md)
scripts/    snapshot capture and end-of-day archive processing, run by cron on the camera hosts
```

`frontend/` is self-contained — its own `package.json`, and nothing outside it
is needed to build or deploy the site.

## Developing

```sh
cd frontend
npm install
npm run dev      # dev server with hot reload on http://localhost:4321
npm run build    # static build into frontend/dist/
npm run preview  # serve dist/ exactly as it will be deployed
npm run check    # Astro + TypeScript diagnostics
npm test         # self-check for the timelapse date logic
```

## Cloudflare Pages settings

| Setting | Value |
| --- | --- |
| Build command | `npm run build` |
| Build output directory | `dist` |
| Root directory | `frontend` |

Pages serves `foo.html` at `/foo`, which is why the build is configured with
`format: 'file'` and `trailingSlash: 'never'` — every existing URL keeps working
unchanged.

## Adding a camera

`frontend/src/config/cameras.ts` is the single source of truth. Adding an entry
there gives you the live page, the picker card, the navbar menu, the timelapse
and image-only pages, and the sitemap entry — no other file needs touching.

## Frontend layout

```
src/config/     camera list, build metadata
src/layouts/    Base.astro — <head>, navbar, footer wrapper
src/components/ Navbar, Footer, Player
src/lib/        nzdate.ts — Pacific/Auckland date handling for the archive
src/pages/      one file per route; [camera].astro fans out per camera
src/styles/     Tailwind theme tokens and the handful of component classes
public/         copied verbatim into the build
scripts/        node self-checks
```

Two routes are deliberately unusual:

- `embed.html.ts` is a build-time endpoint rather than a page, because Astro
  injects a doctype into every `.astro` page and third-party iframes depend on
  the existing quirks-mode layout.
- `sitemap.xml.ts` is hand-rolled rather than `@astrojs/sitemap`, because the
  timelapse and image-only pages are only reachable with a `?camera=` query
  string.

## Theming

Light and dark both come from `prefers-color-scheme` — there is no toggle and no
theme script. Surface colours are CSS variables in
`frontend/src/styles/global.css` registered as Tailwind tokens (`bg-surface`,
`text-muted`, `border-edge`), and the brand cyan is `--color-brand-500`
(`#0dcaf0`).
