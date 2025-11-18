#!/bin/bash

echo "--------------------------------------------"
echo "   FFmpeg Worker Cloud-ready (S3 Upload)"
echo "--------------------------------------------"
echo "Start Time: $(date)"
echo ""

# ENV CONFIG
REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
REDIS_QUEUE="${REDIS_QUEUE:-ffmpeg_jobs}"
AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-}"
AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-}"
AWS_BUCKET="${AWS_BUCKET:-iptv-bucket}"
AWS_REGION="${AWS_REGION:-us-east-1}"
RETRY_DELAY="${RETRY_DELAY:-5}"

# Funksioni për të nxjerrë punë nga Redis
fetch_job() {
    job=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" BLPOP "$REDIS_QUEUE" 0)
    job_data=$(echo "$job" | awk '{for(i=2;i<=NF;i++) printf $i " ";}')
    echo "$job_data"
}

# Funksioni për të ekzekutuar FFmpeg dhe upload në S3
run_ffmpeg() {
    local input_url="$1"
    local output_prefix="$2"
    local ffmpeg_args="$3"

    tmp_dir=$(mktemp -d)
    echo "[INFO] Output temp dir: $tmp_dir"

    ffmpeg -hide_banner -loglevel error -y \
        -i "$input_url" \
        $ffmpeg_args \
        "$tmp_dir/output.m3u8"

    EXIT_CODE=$?
    if [[ $EXIT_CODE -ne 0 ]]; then
        echo "[ERROR] FFmpeg failed with code $EXIT_CODE"
        rm -rf "$tmp_dir"
        return 1
    fi

    echo "[INFO] Uploading to S3 bucket: $AWS_BUCKET/$output_prefix/"
    aws s3 cp "$tmp_dir" "s3://$AWS_BUCKET/$output_prefix/" --recursive --acl public-read

    signed_url=$(aws s3 presign "s3://$AWS_BUCKET/$output_prefix/output.m3u8" --expires-in 3600)
    echo "[INFO] Signed URL (valid 1h): $signed_url"

    rm -rf "$tmp_dir"
}

# Main loop
while true; do
    echo "[INFO] Waiting for next job from Redis queue '$REDIS_QUEUE'..."
    job_json=$(fetch_job)

    input_url=$(echo "$job_json" | jq -r '.input_url')
    output_prefix=$(echo "$job_json" | jq -r '.output_prefix')
    ffmpeg_args=$(echo "$job_json" | jq -r '.ffmpeg_args // "-c:v copy -c:a copy -f hls"')

    if [[ -z "$input_url" || -z "$output_prefix" ]]; then
        echo "[ERROR] Invalid job: $job_json"
        sleep $RETRY_DELAY
        continue
    fi

    run_ffmpeg "$input_url" "$output_prefix" "$ffmpeg_args"
    echo "[INFO] Job finished. Waiting $RETRY_DELAY seconds before next..."
    sleep $RETRY_DELAY
done
