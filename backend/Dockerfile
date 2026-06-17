FROM python:3.13-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg imagemagick webp curl libheif1 libaom3 tzdata \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN pip install --no-cache-dir pyyaml

COPY corolive.py .

ENV COROLIVE_BASE_DIR=/data \
    COROLIVE_CONFIG=/config/cameras.yaml \
    PYTHONUNBUFFERED=1

CMD ["python", "corolive.py"]
