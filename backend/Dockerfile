FROM python:3.13-slim

# libheif-plugin-aomenc is required for ImageMagick to ENCODE AVIF: Debian splits
# libheif's codecs into plugin packages, and without the aom encoder plugin `convert`
# reports "no encode delegate for this image format `AVIF'" and silently writes a
# non-AVIF file. It pulls in libheif1 + libaom3 as dependencies.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg imagemagick webp curl libheif-plugin-aomenc tzdata \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN pip install --no-cache-dir pyyaml

COPY corolive.py .

ENV COROLIVE_BASE_DIR=/data \
    COROLIVE_CONFIG=/config/cameras.yaml \
    PYTHONUNBUFFERED=1

CMD ["python", "corolive.py"]
