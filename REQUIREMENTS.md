# Product Requirements Document (PRD) — Cataloging the Cryptic
**Domain:** `catalogingthecryptic.com`  
**Target Stack:** Laravel 12 + Livewire 3 + Alpine.js + Tailwind CSS + PostgreSQL + Filament v3 + Laravel Scout (Meilisearch)

---

## 1. System Vision & Domain Model
The primary organizational unit of the site is the **Video**, with support for parent **Patreon Posts**.
- A **Patreon Post** represents an official Patreon community publication and can contain one or more **Videos** (1:N).
- A single Patreon Post can contain both public videos (e.g. YouTube solves featured in a post) and exclusive videos (e.g. unlisted patron masterclasses).
- Privacy and access permissions are differentiated at the **Video level** via `access_level` (`public` vs. `exclusive`), rather than strictly at the post level.
- A **Video** has a `video_type` defining format/medium (`youtube`, `youtube_short`, `live_stream`).
- A **Video** optionally belongs to a **Patreon Post** (`patreon_post_id`).
- A **Video** features one or more **Hosts** (Simon Anthony, Mark Goodliffe, or guest solvers).
- A **Video** contains one or more **Puzzles** (e.g., standard single-puzzle deep dives vs. speed-solve / GAS compilations).
- A **Video** has many **Bookmarks / Timestamps** (pinned to the video runtime, optionally tagged to a specific puzzle).
- A **Video** captures optional **Intro Music** and **Outro Music** track credits.
- A **Puzzle** has one or more **Setters** (supporting collaborative builds).
- A **Puzzle** has one **Category** and one or more **Variants / Constraints** (Thermo, Arrow, Killer, Fog of War, German Whispers, etc.).
- A **Puzzle** has one or more **Puzzle Links** (1:N), capturing diverse platform and solve destinations (SudokuPad, Logic Masters Germany, Penpa, Times Puzzles, Cracking the Cryptic App, or other custom sources).
- A **Puzzle** has an **`access_level`** (`public` vs. `exclusive`). Most puzzles are publicly available, but some are Patreon exclusive. For exclusive puzzles, the name, setter, category, difficulty score, and constraints/rules remain visible, but direct links to the actual puzzle are suppressed.
- Public videos play via YouTube embeds; patron-exclusive videos route solvers to their parent Patreon release.

---

## 2. URL Strategy & Slug Conventions
- **Videos:** Slug generated from the video title (e.g., `/videos/beware-the-sliding-horses`). If collisions occur, append a unique hash or date suffix.
- **Puzzles:** Slug generated from `{puzzle_title}-{primary_setter_name}` (e.g., `/puzzles/chessboard-phistomefel`).
- **Canonical URLs:** All puzzle play links must resolve to full, canonical URLs (`sudokupad.app/...`, `penpa-edit/...`) prior to storage. Shortened links (`tinyurl.com`, `bit.ly`) must never be stored raw.

---

## 3. Database Schema (PostgreSQL)

### 3.1. Entity Tables
1. **`hosts`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `name`: `VARCHAR(100) NOT NULL`
   - `slug`: `VARCHAR(120) NOT NULL UNIQUE`
   - `avatar_url`: `VARCHAR(512) NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

2. **`categories`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `name`: `VARCHAR(100) NOT NULL UNIQUE`
   - `slug`: `VARCHAR(120) NOT NULL UNIQUE`
   - `description`: `TEXT NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

3. **`constraints`** *(Variants)*
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `name`: `VARCHAR(100) NOT NULL UNIQUE`
   - `slug`: `VARCHAR(120) NOT NULL UNIQUE`
   - `description`: `TEXT NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

4. **`setters`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `name`: `VARCHAR(150) NOT NULL`
   - `slug`: `VARCHAR(180) NOT NULL UNIQUE`
   - `bio`: `TEXT NULL`
   - `patreon_url`: `VARCHAR(512) NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

5. **`patreon_posts`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `title`: `VARCHAR(255) NOT NULL`
   - `slug`: `VARCHAR(280) NOT NULL UNIQUE`
   - `patreon_url`: `VARCHAR(512) NOT NULL`
   - `published_at`: `TIMESTAMPTZ NOT NULL`
   - `notes`: `TEXT NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

6. **`videos`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `patreon_post_id`: `BIGINT NULL REFERENCES patreon_posts(id) ON DELETE SET NULL`
   - `youtube_video_id`: `VARCHAR(32) NULL UNIQUE`
   - `video_type`: `VARCHAR(30) NOT NULL DEFAULT 'youtube'` (values: `youtube`, `youtube_short`, `live_stream`)
   - `access_level`: `VARCHAR(30) NOT NULL DEFAULT 'public'` (values: `public`, `exclusive`)
   - `patreon_post_url`: `VARCHAR(512) NULL`
   - `title`: `VARCHAR(255) NOT NULL`
   - `slug`: `VARCHAR(280) NOT NULL UNIQUE`
   - `published_at`: `TIMESTAMPTZ NOT NULL`
   - `duration_seconds`: `INTEGER NOT NULL DEFAULT 0`
   - `view_count`: `BIGINT NOT NULL DEFAULT 0`
   - `stats_synced_at`: `TIMESTAMPTZ NULL`
   - `youtube_thumbnail_url`: `VARCHAR(512) NULL`
   - `intro_music`: `VARCHAR(255) NULL`
   - `outro_music`: `VARCHAR(255) NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

7. **`puzzles`**
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `category_id`: `BIGINT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT`
   - `title`: `VARCHAR(255) NOT NULL`
   - `slug`: `VARCHAR(280) NOT NULL UNIQUE`
   - `access_level`: `VARCHAR(30) NOT NULL DEFAULT 'public'` (values: `public`, `exclusive`)
   - `rules_raw`: `TEXT NULL`
   - `difficulty_score`: `NUMERIC(3, 1) NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

8. **`puzzle_links`** *(Platform & Solve URLs)*
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `puzzle_id`: `BIGINT NOT NULL REFERENCES puzzles(id) ON DELETE CASCADE`
   - `link_type`: `VARCHAR(50) NOT NULL` (references `link_types.slug`, default platforms: `sudokupad`, `logic_masters_germany`, `penpa`, `times_puzzles`, `ctc_app`, `other`)
   - `url`: `VARCHAR(1024) NOT NULL`
   - `title`: `VARCHAR(150) NULL`
   - `order_column`: `SMALLINT NOT NULL DEFAULT 1`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

9. **`link_types`** *(Platform & Solve URL Taxonomies)*
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `name`: `VARCHAR(100) NOT NULL UNIQUE`
   - `slug`: `VARCHAR(100) NOT NULL UNIQUE`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

10. **`bookmarks`** *(Video Timecodes & Annotations)*
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `video_id`: `BIGINT NOT NULL REFERENCES videos(id) ON DELETE CASCADE`
   - `puzzle_id`: `BIGINT NULL REFERENCES puzzles(id) ON DELETE SET NULL`
   - `timestamp_seconds`: `INTEGER NOT NULL`
   - `label`: `VARCHAR(150) NOT NULL`
   - `created_at`, `updated_at`: `TIMESTAMPTZ NOT NULL`

### 3.2. Pivot Tables
1. **`video_hosts`**: `video_id` (FK), `host_id` (FK) -> Primary Key `(video_id, host_id)`.
2. **`video_puzzles`**:
   - `id`: `BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY`
   - `video_id`: `BIGINT NOT NULL REFERENCES videos(id) ON DELETE CASCADE`
   - `puzzle_id`: `BIGINT NOT NULL REFERENCES puzzles(id) ON DELETE CASCADE`
   - `order_in_video`: `SMALLINT NOT NULL DEFAULT 1`
   - `start_seconds`: `INTEGER NULL`
   - `solve_duration_seconds`: `INTEGER NULL`
   - Unique constraint: `(video_id, puzzle_id)`
3. **`puzzle_setters`**: `puzzle_id` (FK), `setter_id` (FK) -> Primary Key `(puzzle_id, setter_id)`.
4. **`puzzle_constraints`**: `puzzle_id` (FK), `constraint_id` (FK) -> Primary Key `(puzzle_id, constraint_id)`.

---

## 4. Search Implementation
- Global unified search available across the website via `/search` and persistent header search bar with `/` keyboard shortcut.
- Cross-entity catalog indexing and matching across:
  - **Videos:** title, intro/outro music track credits, host names, and attached puzzles.
  - **Puzzles:** title, rules text, setters, puzzle category, and constraint variants.
  - **Setters:** author name and bio.
  - **Puzzle Types (Categories):** category name and description.
  - **Variants (Constraints):** constraint name and description.
  - **Hosts:** host name and associated video catalog.
- Category tabs filter results dynamically (`All`, `Videos`, `Puzzles`, `Setters`, `Puzzle Types`, `Variants`, `Hosts`) with live counts.
- Empty search state provides guided discovery chips and keyword suggestions.

---

## 5. Ingestion, Background Jobs & Scheduling

### 5.1. Initial Historical Migration
- Artisan command: `php artisan ctc:import-sheet {path/to/catalogue.csv}`
- Seeds past videos, setters, rules, and canonical puzzle links from the community Google Sheet CSV export.

### 5.2. YouTube API Sync (New Public Videos)
- Artisan command: `php artisan ctc:sync-youtube {--all} {--limit=} {--dry-run} {--force}`
- Uses `playlistItems.list` on the CTC channel uploads playlist (`UUC-UOdK8-mIjxBQm_ot1T-Q`) and `videos.list`.
- Ingests public video records and core metadata (`title`, `slug`, `published_at`, `duration_seconds`, `view_count`, `youtube_thumbnail_url`, `video_type = 'youtube'|'youtube_short'|'live_stream'`, `is_finalized = false`).
- Does not ingest descriptions or auto-associate puzzles; puzzle and bookmark curation is handled by editors in Filament.

### 5.3. Patreon Post & Exclusive Entries (Manual & Automated Ingestion)
- Created via Filament v3 `PatreonPostResource` (`title`, `slug`, `patreon_url`, `published_at`, `notes`).
- Features automated metadata fetching (`PatreonService`) via Patreon's public post endpoint (`https://www.patreon.com/api/posts/{postId}`), which automatically parses the post ID from any Patreon URL and auto-populates the post title, exact ISO 8601 publication timestamp, and teaser summary.
- Videos attached to Patreon posts can be created directly or associated via `VideosRelationManager`.
- Each video defines its own `access_level`:
  - `public`: Standard or public companion video attached to the release.
  - `exclusive`: Patron-only video; routes solvers to the Patreon post.

### 5.4. Tiered View Count Sync Schedule
- Artisan command: `php artisan ctc:update-views {--tier=all}`
- Batches up to 50 video IDs per call via `videos.list?part=statistics` (costs 1 quota unit per 50 videos).
- Schedule configuration (`routes/console.php`):
  - **Tier 1 (New, < 7 days old):** `everyThreeHours()->withoutOverlapping()`
  - **Tier 2 (Recent, 8–30 days old):** `dailyAt('02:00')->withoutOverlapping()`
  - **Tier 3 (Archive, > 30 days old):** `weeklyOn(0, '03:00')->withoutOverlapping()`

---

## 6. Frontend & User Interface Specifications

### 6.1. Landing Page (`/`)
- **Hero Video:** Highlights the latest video upload without tagging it as a featured video (preserving all other access and video format tags). Public videos render a playable YouTube embed; exclusive videos suppress the player and render a thumbnail preview with a "Watch on Patreon" outbound link. Includes host badges, duration, publish date, and linked puzzles.
- **Dynamic Stats Bar:** Live counters (total cataloged videos, total puzzles, distinct setters indexed).
- **Latest Feed:** Grid of recent videos with explicit visual badges distinguishing `Public` vs. `Patron Exclusive`, alongside video format badges (`Live Stream`, `Short`, `YouTube`). Older cards display thumbnail previews where available.

### 6.2. Video Detail View (`/videos/{slug}`)
- **Conditional Player:**
  - `public`: Render embedded YouTube player controlled via an Alpine.js component (`x-data="youtubePlayer('...')"`).
  - `exclusive`: YouTube player iframe is completely suppressed to prevent unauthorized playback. Displays the video's thumbnail preview card with a Patreon Exclusive banner and a primary "Watch on Patreon" outbound button linking to the Patreon post.
- **Patreon Companion Callout:** If a public video is attached to a Patreon post, displays the post title and outbound link to the Patreon release.
- **Music Credits:** Displays intro and outro music track names where tagged.
- **Interactive Bookmarks:**
  - Render list of bookmarks sorted chronologically (`timestamp_seconds ASC`).
  - For public videos, clicking a bookmark executes `player.seekTo(seconds, true)` and scrolls smoothly to the player without page reload.
  - Formats timestamps as `HH:MM:SS`, omitting `HH` if hours is zero and omitting `MM` if both hours and minutes are zero (e.g. `1:01:05`, `11:20`, or `45`).
- **Linked Puzzle Cards:**
  - Puzzle title, category badge, setter link(s), constraint pills.
  - External Solve / Platform Links: renders all attached puzzle links (`puzzle->links`) with platform tags and custom labels (SudokuPad, Logic Masters Germany, Penpa, Times Puzzles, etc.).
  - For exclusive puzzles: direct play links are suppressed and replaced with a **"Patron Exclusive Puzzle"** badge, keeping the title, setter, constraints, and rules visible while directing users to Patreon.
  - Collapsible rules drawer to avoid spoilers, formatted with trimmed rules text so no blank line appears at the top when expanded.

### 6.3. Video Directory (`/videos/`)
- Public catalog page listing all videos stored in the database in reverse chronological order (`published_at DESC`, `id DESC`).
- Video cards display:
  - Video thumbnail image (served in bandwidth-friendly WebP format via YouTube CDN, with sleek fallback placeholder if unavailable).
  - Video title / name with direct link to `/videos/{slug}`.
  - Assigned host(s) (Simon Anthony, Mark Goodliffe, etc.).
  - Puzzle type / category (e.g. Variant Sudoku, Classic Sudoku, Crossword, GAS) derived from linked puzzles.
  - Video length / duration formatted with omitted non-existent units (`H:MM:SS`, `M:SS`, or `SS`).
  - Total view count formatted with commas (e.g. `98,765 views`).
  - Formatted publish date and access badges (`Public` vs `Patron Exclusive`, `Live Stream`, `Short`).
  - If a video is marked as finalized (`is_finalized` is true), the tile features a slight, refined border glow (`border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]`).
- Responsive 3-column card grid with pagination support (`paginate(24)`).
- For authenticated administrators, an **Edit** shortcut button appears on each video card thumbnail that links directly to `/videos/{id}/edit` (`/admin/videos/{id}/edit`).

### 6.4. Top 24 Most Viewed Videos (`/videos/top`)
- Dedicated ranking page highlighting the all-time top 24 most viewed videos in the catalog (`view_count DESC`, `published_at DESC`).
- Distinctive rank badges (#1 Gold, #2 Silver, #3 Bronze, and #4-#24 sleek pill badges).
- Full metadata parity with `/videos/` (thumbnails, durations, host badges, puzzle categories, view counts, and finalized glow).
- Permanent redirect from `/videos/most-viewed` to `/videos/top`.

### 6.5. Top 24 Longest Solves (`/videos/longest`)
- Dedicated ranking page highlighting the all-time top 24 longest dedicated puzzle solves, deep-dives, and masterclasses (`duration_seconds DESC`, `published_at DESC`).
- Excludes live stream replays (`video_type != 'live_stream'`).
- Distinctive rank badges (#1 Gold, #2 Silver, #3 Bronze, and #4-#24 sleek pill badges) with highlighted duration counters.
- Full metadata parity with `/videos/` and direct links to `/videos/{slug}`.

### 6.6. Random Video Route (`/videos/random`)
- Direct shortcut accessible from site navigation that queries a random video in the catalog and redirects to `/videos/{slug}`.
- Gracefully falls back to `/videos` if the database contains no videos.

### 6.7. Puzzle Directory (`/puzzles`)
- Livewire 3 reactive interface backed by Meilisearch.
- Faceted multi-select pills for constraints, dropdown for category, search input with typo tolerance for setters/titles.

### 6.8. Privacy Policy & Compliance (`/privacy`)
- Accessible public privacy disclosures in compliance with GDPR (EU/UK), CCPA/CPRA (California), and ePrivacy Directive.
- **Two-Click Privacy Façade:** Embedded YouTube video players on the home page and video detail page use an interactive façade (thumbnail and custom play button). No external iframes or YouTube API scripts load until the user affirmatively clicks to play or seeks to a timestamp, preventing premature IP address transmission to third parties.
- **Self-Hosted Typography:** All web fonts (Plus Jakarta Sans, JetBrains Mono) are hosted locally (`/fonts/`), eliminating third-party Google Fonts CDN requests.
- **Strictly Necessary Cookies Only:** The public application does not deploy advertising, marketing, or tracking cookies; uses only functional Laravel session/CSRF tokens.
- **Navigation & Access Controls:** Top navigation bar renders the "Admin Panel" link exclusively for authenticated administrators. Site-wide footer links to Home, All Videos, Most Viewed, Longest, Random, Privacy Policy, and a direct Login link (replacing the previous public Admin link).

---

## 7. Administrative Requirements (Filament v3)
- **`PatreonPostResource`**:
  - Fields for title, auto-generated slug, Patreon post URL, published date, and editorial notes.
  - Automated Patreon metadata and timestamp fetching action on blur or button click via `PatreonService`.
  - Includes `VideosRelationManager` to create or associate both public and exclusive videos directly under the Patreon post.
- **`VideoResource`**:
  - Organized in a single-column layout (1 column on schema, `columnSpanFull` on "Video Source and Metadata", "Music Information", and "Bookmarks and Timestamps" sections) to provide optimal horizontal space and cleaner vertical flow.
  - Fields for video type (`youtube`, `youtube_short`, `live_stream`), access level (`public`, `exclusive`), and parent Patreon post selection (`patreon_post_id`).
  - Automated YouTube ingestion via ID/URL (supports standard watch, short, and `/live/` streams).
  - Dropdown selectors for intro and outro music with auto-complete from existing tracks and on-the-fly creation.
  - Multi-select search and on-the-fly creation for `hosts` (users can add a new host simply by typing their name in the dropdown search and selecting it, as well as via modal option creation).
  - Duration input and display formatted as `HH:MM:SS` (omitting units that do not exist: if shorter than 60 minutes, omit `HH`, if shorter than 60 seconds, omit `MM`) across forms, tables, and views (while preserved as total integer seconds in the database).
  - Checkbox for `is_finalized` (`Video is Finalized`) indicating an administrator has reviewed the video and verified all tagging, metadata, puzzles, and timestamps are complete.
  - RelationManager for `video_puzzles` (ordering and timing, with `start_seconds` and `solve_duration_seconds` formatted and input as timecodes omitting non-existent units).
  - Filament `Repeater` for `bookmarks` (timestamp input supporting flexible `HH:MM:SS`, `MM:SS`, or seconds input calculated back to seconds on save and displayed omitting units that do not exist; label selectable from previous labels dropdown with on-the-fly creation, optional puzzle association strictly limited to puzzles tagged to the current video; tag and notes fields removed; always-visible non-collapsible single-row layout with no top divider line across the tile, delete icon positioned on the right-hand side, bookmarks grid strictly displays the 3 fields in a row across all screen sizes using a 12-column span with 2 columns for timestamp, and an equal 5 columns each for "Bookmark Label" and "Associated Puzzle" with matched compact 13px font and auto-expanding dropdown width; centered "+" sign button on a new row for adding bookmarks).
  - All timestamps and durations across the entire application (video durations, bookmark moments, puzzle start times, and solve durations) are displayed consistently in `HH:MM:SS` timecode format, omitting units that do not exist (e.g. `1:30:15` for hours, `45:15` for under 60 minutes, `45` for under 60 seconds, `0` for zero).
- **`UnfinalizedVideos` Page (`/admin/unfinalized-videos`)**:
  - Dedicated administrative queue page in the navigation sidebar displaying all videos where `is_finalized` is `false`.
  - Dynamic navigation badge indicating the total count of unfinalized videos awaiting review.
  - Minimalist focused table displaying only video title, access level, and video type.
  - Video title and row clicks directly link to the admin edit page (`/admin/videos/{id}/edit`) for that video.
  - Inline and bulk `Mark Finalized` actions to immediately mark reviewed videos as finalized.
  - Complementary `Unfinalized` review tab and header queue shortcut on `ListVideos`.
- **`PuzzleResource`**:
  - Fields for title, slug, category, access level (`public`, `exclusive`), difficulty score, optional raw rules text (no required constraint and no default text), and a `Repeater` for `links` (`link_type`, `url`, optional `title`).
  - Dropdown for `category` allows adding new categories directly on the fly by typing the name in the search field or via modal option creation.
  - Dropdown for `link_type` allows adding new link types directly on the fly via search or modal creation, identical to setters and constraints.
  - Multi-select tags for `setters` and `constraints` with inline search-to-create and modal creation.
  - Consistent drop-down behavior across open-ended entities: all open-ended taxonomy and metadata dropdowns (hosts, categories, setters, constraints, link types, and bookmark labels) allow typing new names directly into the search field to create them instantly on-the-fly, while video bookmark puzzle associations are strictly scoped to the puzzles tagged to that specific video.
  - Configured in `PuzzleForm`, `PuzzlesTable`, and within `PuzzlesRelationManager` on `VideoResource` (using wide `7xl` modals for creation and editing).
- **Taxonomies**: Minimalist CRUD resources for Hosts, Setters, Categories, Constraints, and Link Types (`LinkTypeResource`).

---

## 8. Verification & Testing Instructions for Antigravity Agents
1. **Migrations & Seeders:** Run all migrations against PostgreSQL; verify all cascade and foreign key constraints.
2. **Seeders:**
   - Seed core hosts: Simon Anthony, Mark Goodliffe.
   - Seed core categories: Variant Sudoku, Classic Sudoku, Crossword, GAS.
   - Seed top constraints: Thermo, Killer, Arrow, German Whispers, Renban, Fog of War.
   - Seed 3 test videos:
     - Public single-puzzle video with 4 bookmarks.
     - Public multi-puzzle video (GAS format) with 2 attached puzzles.
     - Patreon exclusive video with Patreon URL and no YouTube embed.
3. **Pest Tests Required:**
   - Verify `video_type === 'patreon'` videos omit the YouTube embed and link directly to the Patreon post.
   - Verify public video bookmarks render sequentially and bind seek triggers.
   - Verify puzzle slugs default to `{title}-{setter}` pattern.
   - Verify `ResolveCanonicalUrlJob` expands shortlinks to their target destination.
   - Verify Meilisearch query handles misspelled constraint and setter terms.
   - Verify `ctc:update-views` chunks YouTube requests in batches of 50.
