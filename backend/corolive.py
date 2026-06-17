#!/usr/bin/env python3
"""
CoroLive backend — one file, one loop.

Captures camera frames, archives them into a per-day SQLite database, and
encodes a nightly timelapse. Replaces the old cron + scripts/image.py setup.

The loop wakes once a minute and, for each camera, does:
  snap      every minute            — save a fresh live frame for the frontend
  archive   every 2 min, 05:00–22:00 — store a frame in today's database

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
from datetime import datetime
from pathlib import Path

import yaml

# ---------------------------------------------------------------------------
# Settings — change the schedule here.
# ---------------------------------------------------------------------------
BASE_DIR = Path(os.environ.get("COROLIVE_BASE_DIR", "/data"))
CONFIG_PATH = Path(os.environ.get("COROLIVE_CONFIG", "/config/cameras.yaml"))

ARCHIVE_EVERY_MIN = 2     # archive a frame every N minutes
ARCHIVE_START_HOUR = 5    # archive only between these hours (inclusive)
ARCHIVE_END_HOUR = 22
THUMBNAIL_NOON = 12 * 60 + 1   # minutes-since-midnight the thumbnail aims for
ANIMATE_AT = os.environ.get("COROLIVE_ANIMATE_AT", "22:10")  # encode all timelapses at this local time
ANIMATE_POLL_SEC = 30     # how often to log progress while an encode runs


def load_cameras():
    """Return the list of cameras from cameras.yaml. Re-read each loop so edits
    take effect without a restart."""
    return yaml.safe_load(CONFIG_PATH.read_text())["cameras"]


# ---------------------------------------------------------------------------
# Small helpers.
# ---------------------------------------------------------------------------
def day_dir(camera, now):
    return BASE_DIR / camera / "archive" / f"{now.year}" / f"{now.month:02d}" / f"{now.day:02d}"


def ts_to_minutes(ts):
    h, m = ts.split(":")
    return int(h) * 60 + int(m)


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
def snap(name, url):
    """Save a fresh live frame: <camera>/snap.webp"""
    out = BASE_DIR / name / "snap.webp"
    out.parent.mkdir(parents=True, exist_ok=True)
    tmp = Path(tempfile.mkdtemp())
    try:
        jpg = tmp / "in.jpg"
        webp = tmp / "out.webp"
        fetch_jpg(url, jpg)
        to_webp(jpg, webp)
        shutil.move(str(webp), str(out))
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def archive(name, url):
    """Store one frame in today's database, and refresh index.json + thumbnail.
    Does nothing outside the archive window or if this minute is already stored."""
    now = datetime.now()
    minutes = now.hour * 60 + now.minute
    if not (ARCHIVE_START_HOUR * 60 <= minutes <= ARCHIVE_END_HOUR * 60):
        return

    ts = now.strftime("%H:%M")
    out_dir = day_dir(name, now)
    conn = open_db(out_dir / "images.db")
    try:
        if conn.execute("SELECT 1 FROM frames WHERE timestamp = ?", (ts,)).fetchone():
            return  # already have this minute

        tmp = Path(tempfile.mkdtemp())
        try:
            jpg = tmp / "in.jpg"
            avif = tmp / "out.avif"
            fetch_jpg(url, jpg)
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


def run_minute(now):
    """Do everything that should happen this minute, for every camera."""
    for cam in load_cameras():
        name, url = cam["name"], cam["url"]
        try:
            snap(name, url)
            if now.minute % ARCHIVE_EVERY_MIN == 0:
                archive(name, url)
        except Exception as e:
            print(f"{now:%H:%M} [{name}] error: {e}", flush=True)
    # once a day, encode every camera's timelapse back-to-back in the background
    if now.strftime("%H:%M") == ANIMATE_AT:
        threading.Thread(target=run_all_animations, daemon=True).start()


def loop():
    print(f"CoroLive backend started — data: {BASE_DIR}, config: {CONFIG_PATH}", flush=True)
    while True:
        now = datetime.now()
        run_minute(now)
        # sleep until the top of the next minute
        slept = datetime.now()
        time.sleep(max(1, 60 - slept.second - slept.microsecond / 1e6))


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
        archive(name, cam["url"])
    else:
        animate(name)
    print("done", flush=True)


if __name__ == "__main__":
    main()
