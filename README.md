# rexlCloud Web Panel

Web interface for [rexlCloud](https://github.com/rexlNico/rexlCloud). It talks to the **RestModule** (REST API v2)
of the master and stores no data itself.

Features: dashboard with live statistics, servers (live console, commands, stop/restart/kill), groups (create, edit,
start servers, template sync, delete), online players (message, kick), wrappers (status, register, stop),
maintenance mode, modules, and panel users with permissions.

Built with Laravel 13, Livewire 4, Tailwind CSS 4 and Chart.js.

## Running with Docker Compose

1. Load the RestModule (2.0 or newer) on the master. On its first start it prints the password of the `admin` user.
2. Create a `.env` file next to `docker-compose.yml`:

   ```dotenv
   APP_KEY=            # see below
   APP_URL=https://web.example.com
   PANEL_PORT=8080
   ```

   Generate the key with `docker compose run --rm --entrypoint php panel artisan key:generate --show`.
3. Start it: `docker compose up -d --build` and open `http://localhost:8080`.
4. Log in with the **Cloud URL** of the RestModule, e.g. `http://10.0.0.10:4343`. The panel remembers it in a cookie
   and fills it in next time, so one panel can be used for several clouds.

Behind the nginx-proxy / Let's Encrypt setup (external `proxy` network) use the overlay, which sets
`VIRTUAL_HOST`/`LETSENCRYPT_HOST` from `VIRTUAL_HOST` in `.env`:

```bash
docker compose -f docker-compose.yml -f docker-compose.proxy.yml up -d --build
```

If the master runs on the Docker host itself, log in with `http://host.docker.internal:4343`.

### Live console

The browser streams server consoles directly from the RestModule over a WebSocket, authorised with a short-lived,
single-use ticket, using the Cloud URL from the login page. It must therefore also be reachable from the browser (an
`https://` URL when the panel is served over HTTPS). If it is not reachable, the console automatically falls back to updating every
three seconds through the panel.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `REXLCLOUD_API_TIMEOUT` | `10` | Request timeout in seconds |
| `TRUSTED_PROXIES` | `*` | Proxies whose `X-Forwarded-*` headers are trusted |
| `SESSION_LIFETIME` | `120` | Panel session lifetime in minutes (the API token expires after 60 idle minutes) |

## Development

Requires PHP 8.3+, Composer and Node.js 22+.

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
composer run dev   # or: php artisan serve + npm run dev
```

Without a real cloud, run the RestModule against fake data from the rexlCloud repository:
`./gradlew :RestModule:runDevServer` (port 4343, user `dev` / `devpassword`).

Tests fake the API, so they need no cloud: `php artisan test`.
