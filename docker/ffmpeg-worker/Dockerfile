FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Instalo dependencies: ffmpeg, curl, redis-cli, jq
RUN apt-get update && \
    apt-get install -y ffmpeg curl redis-tools jq && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /work

COPY worker.sh /work/worker.sh
RUN chmod +x /work/worker.sh

CMD ["/work/worker.sh"]
