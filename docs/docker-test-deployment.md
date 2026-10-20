# Docker test deployment

This stack is for the separate test VM at `10.20.2.115`. It does not replace
or modify the production deployment at `10.20.2.98`.

## Services

- `web`: Nginx on the VM's port 80.
- `app`: PHP 8.4 FPM with Node 24 and the document-reading dependencies.
- `scheduler`: Laravel's scheduler. It runs the OCR batch every five minutes,
  so this deployment does not need a host cron entry.
- `worker`: Laravel's database queue worker.
- `migrate`: a one-shot migration service that must finish before the app starts.
- `db`: MySQL 8.4 with a persistent Docker volume.

Documents, OCR language data, and MySQL data live in named Docker volumes and
survive image rebuilds and container replacements.

## First manual deployment

The VM needs Docker Engine with the Compose plugin, Git, and OpenSSL. The
`test_server` user must be able to run Docker without an interactive `sudo`
prompt.

```bash
git clone https://github.com/jhmito-gif/CAAP-DTS.git /opt/caap-dts
cd /opt/caap-dts
git switch chore/docker-test-deployment
./docker/prepare-env.sh http://10.20.2.115
./docker/deploy.sh
```

Open `http://10.20.2.115`. To inspect the stack:

```bash
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 web app scheduler worker
```

To queue an existing document backlog:

```bash
docker compose --env-file .env.docker exec -u www-data app php artisan documents:read --queue-all
```

Do this once after importing old data, not on every deployment. New documents
are queued by the application and processed by `scheduler` automatically.

## Test CI/CD

The `Docker Test Deployment` workflow is separate from the existing production
workflow. Its deployment job requires a self-hosted GitHub Actions runner on
the test VM with the custom label `caap-dts-test`.

Create the GitHub environment `test` and add a secret named
`DOCKER_ENV_FILE_B64`. Its value is the base64 encoding of the complete
`.env.docker` file:

```bash
base64 -w 0 .env.docker
```

Run the workflow manually after its image-build check passes. The job checks
out the selected revision on the test VM, restores `.env.docker` from the
secret, and runs `docker/deploy.sh`. A push to `main` still follows the existing
production deployment; this test workflow never targets `10.20.2.98`.

## Useful operations

```bash
# Run all scheduled tasks immediately.
docker compose --env-file .env.docker exec -u www-data app php artisan schedule:run

# Verify Node, PDF parsing, OCR packages, the database, and the document queue.
docker compose --env-file .env.docker exec -u www-data app php artisan documents:check

# Stop containers without deleting persistent data.
docker compose --env-file .env.docker down

# Deliberately delete the test database and documents. This cannot be undone.
docker compose --env-file .env.docker down --volumes
```
