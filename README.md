# ClockFence

**Employee QR attendance with geofence, for Laravel.**

Staff clock in and out with their own phone, with no app to install. The phone's location is checked against the staff member's own Site first. Only a pass issues a single-use token, and only then does the camera open to scan the static QR poster at the entrance. The security lives in that order of steps, not in the QR code.

> **Status: early development, not production-ready until v1.0.** Clocking in and out works end to end (Sites, Staff members, Posters, the clock page). The Register, Manual overrides and payroll exports come next in milestone M2, so do not rely on it for payroll yet. See the [milestones](https://github.com/fiqahmad/clockfence-qr-attendance-geofence/milestones) for progress.

## Features (v1.0)

- **Clock in and out by phone**: a PWA in the browser, under 10 seconds per Clock.
- **Geofence first, QR second**: a photo of the poster is useless away from the Site; tokens last 90 seconds and work once.
- **Clear failure messages**: every refusal is named and tells the person what to do now. Distance and coordinates are never shown.
- **Sites**: many workplaces, each with its own fence, start time, working days and timezone.
- **Register**: one record per staff member per day: present, late (with minutes), absent, on leave, holiday.
- **Manual overrides**: by an Admin or Manager, always with a reason, never on their own record.
- **Reports**: daily board, monthly summary, CSV and XLSX export for payroll.
- **Privacy by design**: location is read once per Clock, never tracked in the background; coordinates are audit-only and pruned after 90 days.
- **English and Bahasa Melayu.**

## How it works

Each Site prints one static QR **Poster** for its entrance. A Staff member clocks in four steps:

1. **Tap Clock in** on their phone. The page reads the location **once**; nothing tracks them in the background.
2. **The server checks the location** against the Staff member's own home Site (the Site is never taken from the phone). Outside the fence, or with a GPS fix worse than 100 m, the Clock is refused with a message that says what to do.
3. **Only a pass issues a Clock token**: random, single-use, valid for 90 seconds and stored only as a hash. Only now does the **camera open**.
4. **Scan the Poster.** The scan spends the token first, then checks it belongs to this person and that the Poster is for the same Site, and records the Clock-in (with late minutes) or Clock-out.

The Poster is not a secret: a photo of it does nothing from home, because the location check comes first. A token cannot be replayed, shared with a colleague or used at another Site's Poster. The first Clock of the day is in, the next is out, and a Clock-out within 5 minutes of the Clock-in is refused so a double tap cannot end the day.

```text
Phone                          Server
  tap Clock in
  read location once ─────────► Geo-check against home Site
                     ◄───────── pass: 90 s single-use token  (fail: named reason)
  open camera, scan Poster ───► spend token → same person? same Site? → record Clock
                     ◄───────── "Clocked in at 08:55"
```

**Not included, on purpose:** payroll, leave management, biometrics, native apps, background tracking, Google Maps, multi-tenancy.

## Requirements

| Need     | Version                                                                                 |
| -------- | --------------------------------------------------------------------------------------- |
| PHP      | 8.3 or newer, with `bcmath`, `gd`, `intl`, and `pdo_mysql`, `pdo_pgsql` or `pdo_sqlite` |
| Composer | 2.x                                                                                     |
| Node.js  | 20 or newer (to build the front end)                                                    |
| Database | MySQL 8, PostgreSQL 14+, or SQLite for a quick try                                      |
| HTTPS    | Required in production: phones only allow GPS and camera on HTTPS                       |

## Quick start

### Install with Claude Code (recommended)

```bash
git clone https://github.com/fiqahmad/clockfence-qr-attendance-geofence.git clockfence
cd clockfence
claude
```

Then type `/install-clockfence`. Claude Code asks one question (demo, Docker or production), checks PHP, Composer and Node, installs, runs the smoke tests and tells you the URL and the admin login. It never overwrites an existing `.env` or wipes a database.

Using another agent (Cursor, Codex, Copilot)? Open the folder and paste:

```text
Install ClockFence in this folder by following AGENTS.md, section "Install".
Ask me which mode I want (demo, docker, production) before you start.
Do not overwrite an existing .env or run migrate:fresh on an existing database.
When done, run the smoke tests and tell me the URL and the admin login.
```

### Try it with demo data (SQLite)

```bash
git clone https://github.com/fiqahmad/clockfence-qr-attendance-geofence.git clockfence
cd clockfence
composer setup
php artisan db:seed
php artisan serve
```

`composer setup` installs dependencies, creates `.env` (SQLite by default), migrates and builds the front end. The seeder prints the demo logins (one Admin, one Manager, three Staff members). Open `http://localhost:8000`: Admins land in the admin panel, Staff members on the clock page.

To try a real Clock on a phone you need HTTPS, because phones only allow GPS and the camera on secure pages. Expose the local server through a tunnel (for example `cloudflared tunnel --url http://localhost:8000`), set a demo Site's latitude and longitude to where you are, and print its Poster from the Sites list.

### Self-host as a normal Laravel app

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate   # then set APP_URL and DB_* in .env
php artisan migrate --force
php artisan clockfence:install
php artisan optimize
```

Then run a queue worker (`php artisan queue:work --tries=3`) and the scheduler cron (`* * * * * cd /path/to/clockfence && php artisan schedule:run >> /dev/null 2>&1`).

### Self-host with Docker

```bash
git clone https://github.com/fiqahmad/clockfence-qr-attendance-geofence.git clockfence && cd clockfence
cp .env.example .env    # set APP_URL, APP_ENV=production, APP_DEBUG=false, DB_PASSWORD, MAIL_*
docker compose build
docker compose run --rm --no-deps -T -e AUTORUN_ENABLED=false app php artisan key:generate --show
# paste the printed key into APP_KEY in .env
docker compose up -d --wait
docker compose exec app php artisan clockfence:install
```

The stack runs the app on port 8000 (`APP_PORT`), MySQL 8.4, a queue worker and the scheduler, and migrates on start. Put it behind a reverse proxy with HTTPS (Caddy, Nginx with Let's Encrypt, or Cloudflare).

### Check the install

```bash
php artisan test --group=smoke
```

## Configuration

| `.env` key                  | Default | What it does                                                          |
| --------------------------- | ------- | --------------------------------------------------------------------- |
| `APP_URL`                   | none    | Must be the public HTTPS address in production                        |
| `CLOCKFENCE_TOKEN_TTL`      | `90`    | Seconds a Clock token stays valid                                     |
| `CLOCKFENCE_MAX_ACCURACY`   | `100`   | GPS fixes worse than this many metres are refused                     |
| `CLOCKFENCE_DEFAULT_RADIUS` | `120`   | Default geofence radius in metres for new Sites (allowed: 50 to 1000) |
| `CLOCKFENCE_CLOCK_OUT_GAP`  | `5`     | Minutes after a Clock-in before a Clock-out is accepted               |
| `CLOCKFENCE_RETENTION_DAYS` | `90`    | Days before location audit data is pruned                             |
| `CLOCKFENCE_DEVICE_BINDING` | `false` | Require a passkey-bound device (planned for M3)                       |

Keep `APP_TIMEZONE=UTC`; each Site sets its own timezone.

## Limitations

- **No overnight shifts in v1.** A day is judged in the Site's timezone, so a shift from 22:00 to 06:00 is recorded as two days. An Admin fixes it with a Manual override.
- **A browser cannot detect a mocked GPS location.** The geofence is a strong nudge, not proof.
- **One organisation per install.**

## Documentation

- [`GLOSSARY.md`](GLOSSARY.md): the domain language (Site, Clock, Geo-check, Attendance, ...)
- [`docs/adr/`](docs/adr): why key decisions were made
- [`CONTRIBUTING.md`](CONTRIBUTING.md): set up, test and submit a change
- [`SECURITY.md`](SECURITY.md): report a vulnerability or a spoofing bypass

## How this was built

Fiq wrote the plan, the domain model, the glossary and the decision records, and implemented the code with Claude Code guided by [`AGENTS.md`](AGENTS.md) and [`CLAUDE.md`](CLAUDE.md). The ADRs explain each decision so it can be discussed without any tool open.

## Licence

[MIT](LICENSE)
