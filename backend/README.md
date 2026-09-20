# CoroLive Backend

Captures camera frames, archives them into a per-day SQLite database, and encodes
a nightly timelapse. It's a single script (`corolive.py`) running in a container.

## How it works

`corolive.py` runs one loop that wakes once a minute. Each minute, for every
camera in `config/cameras.yaml`, it does:

| Job       | When                          | Output                                   |
|-----------|-------------------------------|------------------------------------------|
| `snap`    | every minute                  | `<camera>/snap.webp` (live frontend frame)|
| `archive` | every even minute, from astronomical dawn to 15 min after astronomical dusk | a frame in today's `images.db` + `index.json` + `thumbnail.avif` |
| `animate` | once for all cameras at `COROLIVE_ANIMATE_AT` | today's `animation.webm`          |

The camera coordinates and elevation in metres live in `config/cameras.yaml`.
Archive windows are calculated locally with Astral in the `Pacific/Auckland`
timezone; no network lookup is involved. By default capture begins at
astronomical dawn (sun 18 degrees below the horizon, when the first faint light
appears) and ends 15 minutes after astronomical dusk (when evening twilight is
over). Both twilight angles and the
before/after margins can be overridden with the environment variables shown in
`compose.yml`.

Archive timestamps are always even minutes. The check is enforced where the
database row is written, including for manually invoked archive jobs. The live
`snap.webp` still refreshes every minute and is overwritten in place.

`animate` runs in a background thread so the slow nightly encode never blocks the
per-minute `snap`/`archive` jobs.

Run the schedule tests with `python -m unittest -v test_corolive.py` from this
directory after installing the Dockerfile's Python dependencies.

## Layout

```
backend/
  corolive.py           # the whole backend — one file, one loop
  config/
    cameras.yaml         # cameras + URLs/credentials (gitignored)
    cameras.example.yaml # template
  data/                  # everything it produces (gitignored)
  Dockerfile
  docker-compose.yml
```

## Data layout (under `data/`)

```
data/<camera>/snap.webp
data/<camera>/archive/<YYYY>/<MM>/<DD>/images.db
data/<camera>/archive/<YYYY>/<MM>/<DD>/index.json
data/<camera>/archive/<YYYY>/<MM>/<DD>/thumbnail.avif
data/<camera>/archive/<YYYY>/<MM>/<DD>/animation.webm
```

## Historical archives

The web container serves 2019–2025 directly from the Docker host at
`/mnt/nfs/corolive/<camera>/archive/`. That directory is bind-mounted read-only
at the same path inside the container. The current year remains in `data/`.

When a completed year is moved into the historical archive, extend the year
range in `nginx.conf` so requests for that year use the host mount.
