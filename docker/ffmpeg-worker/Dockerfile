FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y ffmpeg curl && rm -rf /var/lib/apt/lists/*
WORKDIR /work
COPY worker.sh /work/worker.sh
RUN chmod +x /work/worker.sh
CMD ["/work/worker.sh"]
