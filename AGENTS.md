## Model routing
- You own planning, architecture, and final verification. Don't write bulk code yourself.
- Break work into independent, clearly scoped tasks with success criteria and hand each to a subagent.
- Review subagent output before reporting done.

## Project overview

CoroLive has two independently deployed parts:

- `frontend/` is an Astro + Tailwind static site deployed to Cloudflare Pages.
- `backend/` is a Dockerized Python service that captures camera images, stores
  archives, encodes timelapses, and exposes the generated files through nginx.

Keep the two parts independently testable. Do not introduce runtime coupling or
share dependencies between them.

## Repository map

- `frontend/src/config/cameras.ts`: frontend camera registry and API helpers. It
  drives generated camera pages, navigation, players, and sitemap entries.
- `frontend/src/pages/`: Astro routes. `[camera].astro` generates one page per
  camera; timelapse and image-only routes select a camera with `?camera=`.
- `frontend/src/components/`: shared page and player components.
- `frontend/src/lib/`: browser playback and Pacific/Auckland date logic.
- `frontend/src/styles/global.css`: Tailwind theme and shared component styles.
- `frontend/src/pages/embed.html.ts`: deliberately hand-generated embed output.
- `frontend/scripts/`: lightweight Node test scripts.
- `backend/corolive.py`: the complete capture and scheduling service.
- `backend/test_corolive.py`: backend schedule and archive-invariant tests.
- `backend/config/cameras.yaml`: deployment-only camera URLs, credentials, and
  coordinates. Never commit this file; use `cameras.example.yaml` as the shape.
- `backend/data/`: generated snapshots and archives. Never commit it.
- `backend/nginx.conf`: read-only file-serving and historical archive routing.

## Working approach

- Read the nearest README and relevant source before changing behavior.
- Keep changes narrowly scoped and preserve unrelated work in the worktree.
- Prefer existing helpers, tokens, and configuration sources over duplicated
  constants or parallel implementations.
- Add or update tests when changing date calculations, scheduling, archive
  rules, URL generation, or other logic with stable expected behavior.
- Do not commit credentials, camera snapshot URLs, generated media, build
  output, `node_modules`, `.astro`, or `dist`.

## Frontend commands

Run frontend commands from `frontend/`:

```sh
npm install
npm run dev
npm run check
npm test
npm run build
npm run preview
```

Cloudflare Pages uses `frontend` as its root, `npm run build` as the build
command, and `dist` as the output directory.

For a normal frontend change, verify with `npm run check`, `npm test`, and
`npm run build`. Use `npm run preview` when behavior needs browser inspection.

## Frontend conventions and invariants

- Follow the existing TypeScript style: two-space indentation, semicolons,
  single quotes, and strict type checking.
- Preserve `trailingSlash: 'never'` and `build.format: 'file'`; Cloudflare Pages
  relies on them to retain the site's existing extensionless URLs.
- Add frontend cameras only in `src/config/cameras.ts`. Use a unique lowercase
  `id`, the correct stream UUID, and the first available archive date. Keep
  `BY_ID` and URL helpers derived from `CAMERAS`; do not duplicate camera lists.
- Treat archive dates as plain `YYYY-MM-DD` calendar strings in
  `Pacific/Auckland`. Use `src/lib/nzdate.ts`; avoid local-time `Date`
  arithmetic that can drift across daylight-saving transitions.
- Keep `embed.html.ts` as a bare, doctype-free fragment because existing
  third-party embeds depend on its quirks-mode layout. It cannot import the
  bundled player, so keep its inlined playback behavior aligned with
  `src/lib/player.ts` when either changes.
- Keep `sitemap.xml.ts` hand-generated unless query-string camera pages are
  replaced; the standard sitemap integration cannot represent those URLs.
- Put shared theme values in `src/styles/global.css`. Preserve automatic
  `prefers-color-scheme` theming and the semantic Tailwind color tokens.
- The live feed is muted and live-only. Its controls intentionally start/stop
  at the live edge rather than pause/resume; do not add volume or seek UI
  without an explicit product change.

## Backend commands

Run backend tests from `backend/` after installing the dependencies listed in
the Dockerfile:

```sh
python -m unittest -v test_corolive.py
```

One-shot jobs require a valid camera configuration and data directory:

```sh
python corolive.py snap <camera>
python corolive.py archive <camera>
python corolive.py animate <camera>
```

The deployed stack is defined in `backend/compose.yml`. Do not start, rebuild,
or restart deployment services unless the task calls for it.

## Backend conventions and invariants

- `corolive.py` intentionally remains a small, single-service design. Avoid
  introducing a framework or splitting it up without a clear operational need.
- The service reloads `config/cameras.yaml` each scheduling loop. New cameras
  need a name, snapshot URL, latitude, longitude, and optional elevation.
  Latitude must be in `[-90, 90]` and longitude in `[-180, 180]`.
- Each camera gets a live `snap.webp` every minute. Archive frames are accepted
  only on even minutes inside the calculated solar window.
- Preserve the schedule check inside `save_archive()` as a hard invariant,
  including for manual jobs and any new callers.
- Fetch each camera once per minute and reuse that image for live and archive
  output; do not double the requests made to a camera.
- Archive output stays under `data/<camera>/archive/YYYY/MM/DD/` as
  `images.db`, `index.json`, `thumbnail.avif`, and `animation.webm`.
- Use `COROLIVE_TIMEZONE`/`TZ` (normally `Pacific/Auckland`) for scheduling and
  solar calculations. Keep environment overrides documented in `compose.yml`.
- Encode nightly animations after the final summer archive window, one camera
  at a time, in a background thread. Heavy ffmpeg jobs must not overlap or
  block minute-level captures.
- Use temporary directories for intermediate media and clean them in `finally`
  blocks. Publish only fully encoded snapshots.
- When a completed year moves to `/mnt/nfs/corolive`, extend the historical
  year regex in `nginx.conf` and keep that location above the generic archive
  location so it wins nginx's regex ordering.
- Preserve nginx's read-only, GET/HEAD-only, non-root, low-resource security
  posture.

## Cross-system camera changes

The frontend registry and backend YAML serve different purposes and must agree:

- The frontend owns public IDs, display names, stream UUIDs, and archive start
  dates.
- The backend owns capture URLs/credentials and geographic coordinates.
- The public camera ID must match the backend data directory name and nginx
  routing. Update nginx's explicit camera regex when adding a camera.

After a camera change, verify generated frontend routes and sitemap entries,
stream and snapshot URLs, backend archive paths, and nginx routing.

## Final verification

- Run the smallest relevant checks while iterating, then the full checks for
  every part changed.
- For frontend work: `npm run check`, `npm test`, and `npm run build`.
- For backend work: `python -m unittest -v test_corolive.py`.
- For deployment or generated-output changes, inspect the resulting files or
  container configuration directly.
- Review `git diff` and `git status` for accidental generated files, secrets,
  unrelated edits, and missing tests before reporting completion.
