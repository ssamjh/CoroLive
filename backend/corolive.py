#!/usr/bin/env python3
"""
CoroLive backend — one file, one loop.

Captures camera frames, archives them into a per-day SQLite database, and
encodes a nightly timelapse.

The loop wakes once a minute and, for each camera, does:
  snap      every minute                 — save a fresh live frame for the frontend
  archive   every even daylight minute   — store a frame in today's database

Once a day, at ANIMATE_AT, every camera's timelapse is encoded one after
another in a background thread — each runs in its own thread that we monitor
to completion before starting the next, so the heavy ffmpeg passes never
overlap and never block the per-minute snaps.

Run:
  python corolive.py                  # the scheduling loop (what the container runs)
  python corolive.py snap    whitianga   # run one job once, for testing
  python corolive.py archive whitianga
  python corolive.py animate whitianga

Config: config/cameras.yaml   (gitignored — holds camera URLs + credentials)
Data:   written under $COROLIVE_BASE_DIR (default /data)
"""

import json
import os
import shutil
import sqlite3
import subprocess
import sys
import tempfile
import threading
import time
from datetime import datetime, timedelta
from functools import lru_cache
from pathlib import Path
from zoneinfo import ZoneInfo

import yaml
from astral import Observer
from astral.sun import dawn, dusk

# ---------------------------------------------------------------------------
# Settings — override the schedule with environment variables if needed.
# ---------------------------------------------------------------------------
BASE_DIR = Path(os.environ.get("COROLIVE_BASE_DIR", "/data"))
CONFIG_PATH = Path(os.environ.get("COROLIVE_CONFIG", "/config/cameras.yaml"))

ARCHIVE_EVERY_MIN = 2
TIMEZONE_NAME = os.environ.get("COROLIVE_TIMEZONE", "Pacific/Auckland")
LOCAL_TIMEZONE = ZoneInfo(TIMEZONE_NAME)
DAWN_DEPRESSION = float(os.environ.get("COROLIVE_DAWN_DEPRESSION", "18"))
DUSK_DEPRESSION = float(os.environ.get("COROLIVE_DUSK_DEPRESSION", "18"))
ARCHIVE_BEFORE_DAWN_MIN = int(os.environ.get("COROLIVE_BEFORE_DAWN_MIN", "0"))
ARCHIVE_AFTER_DUSK_MIN = int(os.environ.get("COROLIVE_AFTER_DUSK_MIN", "15"))
THUMBNAIL_NOON = 12 * 60 + 1   # minutes-since-midnight the thumbnail aims for
ANIMATE_AT = os.environ.get("COROLIVE_ANIMATE_AT", "23:00")  # safely after astronomical dusk
ANIMATE_POLL_SEC = 30     # how often to log progress while an encode runs

# Keeps existing, gitignored cameras.yaml files working after deployment. New
# cameras must provide latitude and longitude explicitly in cameras.yaml.
DEFAULT_CAMERA_COORDINATES = {
    "whitianga": (-36.8333, 175.7000),
    "whangamata": (-37.2085, 175.8705),
    "thames": (-37.1383, 175.5401),
}


def load_cameras():
    """Return the list of cameras from cameras.yaml. Re-read each loop so edits
    take effect without a restart."""
    cameras = yaml.safe_load(CONFIG_PATH.read_text())["cameras"]
    for camera in cameras:
        fallback = DEFAULT_CAMERA_COORDINATES.get(camera.get("name"))
        if fallback:
            camera.setdefault("latitude", fallback[0])
            camera.setdefault("longitude", fallback[1])
        if "latitude" not in camera or "longitude" not in camera:
            raise ValueError(
                f"camera {camera.get('name', '<unnamed>')} needs latitude and longitude"
            )
        camera["latitude"] = float(camera["latitude"])
        camera["longitude"] = float(camera["longitude"])
        camera["elevation"] = float(camera.get("elevation", 0))
        if not -90 <= camera["latitude"] <= 90:
            raise ValueError(f"invalid latitude for camera {camera['name']}")
        if not -180 <= camera["longitude"] <= 180:
            raise ValueError(f"invalid longitude for camera {camera['name']}")
    return cameras


# ---------------------------------------------------------------------------
# Small helpers.
# ---------------------------------------------------------------------------
def day_dir(camera, now):
    return BASE_DIR / camera / "archive" / f"{now.year}" / f"{now.month:02d}" / f"{now.day:02d}"


def ts_to_minutes(ts):
    h, m = ts.split(":")
    return int(h) * 60 + int(m)


def local_now():
    return datetime.now(LOCAL_TIMEZONE)


def floor_to_archive_minute(value):
    """Round down to an even minute so the first stored timestamp is even."""
    value = value.replace(second=0, microsecond=0)
    return value - timedelta(minutes=value.minute % ARCHIVE_EVERY_MIN)


def ceil_to_archive_minute(value):
    """Round up to an even minute so the configured daylight margin is kept."""
    rounded = value.replace(second=0, microsecond=0)
    if value > rounded:
        rounded += timedelta(minutes=1)
    remainder = rounded.minute % ARCHIVE_EVERY_MIN
    if remainder:
        rounded += timedelta(minutes=ARCHIVE_EVERY_MIN - remainder)
    return rounded


@lru_cache(maxsize=64)
def solar_archive_window(day, latitude, longitude, elevation=0.0):
    """Return the local, even-minute archive window for an observer and date."""
    observer = Observer(latitude=latitude, longitude=longitude, elevation=elevation)
    start = dawn(
        observer, date=day, depression=DAWN_DEPRESSION, tzinfo=LOCAL_TIMEZONE
    ) - timedelta(minutes=ARCHIVE_BEFORE_DAWN_MIN)
    end = dusk(
        observer, date=day, depression=DUSK_DEPRESSION, tzinfo=LOCAL_TIMEZONE
    ) + timedelta(minutes=ARCHIVE_AFTER_DUSK_MIN)
    return floor_to_archive_minute(start), ceil_to_archive_minute(end)


def archive_window(camera, day):
    return solar_archive_window(
        day, camera["latitude"], camera["longitude"], camera.get("elevation", 0.0)
    )


def should_archive(camera, now):
    """Hard archive invariant: inside daylight window and on an even minute."""
    if now.tzinfo is None:
        now = now.replace(tzinfo=LOCAL_TIMEZONE)
    else:
        now = now.astimezone(LOCAL_TIMEZONE)
    if now.minute % ARCHIVE_EVERY_MIN != 0:
        return False
    start, end = archive_window(camera, now.date())
    minute = now.replace(second=0, microsecond=0)
    return start <= minute <= end


def fetch_jpg(url, dest):
    """Download a snapshot to dest. Bounded timeout/retries so it can never hang."""
    r = subprocess.run(
        ["curl", "--connect-timeout", "2", "--retry", "4", "--retry-delay", "1",
         "-s", "-S", "-f", "-o", str(dest), url],
        capture_output=True,
    )
    if r.returncode != 0:
        raise RuntimeError(f"curl failed: {r.stderr.decode().strip()}")


def to_webp(src, out):
    """1080p WebP for the live frontend frame."""
    r = subprocess.run(
        ["cwebp", str(src), "-quiet", "-preset", "photo",
         "-resize", "1920", "1080", "-o", str(out)],
        capture_output=True,
    )
    if r.returncode != 0:
        raise RuntimeError("cwebp failed")


def to_avif(src, out):
    """720p AVIF for the archive (small, good quality)."""
    r = subprocess.run(
        ["convert", str(src), "-resize", "1280x720>", "-quality", "53",
         "-define", "avif:compression-level=4", "-define", "avif:speed=0",
         "-define", "avif:tiling=1", str(out)],
        capture_output=True,
    )
    if r.returncode != 0:
        raise RuntimeError("AVIF conversion failed")


def open_db(db_path):
    db_path.parent.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(db_path)
    conn.execute(
        "CREATE TABLE IF NOT EXISTS frames ("
        "id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT NOT NULL, "
        "timestamp TEXT, ext TEXT NOT NULL, data BLOB NOT NULL)"
    )
    conn.execute("CREATE INDEX IF NOT EXISTS idx_frames_timestamp ON frames (timestamp)")
    conn.commit()
    return conn


def write_index_json(conn, index_path):
    names = [row[0] for row in conn.execute("SELECT filename FROM frames ORDER BY timestamp")]
    index_path.write_text(json.dumps(names))


def update_thumbnail(conn, out_dir, current_data, minutes):
    """Thumbnail = the frame closest to noon.
    Before noon, keep overwriting with each new frame (latest is closest so far).
    After noon, leave it alone — unless it's missing, then pick the closest frame."""
    thumb = out_dir / "thumbnail.avif"
    if minutes <= THUMBNAIL_NOON:
        thumb.write_bytes(current_data)
    elif not thumb.exists():
        rows = conn.execute("SELECT timestamp, data FROM frames ORDER BY timestamp").fetchall()
        if rows:
            _, best = min(rows, key=lambda r: abs(ts_to_minutes(r[0]) - THUMBNAIL_NOON))
            thumb.write_bytes(best)


# ---------------------------------------------------------------------------
# The three jobs.
# ---------------------------------------------------------------------------
def save_snap(name, jpg):
    """Encode an already-fetched JPEG into the live frame: <camera>/snap.webp"""
    out = BASE_DIR / name / "snap.webp"
    out.parent.mkdir(parents=True, exist_ok=True)
    tmp = Path(tempfile.mkdtemp())
    try:
        webp = tmp / "out.webp"
        to_webp(jpg, webp)
        shutil.move(str(webp), str(out))
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def save_archive(name, jpg, camera, now=None):
    """Store an already-fetched JPEG in today's database, and refresh
    index.json + thumbnail. Does nothing outside the solar archive window, on
    odd minutes, or if this minute is already stored."""
    now = now or local_now()
    minutes = now.hour * 60 + now.minute
    if not should_archive(camera, now):
        return

    ts = now.strftime("%H:%M")
    out_dir = day_dir(name, now)
    conn = open_db(out_dir / "images.db")
    try:
        if conn.execute("SELECT 1 FROM frames WHERE timestamp = ?", (ts,)).fetchone():
            return  # already have this minute

        tmp = Path(tempfile.mkdtemp())
        try:
            avif = tmp / "out.avif"
            to_avif(jpg, avif)
            data = avif.read_bytes()
        finally:
            shutil.rmtree(tmp, ignore_errors=True)

        conn.execute(
            "INSERT INTO frames (filename, timestamp, ext, data) VALUES (?, ?, ?, ?)",
            (ts.replace(":", "-") + ".avif", ts, ".avif", data),
        )
        conn.commit()
        write_index_json(conn, out_dir / "index.json")
        update_thumbnail(conn, out_dir, data, minutes)
    finally:
        conn.close()


def snap(name, url):
    """Fetch a frame and save it as the live <camera>/snap.webp."""
    tmp = Path(tempfile.mkdtemp())
    try:
        jpg = tmp / "in.jpg"
        fetch_jpg(url, jpg)
        save_snap(name, jpg)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def archive(camera):
    """Fetch a frame and store it in today's database."""
    tmp = Path(tempfile.mkdtemp())
    try:
        jpg = tmp / "in.jpg"
        fetch_jpg(camera["url"], jpg)
        save_archive(camera["name"], jpg, camera, local_now())
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def animate(name):
    """Encode today's frames into animation.webm."""
    now = datetime.now()
    out_dir = day_dir(name, now)
    db_path = out_dir / "images.db"
    if not db_path.exists():
        raise RuntimeError(f"no database at {db_path}")

    conn = sqlite3.connect(db_path)
    rows = conn.execute("SELECT ext, data FROM frames ORDER BY timestamp").fetchall()
    conn.close()
    if not rows:
        raise RuntimeError("no frames to animate")

    tmp = Path(tempfile.mkdtemp())
    try:
        listing = tmp / "files.txt"
        with open(listing, "w") as fl:
            for i, (ext, data) in enumerate(rows, 1):
                frame = tmp / f"{i:05d}{ext}"
                frame.write_bytes(data)
                fl.write(f"file '{frame}'\n")

        cmd = ["ffmpeg", "-loglevel", "error", "-r", "12", "-f", "concat",
               "-safe", "0", "-i", str(listing), "-c:v", "libvpx-vp9", "-b:v", "0",
               "-crf", "38", "-deadline", "good", "-cpu-used", "5",
               "-vf", "format=yuv420p"]
        # two-pass encode
        subprocess.run(cmd + ["-pass", "1", "-an", "-f", "null", os.devnull],
                       check=True, capture_output=True, cwd=tmp)
        subprocess.run(cmd + ["-pass", "2", "-an", str(out_dir / "animation.webm")],
                       check=True, capture_output=True, cwd=tmp)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


# ---------------------------------------------------------------------------
# The loop.
# ---------------------------------------------------------------------------
def run_animate(name):
    """Encode one camera's timelapse. Runs in its own thread so we can monitor
    it to completion; the thread just waits on the ffmpeg subprocess."""
    try:
        animate(name)
        print(f"[{name}] animation done", flush=True)
    except Exception as e:
        print(f"[{name}] animation error: {e}", flush=True)


def run_all_animations():
    """Encode every camera's timelapse one after another. Each runs in its own
    thread that we monitor to completion before starting the next, so the heavy
    ffmpeg passes never overlap. Runs in a background thread itself, so it never
    blocks the per-minute snaps/archives."""
    for cam in load_cameras():
        name = cam["name"]
        start = datetime.now()
        t = threading.Thread(target=run_animate, args=(name,), daemon=True)
        t.start()
        print(f"[{name}] animation started", flush=True)
        while t.is_alive():
            t.join(timeout=ANIMATE_POLL_SEC)
            if t.is_alive():
                elapsed = int((datetime.now() - start).total_seconds())
                print(f"[{name}] still encoding… {elapsed}s", flush=True)
    print("all animations done", flush=True)


def run_camera_minute(camera, now):
    """Grab this minute's frame for one camera with a single fetch: encode it as
    the live snap, and on the archive cadence also store it in the database."""
    tmp = Path(tempfile.mkdtemp())
    name = camera["name"]
    try:
        jpg = tmp / "in.jpg"
        fetch_jpg(camera["url"], jpg)
        save_snap(name, jpg)
        # save_archive repeats the cadence check deliberately: direct and future
        # call sites can never persist an odd-minute archive timestamp.
        save_archive(name, jpg, camera, now)
    except Exception as e:
        print(f"{now:%H:%M} [{name}] error: {e}", flush=True)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def run_minute(now):
    """Do everything that should happen this minute, for every camera. Each
    camera's image grabbing runs in its own thread so they happen at once and a
    slow camera never delays the others."""
    threads = []
    for cam in load_cameras():
        t = threading.Thread(
            target=run_camera_minute, args=(cam, now), daemon=True
        )
        t.start()
        threads.append(t)
    for t in threads:
        t.join()
    # once a day, encode every camera's timelapse back-to-back in the background
    if now.strftime("%H:%M") == ANIMATE_AT:
        threading.Thread(target=run_all_animations, daemon=True).start()


def loop():
    print(f"CoroLive backend started — data: {BASE_DIR}, config: {CONFIG_PATH}", flush=True)
    # grab a live frame straight away so the frontend isn't blank until the next
    # minute; archives stay on their aligned schedule below.
    for cam in load_cameras():
        threading.Thread(
            target=snap, args=(cam["name"], cam["url"]), daemon=True
        ).start()
    while True:
        # wait for the top of the next minute, then do that minute's work — so a
        # mid-minute start doesn't grab an archive frame off-schedule.
        now = local_now()
        time.sleep(max(1, 60 - now.second - now.microsecond / 1e6))
        run_minute(local_now())


# ---------------------------------------------------------------------------
# Entry point: no args = run the loop; args = run one job once (for testing).
# ---------------------------------------------------------------------------
def main():
    if len(sys.argv) == 1:
        loop()
        return

    if len(sys.argv) != 3 or sys.argv[1] not in ("snap", "archive", "animate"):
        print("usage: corolive.py [snap|archive|animate] <camera>", file=sys.stderr)
        sys.exit(1)

    job, name = sys.argv[1], sys.argv[2]
    cam = next((c for c in load_cameras() if c["name"] == name), None)
    if cam is None:
        print(f"unknown camera: {name}", file=sys.stderr)
        sys.exit(1)

    if job == "snap":
        snap(name, cam["url"])
    elif job == "archive":
        archive(cam)
    else:
        animate(name)
    print("done", flush=True)


if __name__ == "__main__":
    main()
