# Base image
FROM ubuntu:22.04

# Disable interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Instalo dependencies: ffmpeg, curl, redis-cli, jq, python3 + awscli
RUN apt-get update && \
    apt-get install -y \
        ffmpeg \
        curl \
        redis-tools \
        jq \
        python3 \
        python3-pip && \
    pip3 install --no-cache-dir awscli && \
    rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /work

# Kopjo worker script
COPY worker.sh /work/worker.sh
RUN chmod +x /work/worker.sh

# Start worker
CMD ["/work/worker.sh"]
