#!/bin/bash

echo "--------------------------------------------"
echo "   FFmpeg Worker Started"
echo "--------------------------------------------"
echo "Start Time: $(date)"
echo ""

# -----------------------------
# Konfigurime nga ENV
# -----------------------------

INPUT_URL="${INPUT_URL:-""}"
OUTPUT_URL="${OUTPUT_URL:-""}"
FFMPEG_ARGS="${FFMPEG_ARGS:-"-c:v copy -c:a copy -f hls"}"
RETRY_DELAY="${RETRY_DELAY:-5}"

# Kontroll nëse mungojnë URL-t

if [[ -z "$INPUT_URL" ]]; then
    echo "[ERROR] INPUT_URL nuk është vendosur!"
    echo "Vendose:  docker run -e INPUT_URL=\"http://stream\" ..."
    exit 1
fi

if [[ -z "$OUTPUT_URL" ]]; then
    echo "[ERROR] OUTPUT_URL nuk është vendosur!"
    exit 1
fi

echo "[INFO] INPUT_URL  = $INPUT_URL"
echo "[INFO] OUTPUT_URL = $OUTPUT_URL"
echo "[INFO] FFMPEG_ARGS = $FFMPEG_ARGS"
echo ""

# -----------------------------
# Funksioni kryesor
# -----------------------------

run_ffmpeg() {
    echo "[INFO] Running FFmpeg..."
    
    ffmpeg -hide_banner -loglevel error \
        -i "$INPUT_URL" \
        $FFMPEG_ARGS \
        "$OUTPUT_URL"

    EXIT_CODE=$?

    echo "[WARN] FFmpeg exited with code $EXIT_CODE"
    echo "[INFO] Restarting FFmpeg in $RETRY_DELAY seconds..."
    sleep $RETRY_DELAY
}

# -----------------------------
# Loop i pafund
# -----------------------------

while true; do
    run_ffmpeg
done
