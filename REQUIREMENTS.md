# Product Requirements Document (PRD) — Cataloging the Cryptic
**Domain:** `catalogingthecryptic.com`  
**Stack:** Laravel 13 + PHP 8.5 (8.5.12+) + Livewire 4 + Filament v5 + Alpine.js + Tailwind CSS v4 + Chart.js + KaTeX + PostgreSQL / SQLite

---

## 1. System Vision & Domain Model
The primary organizational unit of the site is the **Video**, with support for parent **Patreon Posts**.
- A **Patreon Post** represents an official Patreon community publication and can contain one or more **Videos** (1:N).
- A single Patreon Post can contain both public videos (e.g., YouTube solves featured in a post) and exclusive videos (e.g., unlisted patron masterclasses).
- Privacy and access permissions are differentiated at the **Video level** via `access_level` (`public` vs. `exclusive`), rather than strictly at the post level.
- A **Video** has a `video_type` defining format/medium (`youtube`, `youtube_short`, `live_stream`, or `patreon`).
- A **Video** optionally belongs to a **Patreon Post** (`patreon_post_id`). When attached to a Patreon Post, its `patreon_post_url` fallback automatically syncs from the parent post's `patreon_url` if blank.
- A **Video** features one or more **Hosts** (Simon Anthony, Mark Goodliffe, or guest solvers).
- A **Video** contains one or more **Puzzles** (standard single-puzzle deep dives vs. multi-puzzle compilations).
- A **Video** has many **Bookmarks / Timestamps** (pinned to the video runtime, optionally tagged to a specific puzzle).
- A **Video** captures optional **Intro Music** and **Outro Music** track credits and an `is_finalized` curation flag.
- A **Puzzle** has one or more **Setters** (supporting collaborative builds), and each **Setter** has zero or more external **Setter Links** (`setter_links`).
- A **Puzzle** has one **Category** and one or more **Variants / Constraints** (Thermo, Arrow, Killer, Fog of War, German Whispers, etc.), always ordered alphabetically on display.
- A **Puzzle** has one or more **Puzzle Links** (`puzzle_links`, 1:N), capturing platform and solve destinations (SudokuPad, Logic Masters Germany, Penpa, Times Puzzles, Cracking the Cryptic App, or custom sources).
- A **Puzzle** has an **`access_level`** (`public` vs. `exclusive`). For exclusive puzzles, the title, setter, category, difficulty score, constraints, and rules remain visible, while direct solve links are suppressed on public pages.
- **CTC Apps (`ctc_apps`)**: Showcases official Cracking the Cryptic mobile and desktop applications with store links (Steam, iOS App Store, Google Play) and release dates.
- **Audit Tracking (`HasUserTracking`)**: All core catalog entities track `created_by_id` and `updated_by_id` referencing `users.id`.

---

## 2. URL Strategy, Slug Conventions & URL Sanitization
- **Unique Slugs (`HasSlugRouteKey`):** Videos, Puzzles, Setters, Constraints, Categories, Hosts, and Patreon Posts automatically generate unique slugs on creation, appending numeric suffixes (`-2`, `-3`, etc.) when collisions occur.
- **YouTube Redirect Unwrapping (`UrlSanitizer`):** Any YouTube description redirect URL (`https://www.youtube.com/redirect?...&q=...`) entered into Filament forms (`TextInput::unwrapRedirects()`), model URL fields (`Video`, `PuzzleLink`, `Setter`, `SetterLink`, `PatreonPost`), or markdown rules/descriptions is automatically unwrapped to its canonical `http(s)` target URL prior to storage.
- **WebP Thumbnails:** YouTube thumbnail URLs (`i.ytimg.com/vi/{id}/{quality}.jpg`) are automatically normalized to WebP (`i.ytimg.com/vi_webp/{id}/{quality}.webp`).

---

## 3. Database Schema & Indexing

### 3.1. Core Tables
1. **`hosts`**: `id`, `name`, `slug` (unique), `avatar_url`, `created_by_id`, `updated_by_id`, timestamps.
2. **`categories`**: `id`, `name` (unique), `slug` (unique), `description`, `created_by_id`, `updated_by_id`, timestamps.
3. **`constraints`**: `id`, `name` (unique), `slug` (unique), `description`, `created_by_id`, `updated_by_id`, timestamps.
4. **`setters`**: `id`, `name`, `slug` (unique), `bio`, `patreon_url`, `created_by_id`, `updated_by_id`, timestamps.
5. **`setter_links`**: `id`, `setter_id` (FK), `link_type`, `url`, `title`, `order_column`, `created_by_id`, `updated_by_id`, timestamps.
6. **`patreon_posts`**: `id`, `title`, `slug` (unique), `patreon_url`, `published_at`, `notes`, `created_by_id`, `updated_by_id`, timestamps.
7. **`videos`**:
   - `id`, `patreon_post_id` (FK nullable), `youtube_video_id` (unique nullable), `video_type`, `access_level`, `patreon_post_url`, `title`, `slug` (unique), `published_at`, `duration_seconds`, `view_count`, `stats_synced_at`, `youtube_thumbnail_url`, `intro_music`, `outro_music`, `is_finalized`, `created_by_id`, `updated_by_id`, timestamps.
   - Indexed on `['published_at', 'id']`, `view_count`, `duration_seconds`, `video_type`, and `is_finalized`.
8. **`puzzles`**: `id`, `category_id` (FK), `title`, `slug` (unique), `access_level`, `rules_raw` (nullable), `difficulty_score` (nullable), `created_by_id`, `updated_by_id`, timestamps.
9. **`puzzle_links`**: `id`, `puzzle_id` (FK), `link_type`, `url`, `title`, `order_column`, `created_by_id`, `updated_by_id`, timestamps.
10. **`link_types`**: `id`, `name` (unique), `slug` (unique), `created_by_id`, `updated_by_id`, timestamps.
11. **`bookmarks`**: `id`, `video_id` (FK), `puzzle_id` (FK nullable), `timestamp_seconds`, `label`, `created_by_id`, `updated_by_id`, timestamps.
12. **`ctc_apps`**: `id`, `name`, `slug` (unique), `tagline`, `description`, `release_date`, `steam_url`, `ios_url`, `android_url`, `other_url`, `other_label`, `sort_order`, `created_by_id`, `updated_by_id`, timestamps.
13. **`youtube_sync_logs`**: `id`, `status`, `started_at`, `finished_at`, `duration_ms`, `new_videos_count`, `updated_views_count`, `total_views_added`, `total_processed`, `message`, `details` (JSON), timestamps.

### 3.2. Pivot Tables & Reverse Lookup Indexes
1. **`video_hosts`**: PK `(video_id, host_id)` + index on `host_id`.
2. **`video_puzzles`**: `id`, `video_id`, `puzzle_id`, `order_in_video`, `start_seconds`, `solve_duration_seconds`, `created_by_id`, `updated_by_id`, timestamps; unique `(video_id, puzzle_id)` + index on `puzzle_id`.
3. **`puzzle_setters`**: PK `(puzzle_id, setter_id)` + index on `setter_id`.
4. **`puzzle_constraints`**: PK `(puzzle_id, constraint_id)` + index on `constraint_id`.

---

## 4. Search Implementation (`/search`)
- Powered by `App\Services\SearchService` (`PER_PAGE = 30`).
- Cross-entity matching across **Videos** (title, intro/outro music, hosts, bookmarks, attached puzzles), **Bookmarks** (timestamp labels), **Puzzles** (title, rules, setters, category, constraints), **Setters** (name, bio), **Categories** (name, description), **Constraints** (name, description), and **Hosts** (name).
- Category filter tabs (`all`, `videos`, `bookmarks`, `puzzles`, `setters`, `categories`, `constraints`, `hosts`) with live badge counts.

---

## 5. YouTube Synchronization & Tiered View Count Schedule
- Artisan command: `php artisan ctc:sync-youtube {--all} {--limit=} {--playlist=} {--dry-run} {--force} {--all-stats} {--at=} {--skip-new} {--skip-stats}`
- Ingests new public uploads from the channel playlist (`UUC-UOdK8-mIjxBQm_ot1T-Q`) and updates view counts according to a 5-tier release age schedule in `YouTubeService::isVideoDueForViewUpdate()`:
  - **Tier 1 (< 48 hours old):** Updated on every 30-minute run.
  - **Tier 2 (2 to 7 days old):** Updated twice daily (midnight and noon UTC).
  - **Tier 3 (7 to 14 days old):** Updated once daily (midnight UTC).
  - **Tier 4 (14 to 31 days old):** Updated weekly on days 14, 21, and 28 (midnight UTC).
  - **Tier 5 (> 31 days old):** Updated monthly on the release day anniversary (midnight UTC).
- Logs execution metrics to `youtube_sync_logs` (retaining the latest 100 entries) and flushes `CatalogStatsService` caches on completion.

---

## 6. Frontend & User Interface Specifications

### 6.1. Landing Page (`/`)
- **Hero Video:** Latest published video with two-click privacy facade (public) or Patreon preview card (exclusive).
- **Dynamic Stats Bar:** Cached counters (`CatalogStatsService::getHomeStats()`) linking to Total Cataloged Videos (`/videos`), Total Puzzles Indexed (`/search?type=puzzles`), Distinct Setters (`/setters`), and Distinct Constraints (`/constraints`).
- **Latest Feed:** Grid of the 6 most recent videos following the hero video.

### 6.2. Video Views (`/videos`, `/videos/top`, `/videos/longest`, `/videos/shortest`, `/videos/{slug}`)
- **Directory & Leaderboards:** Paginated video catalog (`/videos`), Top 24 Most Viewed (`/videos/top`), Top 24 Longest Solves (`/videos/longest`, excluding live streams), and Top 24 Shortest Solves (`/videos/shortest`, excluding Shorts), with optional host filtering (`?host=simon` or `?host=mark`).
- **Video Detail (`/videos/{slug}`):** Conditional player, Patreon companion callout, intro/outro music badges, interactive chronological bookmarks (`seekTo`), linked puzzle cards with alphabetical constraint badges and collapsible rules drawer, and chronological **Previous Video / Next Video** navigation buttons.

### 6.3. Setters & Constraints Directories (`/setters`, `/constraints`)
- SQL-paginated (`20` per page) directories with server-side search (`?q=`) and column sorting (`name`, `puzzles`, `videos`, `first`, `recent`), hydrating first/recent video and puzzle relations exclusively for the current page's 20 rows.
- Detail pages (`/setters/{slug}`, `/constraints/{slug}`) render sanitized Markdown + KaTeX math descriptions, summary metric tiles, and paginated video grids.

### 6.4. Catalog Statistics & Analytics (`/stats`)
- Powered by `App\Services\CatalogStatsService` (`catalog_analytics_summary_v6`, cached for 30 minutes and automatically invalidated on catalog model saves/deletes).
- Uses **median** viewership and duration metrics to prevent viral outlier skew.
- Includes release volume trends, format breakdown, duration sweet-spot brackets, day-of-week performance, puzzle category stacked progressions (yearly and 12-month navigable monthly window), variant sudoku constraints pie chart (combining the tail ~25% into an "Other" wedge linking to `/constraints`), intro/outro music pie charts with unified color legend, hall-of-fame milestone cards, and dynamic analytical observations.

### 6.5. User Timezones & Privacy Compliance
- Authenticated users can configure their preferred timezone (`TimezoneService`, `@userDate`, `@userDateTime`), with automatic browser timezone detection via the `ctc_user_timezone` cookie for guests.
- Consent-gated Google Analytics and two-click YouTube privacy facades documented at `/privacy`.

---

## 7. Administrative Requirements (Filament v5)
- Restricted to administrators (`User::isAdmin()`).
- Resources for `VideoResource`, `PatreonPostResource`, `PuzzleResource`, `SetterResource`, `ConstraintResource`, `CategoryResource`, `HostResource`, `LinkTypeResource`, `CtcAppResource`, and `UserResource`.
- Dedicated curation tools: `UnfinalizedVideos` review queue, `BookmarkPlayer` interactive timestamp annotation studio, and `YouTubeUpdateScripts` sync dashboard.
