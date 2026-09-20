# CoroLive Backend

Captures camera frames, archives them into a per-day SQLite database, and encodes
a nightly timelapse. It's a single script (`corolive.py`) running in a container.

## How it works

`corolive.py` runs one loop that wakes once a minute. Each minute, for every
camera in `config/cameras.yaml`, it does:

| Job       | When                          | Output                                   |
|-----------|-------------------------------|------------------------------------------|
| `snap`    | every minute                  | `<camera>/snap.webp` (live frontend frame)|
| `archive` | every 2 min, 05:00–22:00      | a frame in today's `images.db` + `index.json` + `thumbnail.avif` |
| `animate` | once, at the camera's `animation_at` | today's `animation.webm`          |

The schedule is just constants at the top of `corolive.py`. The camera list lives
in `config/cameras.yaml`.

`animate` runs in a background thread so the slow nightly encode never blocks the
per-minute `snap`/`archive` jobs.

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
