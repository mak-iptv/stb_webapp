# IPTV Render Complete Starter

This is a more complete starter for a web IPTV player and admin panel, intended for hosting on Render.com or running locally with Docker.

## What's included
- PHP backend with JWT authentication (login/register)
- Admin panel to manage channels (demo)
- M3U playlist and XMLTV (EPG) endpoints
- DB migrations (MySQL)
- Dockerfile for an FFmpeg worker (placeholder)
- docker-compose for local development
- .env.example for configuration

## Quick local setup (without Render)
1. Copy `.env.example` to `.env` and edit DB credentials and JWT secret.
2. Start MySQL locally (or use docker-compose below).
3. Install composer deps:
   ```
   composer install
   ```
4. Run migrations (example using mysql CLI):
   ```
   mysql -u root -prootpassword iptv < src/migrations.sql
   ```
5. Start PHP server:
   ```
   php -S 0.0.0.0:10000 -t public
   ```
6. Point browser to `http://localhost:10000`.

## Using docker-compose (local)
```
docker-compose up --build
# then run migrations into the mysql container, or use a MySQL client
```

## Deploying to Render
- Option 1 (Quick): Use Render's **Web Service** with Environment = PHP, Root = repo root, Build command `composer install`, Start command `php -S 0.0.0.0:10000 -t public`.
- Option 2 (Container): Build a Docker image and deploy to Render as a Docker service. For FFmpeg worker, use the provided Dockerfile `docker/ffmpeg-worker/Dockerfile` and run it as a background service or separate job.

## Security & production notes
- Replace demo tokens & secrets. Set `JWT_SECRET` to a long random value.
- Use HTTPS (Render provides TLS).
- Protect admin creation: current sample requires manual role update in DB to set admin.
- Implement proper concurrent stream counting and signed URLs for segments in production.
- This starter is for educational/demo purposes.

## Next steps I can do for you (pick any)
- Harden auth (full JWT refresh tokens + password resets)
- Admin UI improvements (edit/delete channels, upload logos)
- Add concurrent stream limiter logic
- Implement real ffmpeg transcoding job queue example
