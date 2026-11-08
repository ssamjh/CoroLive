FROM python:3.14-slim

# libheif-plugin-aomenc is required for ImageMagick to ENCODE AVIF: Debian splits
# libheif's codecs into plugin packages, and without the aom encoder plugin `convert`
# reports "no encode delegate for this image format `AVIF'" and silently writes a
# non-AVIF file. It pulls in libheif1 + libaom3 as dependencies.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg imagemagick webp curl libheif-plugin-aomenc tzdata \
    && rm -rf /var/lib/apt/lists/*

COPY --from=ghcr.io/astral-sh/uv:0.13 /uv /usr/local/bin/uv

WORKDIR /app

# Install the locked dependencies into /app/.venv using the image's Python.
ENV UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    UV_PYTHON_DOWNLOADS=never \
    UV_NO_CACHE=1
COPY pyproject.toml uv.lock .python-version ./
RUN uv sync --locked --no-dev --no-install-project

COPY corolive.py .

ENV COROLIVE_BASE_DIR=/data \
    COROLIVE_CONFIG=/config/cameras.yaml \
    PYTHONUNBUFFERED=1 \
    PATH="/app/.venv/bin:$PATH"

CMD ["python", "corolive.py"]
