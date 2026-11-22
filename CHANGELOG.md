# Changelog

All notable changes to ClockFence are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Laravel 13 app with Inertia and Vue, and a Filament admin panel at `/admin` for Admins and Managers.
- Roles (Admin, Manager, Staff) and a demo seeder.
- `php artisan clockfence:install` to create the first Admin.
- `config/clockfence.php` with the `CLOCKFENCE_*` settings.
- Smoke test group: `php artisan test --group=smoke`.
- CI on PHP 8.3 and 8.4 against SQLite, MySQL and PostgreSQL.
- Sites with a geofence, start time, timezone and Working days, managed by Admins and Archived instead of deleted.
- Staff members with a staff number, home Site, role and language, invited by email to set their own password, and deactivated instead of deleted.
- Geofence distance, Site day and lateness rules.
- The 16 named Clock failures with English and Bahasa Melayu messages.
- The Clock flow: a Geo-check issues a 90-second single-use Clock token, and scanning the Site Poster spends it and records a Clock-in or Clock-out with Late minutes.
- A printable A4 Poster per Site, in English and Bahasa Melayu.
- Staff login and the clock page: an installable web app that reads the location once, opens the camera only with a Clock token, scans the Poster and shows each Clock failure in the Staff member's language.
- QR scanning on iPhone and iPad through a jsQR fallback that loads only when the browser has no built-in QR reader.
- Project skills for Claude Code: `add-clock-failure` and `clock-flow-change`.
- Password reset for Staff members (/forgot-password) and for the admin panel; deactivating a Staff member ends their sessions.
