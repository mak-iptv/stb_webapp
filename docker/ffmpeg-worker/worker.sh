#!/bin/bash

echo "--------------------------------------------"
echo "   FFmpeg Worker Cloud-ready (S3 Upload)"
echo "--------------------------------------------"
echo "Start Time: $(date)"
echo ""

# -----------------------------
# ENV CONFIG
# -----------------------------
REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
REDIS_QUEUE="${REDIS_QUEUE:-ffmpeg_jobs}"
AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-}"
AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-}"
AWS_BUCKET="${AWS_BUCKET:-iptv-bucket}"
AWS_REGION="${AWS_REGION:-us-east-1}"
RETRY_DELAY="${RETRY_DELAY:-5}"

# -----------------------------
# Funksioni për të nxjerrë punë nga Redis
# -----------------------------
fetch_job() {
    job=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" BLPOP "$REDIS_QUEUE" 0)
    job_data=$(echo "$job" | awk '{for(i=2;i<=NF;i++) printf $i " ";}')
    echo "$job_data"
}

# -----------------------------
# Funksioni për të ekzekutuar FFmpeg dhe upload në S3
# -----------------------------
run_ffmpeg() {
    local input_url="$1"
    local output_prefix="$2"
    local ffmpeg_args="$3"

    tmp_dir=$(mktemp -d)
    echo "[INFO] Output temp dir: $tmp_dir"

    #
