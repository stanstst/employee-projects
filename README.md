# Simple API

A minimal Laravel 13 API: no database, no authentication, a blank home page and JSON routes under `/api`.

## Run with Docker

Two services: `web` (Nginx, the only published port) forwards PHP requests to `app` (PHP-FPM).

Development, with live reload (code edits are synced into the container; changes to `composer.json`/`composer.lock` rebuild the image):

```sh
docker compose up --build --watch
```

Live reload only runs while that command is running; on start it first syncs any edits made while it was off.

Or without watching (serves the code from the last build):

```sh
docker compose up --build --watch -d
```

- Home page (blank): http://localhost:8000
- API: http://localhost:8000/api
- Health check: http://localhost:8000/up

Set `APP_PORT` to change the host port. An `APP_KEY` is generated on first start; pass one through the environment to keep it stable across container rebuilds.

## Add endpoints

Define routes in `routes/api.php`; they are served under the `/api` prefix.

## Tests

```sh
docker run --rm -v "$PWD":/app -w /app composer:2 sh -c "composer install && composer test"
```
## Api calculate-common-projects endpoint Test

```sh
curl -F "file=@tests/data/sample.csv" localhost:8000/api/calculate-common-projects
```
