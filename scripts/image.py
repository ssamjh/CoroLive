#!/usr/bin/env python3
"""
image.py — live capture and end-of-day processing for the per-day SQLite archive.

Usage:
  python3 image.py snap      <camera> <url>   # fetch + write live snap.avif for frontend
  python3 image.py api       <camera> <url>   # fetch + archive frame in DB, update index.json + thumbnail.avif
  python3 image.py animation <camera>          # encode animation.webm for today

Thumbnail logic (managed during api calls):
  Before 12:00 — thumbnail.avif is overwritten with each new frame (latest = closest to noon so far)
  After  12:00 — thumbnail.avif is left untouched (preserves the last pre-noon frame)
  Fallback     — if thumbnail.avif is missing after noon (camera was down all morning),
                 the DB frame closest to 12:00 is used instead

Environment:
  COROLIVE_BASE_DIR   Root path for camera data (default: /var/www/corolive.nz/api)

Output layout:
  <BASE_DIR>/<camera>/snap.avif
  <BASE_DIR>/<camera>/archive/<YYYY>/<MM>/<DD>/images.db
  <BASE_DIR>/<camera>/archive/<YYYY>/<MM>/<DD>/index.json
  <BASE_DIR>/<camera>/archive/<YYYY>/<MM>/<DD>/thumbnail.avif
  <BASE_DIR>/<camera>/archive/<YYYY>/<MM>/<DD>/animation.webm
"""

import json
import os
import sqlite3
import subprocess
import sys
import tempfile
import shutil
from datetime import datetime
from pathlib import Path

BASE_DIR = Path(os.environ.get("COROLIVE_BASE_DIR", "/var/www/corolive.nz/api"))

NOON_MINUTES = 12 * 60 + 1


def check_dependencies():
    missing = [
        p for p in ("ffmpeg", "convert", "cwebp", "curl") if shutil.which(p) is None
    ]
    if missing:
        print(
            f"Error: missing required programs: {', '.join(missing)}", file=sys.stderr
        )
        sys.exit(1)


def day_dir(camera: str, now: datetime) -> Path:
    return BASE_DIR / camera / "archive" / f"{now.year}" / f"{now.month:02d}" / f"{now.day:02d}"


def ts_to_minutes(ts: str) -> int:
    h, m = ts.split(":")
    return int(h) * 60 + int(m)


def open_db(db_path: Path) -> sqlite3.Connection:
    db_path.parent.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(db_path)
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS frames (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            filename  TEXT NOT NULL,
            timestamp TEXT,
            ext       TEXT NOT NULL,
            data      BLOB NOT NULL
        )
    """
    )
    conn.execute(
        "CREATE INDEX IF NOT EXISTS idx_frames_timestamp ON frames (timestamp)"
    )
    conn.commit()
    return conn


def to_webp(src: Path, out: Path) -> bool:
    result = subprocess.run(
        [
            "cwebp",
            str(src),
            "-quiet",
            "-preset",
            "photo",
            "-resize",
            "1920",
            "1080",
            "-o",
            str(out),
        ],
        capture_output=True,
    )
    return result.returncode == 0


def to_avif(src: Path, out: Path) -> bool:
    """Convert src to AVIF at 720p using the exact settings from image.sh."""
    result = subprocess.run(
        [
            "convert",
            str(src),
            "-resize",
            "1280x720>",
            "-quality",
            "53",
            "-define",
            "avif:compression-level=4",
            "-define",
            "avif:speed=0",
            "-define",
            "avif:tiling=1",
            str(out),
        ],
        capture_output=True,
    )
    return result.returncode == 0


def write_index_json(conn: sqlite3.Connection, index_path: Path) -> None:
    filenames = [
        row[0] for row in conn.execute("SELECT filename FROM frames ORDER BY timestamp")
    ]
    index_path.write_text(json.dumps(filenames))


def fetch_jpg(url: str, dest: Path) -> None:
    result = subprocess.run(
        [
            "curl",
            "--connect-timeout",
            "2",
            "--retry",
            "4",
            "--retry-delay",
            "1",
            "-s",
            "-S",
            "-f",
            "-o",
            str(dest),
            url,
        ],
        capture_output=True,
    )
    if result.returncode != 0:
        print(f"curl failed: {result.stderr.decode().strip()}", file=sys.stderr)
        sys.exit(1)


def cmd_snap(camera: str, url: str) -> None:
    snap_path = BASE_DIR / camera / "snap.webp"
    snap_path.parent.mkdir(parents=True, exist_ok=True)

    tmp = Path(tempfile.mkdtemp())
    try:
        src_jpg = tmp / "snap.jpg"
        fetch_jpg(url, src_jpg)

        out_webp = tmp / "snap.webp"
        if not to_webp(src_jpg, out_webp):
            print("WebP conversion failed.", file=sys.stderr)
            sys.exit(1)

        shutil.move(str(out_webp), str(snap_path))
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    print(f"Updated {snap_path} ({snap_path.stat().st_size / 1024:.0f} KB)")


def cmd_api(camera: str, url: str) -> None:
    now = datetime.now()
    ts = now.strftime("%H:%M")
    minutes = ts_to_minutes(ts)

    if minutes < 5 * 60 or minutes > 22 * 60:
        print(f"Outside 05:00–22:00 ({ts}), skipping.")
        return

    fname = ts.replace(":", "-") + ".avif"
    out_dir = day_dir(camera, now)
    db_path = out_dir / "images.db"

    conn = open_db(db_path)
    existing = conn.execute(
        "SELECT 1 FROM frames WHERE timestamp = ?", (ts,)
    ).fetchone()
    if existing:
        print(f"Frame {ts} already in db, skipping.")
        conn.close()
        return

    tmp = Path(tempfile.mkdtemp())
    try:
        src_jpg = tmp / "snap.jpg"
        fetch_jpg(url, src_jpg)

        out_avif = tmp / "snap.avif"
        if not to_avif(src_jpg, out_avif):
            print("AVIF conversion failed.", file=sys.stderr)
            sys.exit(1)

        data = out_avif.read_bytes()
    finally:
        shutil.rmtree(tmp)

    conn.execute(
        "INSERT INTO frames (filename, timestamp, ext, data) VALUES (?, ?, ?, ?)",
        (fname, ts, ".avif", data),
    )
    conn.commit()
    write_index_json(conn, out_dir / "index.json")
    update_thumbnail(conn, out_dir, data, minutes, ts)
    conn.close()
    print(f"Stored {fname} ({len(data) / 1024:.0f} KB) → {db_path}")


def update_thumbnail(
    conn: sqlite3.Connection,
    out_dir: Path,
    current_data: bytes,
    minutes: int,
    ts: str,
) -> None:
    thumb_path = out_dir / "thumbnail.avif"
    if minutes <= NOON_MINUTES:
        thumb_path.write_bytes(current_data)
        print(f"Updated {thumb_path} ({len(current_data) / 1024:.0f} KB)")
    elif not thumb_path.exists():
        rows = conn.execute(
            "SELECT timestamp, data FROM frames ORDER BY timestamp"
        ).fetchall()
        if rows:
            _, best_data = min(
                rows, key=lambda r: abs(ts_to_minutes(r[0]) - NOON_MINUTES)
            )
            thumb_path.write_bytes(best_data)
            print(f"Wrote fallback {thumb_path} ({len(best_data) / 1024:.0f} KB)")


def cmd_animation(camera: str) -> None:
    now = datetime.now()
    out_dir = day_dir(camera, now)
    db_path = out_dir / "images.db"
    webm_path = out_dir / "animation.webm"

    if not db_path.exists():
        print(f"No db found at {db_path}", file=sys.stderr)
        sys.exit(1)

    conn = sqlite3.connect(db_path)
    rows = conn.execute(
        "SELECT timestamp, ext, data FROM frames ORDER BY timestamp"
    ).fetchall()
    conn.close()

    if not rows:
        print("No frames in db.", file=sys.stderr)
        sys.exit(1)

    tmp = Path(tempfile.mkdtemp())
    try:
        file_list = tmp / "file_list.txt"
        with open(file_list, "w") as fl:
            for i, (ts, ext, data) in enumerate(rows, 1):
                frame_path = tmp / f"{i:05d}{ext}"
                frame_path.write_bytes(data)
                fl.write(f"file '{frame_path}'\n")

        base_cmd = [
            "ffmpeg",
            "-loglevel",
            "error",
            "-r",
            "12",
            "-f",
            "concat",
            "-safe",
            "0",
            "-i",
            str(file_list),
            "-c:v",
            "libvpx-vp9",
            "-b:v",
            "0",
            "-crf",
            "38",
            "-deadline",
            "good",
            "-cpu-used",
            "5",
            "-vf",
            "format=yuv420p",
        ]
        subprocess.run(
            base_cmd + ["-pass", "1", "-an", "-f", "null", os.devnull],
            check=True,
            capture_output=True,
            cwd=tmp,
        )
        subprocess.run(
            base_cmd + ["-pass", "2", "-an", str(webm_path)],
            check=True,
            capture_output=True,
            cwd=tmp,
        )
    except subprocess.CalledProcessError as e:
        print(f"ffmpeg failed: {e.stderr.decode().strip()}", file=sys.stderr)
        sys.exit(1)
    finally:
        shutil.rmtree(tmp)

    size_mb = webm_path.stat().st_size / (1024 * 1024)
    print(f"Wrote {webm_path} ({len(rows)} frames, {size_mb:.1f} MB)")


def main():
    check_dependencies()

    if len(sys.argv) < 3:
        print(__doc__, file=sys.stderr)
        sys.exit(1)

    mode, camera = sys.argv[1], sys.argv[2]
    rest = sys.argv[3:]

    if mode == "snap":
        if not rest:
            print("Usage: image.py snap <camera> <url>", file=sys.stderr)
            sys.exit(1)
        cmd_snap(camera, rest[0])
    elif mode == "api":
        if not rest:
            print("Usage: image.py api <camera> <url>", file=sys.stderr)
            sys.exit(1)
        cmd_api(camera, rest[0])
    elif mode == "animation":
        cmd_animation(camera)
    else:
        print(
            f"Unknown mode: {mode!r}. Expected snap, api, or animation.",
            file=sys.stderr,
        )
        sys.exit(1)


if __name__ == "__main__":
    main()
