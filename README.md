# Cataloging the Cryptic

**Cataloging the Cryptic** (`catalogingthecryptic.com`) is a searchable community archive and analytics platform for videos, puzzles, setters, variant sudoku constraints, and timestamp bookmarks featured on the [Cracking the Cryptic](https://www.youtube.com/@CrackingTheCryptic) YouTube channel and Patreon.

---

## Technology Stack

* **Runtime:** PHP 8.5 (`>= 8.5.12`, patched against PHP2Shell CVE-2026-109618 / CVE-2026-109619)
* **Framework:** Laravel 13
* **Admin Panel:** Filament v5 + Livewire 4
* **Frontend:** Blade, Alpine.js, Tailwind CSS v4, Chart.js 4, KaTeX (bundled locally via Vite)
* **Database:** PostgreSQL (production) / SQLite (local & automated test suite)

---

## Key Features

* **Video Catalog (`/videos`, `/videos/top`, `/videos/longest`, `/videos/shortest`, `/videos/random`):**
  * Full archive of standard YouTube solves, YouTube Shorts, Live Streams, and Patron-exclusive releases.
  * Two-click privacy facade for YouTube embeds, interactive timestamp bookmarks with instant player seeking, and chronological previous/next video navigation.
  * Host filtering (Simon Anthony, Mark Goodliffe) on top, longest, and shortest video leaderboards.
* **Setters Directory (`/setters`, `/setters/{slug}`):**
  * Sortable, searchable directory of puzzle setters with puzzle counts, debut ("First on Channel") and latest ("Most Recent on Channel") appearances, Markdown biographies, and external setter links.
* **Variant Constraints Directory (`/constraints`, `/constraints/{slug}`):**
  * Sortable, searchable encyclopedia of variant sudoku rulesets and mechanics with Markdown + LaTeX math definitions, video/puzzle counts, viewership totals, and featured solve listings.
* **Catalog Analytics & Trends (`/stats`):**
  * Powered by `App\Services\CatalogStatsService` with automatic cache invalidation on model changes.
  * Tracks release volume, median viewership (resistant to viral outlier skew), duration sweet-spot brackets, day-of-week publishing cadence, puzzle category progressions (yearly and 12-month rolling windows), variant constraint breakdowns (grouping tail constraints into an "Other" wedge), intro/outro music statistics, and automated data-driven observations.
* **Cross-Entity Search (`/search`):**
  * Unified search across videos, timestamp bookmarks, puzzles, setters, categories, constraints, and hosts via `App\Services\SearchService`.
* **Cracking the Cryptic Apps Showcase (`/apps`):**
  * Curated directory of official CTC mobile/desktop releases across Steam, iOS, and Android.
* **Filament v5 Curation & Admin Suite (`/admin`):**
  * Full CRUD management for Videos, Patreon Posts, Puzzles, Setters, Constraints, Categories, Hosts, Link Types, CTC Apps, and Users.
  * Interactive `BookmarkPlayer` tool for live video timestamp annotation, `UnfinalizedVideos` review queue, automated Patreon metadata ingestion (`PatreonService`), and automatic YouTube redirect URL unwrapping (`UrlSanitizer`).

---

## Local Setup

1. **Install PHP and Node dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Set `YOUTUBE_API_KEY` in `.env` if running YouTube playlist/video synchronization.

3. **Run database migrations and build frontend assets:**
   ```bash
   php artisan migrate
   npm run build
   ```

4. **Start the development server:**
   ```bash
   composer run dev
   ```

---

## Artisan Commands

* **Synchronize YouTube Uploads & Tiered View Counts:**
  ```bash
  php artisan ctc:sync-youtube
  php artisan ctc:sync-youtube --all
  php artisan ctc:sync-youtube --dry-run
  php artisan ctc:sync-youtube --all-stats
  ```

---

## Testing, Formatting & Security Audits

Run the PHPUnit test suite, Laravel Pint code formatter, and dependency security audits:

```bash
# Run full test suite (compact output)
php artisan test --compact

# Run a single test file or filter
php artisan test --compact tests/Feature/CatalogStatsTest.php

# Format modified PHP files with Laravel Pint
vendor/bin/pint --dirty --format agent

# Audit PHP and JS dependencies for security advisories
composer audit
npm audit
```

See [SECURITY.md](SECURITY.md) for detailed runtime hardening (including PHP2Shell CVE-2026-109618 / CVE-2026-109619 requirements), [PROJECT_RULES.md](PROJECT_RULES.md) for engineering conventions, and [REQUIREMENTS.md](REQUIREMENTS.md) for product specifications.
