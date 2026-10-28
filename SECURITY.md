# Security & Defensive Implementation Standards
**Project:** Cataloging the Cryptic (`catalogingthecryptic.com`)  
**Stack:** Laravel 13, PHP 8.5 (8.5.12+), PostgreSQL / SQLite, Filament v5, Livewire 4, Alpine.js, Tailwind CSS v4, Chart.js, KaTeX

---

## 1. PHP Runtime Hardening & PHP2Shell Mitigation (CVE-2026-109618 / CVE-2026-109619)
**PHP2Shell** (**CVE-2026-109618** and **CVE-2026-109619**, CVSS 9.2 Critical) is a family of unauthenticated Remote Code Execution (RCE) and memory disclosure vulnerabilities in the core PHP runtime engine:
* **CVE-2026-109618:** Out-of-memory (OOM) state corruption during multipart/request parsing leaving internal structures partially initialized.
* **CVE-2026-109619:** Use-after-free (UAF) in worker process state management (specifically via PHP's session upload-progress tracking and PHP 8.5 fatal error backtraces) allowing subsequent requests on the same PHP-FPM worker to read or manipulate freed request memory.

### Mandatory Runtime & Platform Controls
1. **Patched PHP Runtime Requirement:**
   * All local, staging, and production environments **must** run a patched PHP binary: **PHP >= 8.5.12** (or `>= 8.4.27` / `>= 8.3.36`).
   * `composer.json` enforces `"php": "^8.3.36 || ^8.4.27 || ^8.5.12"` so deployments fail fast if a host runs an unpatched PHP binary.
   * After upgrading PHP packages on any server, **restart `php-fpm`** (or the web server process manager) immediately to flush worker memory states.
2. **Global `php.ini` / PHP-FPM Pool Hardening:**
   Configure the following directives globally in `php.ini` or `php-fpm` pool definitions (runtime `ini_set()` or `.user.ini` overrides execute too late in the request lifecycle):
   ```ini
   session.upload_progress.enabled = 0
   fatal_error_backtraces = 0
   register_argc_argv = 0
   expose_php = Off
   post_max_size = 8M
   max_input_vars = 1000
   ```
3. **Application-Level Defense-in-Depth (`SetSecurityHeaders` Middleware):**
   * `app/Http/Middleware/SetSecurityHeaders.php` explicitly rejects any HTTP request containing a `PHP_SESSION_UPLOAD_PROGRESS` query parameter, body field, or cookie with `HTTP 400 Bad Request`.

---

## 2. Known Laravel, Livewire & Symfony Advisory Mitigations
* **Livewire Authorization & Synthesis Hardening (`GHSA-mhf7-xp6r-6qrc` / `PKSA-xhv3-nr94-h8db`):**
  * Maintain `livewire/livewire` at **`>= 4.4.8`** to prevent authorization bypasses in `#[Authorize]` via reordered parameter keys and `#[Json]` execution order.
* **Symfony Mime Header Injection (`CVE-2026-45070` / `PKSA-wtxr-p26d-nn42`):**
  * Maintain `symfony/mime` (and related `symfony/*` packages) at **`>= 8.1.10`** to prevent email header injection via non-token characters in MIME parameter names.
* **Query String `argv` Injection (`CVE-2024-52301`):**
  * Keep `register_argc_argv = 0` in `php.ini` and `laravel/framework` at **`>= 13.33.0`** so crafted query strings cannot manipulate environment detection when running under web SAPIs.

---

## 3. Environment & API Key Protection
* **Server-Side Exclusivity:** All external API communications—including YouTube Data API v3 (`YouTubeService`) and Patreon post metadata fetching (`PatreonService`)—must occur strictly server-side within console commands or dedicated service classes.
* **No Client Leaks:** Never prefix third-party secrets with `VITE_` or expose `YOUTUBE_API_KEY` to client-side bundles. Read secrets exclusively through `config('services.youtube.key')` rather than `env()` outside configuration files.
* **External URL Sanitization & Scheme Validation (`UrlSanitizer`):**
  * All YouTube redirect URLs (`youtube.com/redirect?q=...`) pasted into forms or model attributes are automatically unwrapped via `App\Services\UrlSanitizer`.
  * `UrlSanitizer::unwrapYouTubeRedirect()` strictly validates that extracted target URLs begin with `http://` or `https://`, rejecting `javascript:`, `data:`, `vbscript:`, or other unsafe URI schemes.
* **API Quota & Fault Resilience:** Wrap all YouTube Data API and Patreon API calls in explicit `try-catch` blocks with HTTP timeouts (`timeout(8)`) and graceful fallback handling so quota errors (HTTP 403) or rate limits (HTTP 429) never leak stack traces or crash sync runs.

---

## 4. Eloquent, Database & Data Layer
* **Enforce Strict Eloquent Execution:** `Model::shouldBeStrict(! app()->isProduction());` is enabled in `app/Providers/AppServiceProvider.php` to catch lazy-loading (N+1) queries, mass-assignment of unfillable attributes, and access to missing attributes during development and testing.
* **Mass Assignment Defense:** Define explicit `$fillable` arrays on every Eloquent model; never use unconstrained `$guarded = []`.
* **SQL Injection & Parameterized Bindings:** Never concatenate user input into `whereRaw`, `selectRaw`, or `orderByRaw`. Always pass user-supplied search strings via parameterized bindings (`whereRaw('LOWER(title) LIKE ?', [$term])`) and map sort keys/directions against strict allowlists (`match ($sort)` and `in_array($direction, ['asc', 'desc'], true)`).

---

## 5. Filament v5 & Admin Panel Hardening
* **Explicit Panel Gate Contract:** `App\Models\User` implements `Filament\Models\Contracts\FilamentUser`, restricting `/admin` panel access via `canAccessPanel()` strictly to users with `is_admin === true`.
* **Audit Trail Tracking (`HasUserTracking`):** Content models (`Video`, `Puzzle`, `Setter`, `Constraint`, `Category`, `PatreonPost`, `CtcApp`, `VideoPuzzle`, etc.) automatically record `created_by_id` and `updated_by_id`.

---

## 6. Livewire, Markdown & Front-End Security
* **Safe Markdown Rendering (XSS Prevention):**
  * Whenever rendering database-stored Markdown content (`$setter->bio`, `$constraint->description`, `$category->description`, `$app->description`) with `{!! Str::markdown(...) !!}`, always pass `['html_input' => 'strip', 'allow_unsafe_links' => false]` to strip raw HTML/script tags and block `javascript:` or `data:` links.
  * Render plain-text fields (video titles, puzzle titles, rules text, host names) using escaped Blade syntax (`{{ $value }}`).
* **Production Security Headers (`SetSecurityHeaders`):**
  * Scoped to production (`if (! app()->isProduction()) { return $next($request); }`) so local HTTP development (`ctc.test`) is unaffected.
  * Enforces `Strict-Transport-Security`, `Content-Security-Policy`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `X-Content-Type-Options: nosniff`, and `X-Frame-Options: SAMEORIGIN`.

---

## 7. Local Quality & Dependency Verification
Run automated security and test checks prior to deployment:
```bash
composer audit
npm audit
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

---

## 8. Privacy, GDPR & CCPA Compliance Standards
* **Two-Click Privacy Facade for Embedded Media:** Public YouTube embeds use an interactive poster facade. External iframes (`youtube-nocookie.com`) and the YouTube Iframe API load only after affirmative user interaction (clicking play or jumping to a timestamp bookmark).
* **Consent-Gated Analytics:** Google Analytics loads only when the visitor explicitly grants consent via the privacy banner (`ctc_cookie_consent=accepted`).
* **Self-Hosted Assets:** Web fonts (`/fonts/`), Tailwind CSS, Chart.js, and KaTeX are bundled locally via Vite (`resources/js/app.js`, `resources/css/app.css`).
