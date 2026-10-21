# Security & Defensive Implementation Standards
**Project:** Cracking the Cryptic Catalog (catalogingthecryptic.com)  
**Stack:** Laravel 12, PHP 8.5, PostgreSQL, Filament v3, Livewire 3, Alpine.js, Tailwind CSS, Meilisearch

---

## 1. Environment & API Key Protection
* **Server-Side Exclusivity:** All external API communications—including YouTube Data API v3, Patreon scraping, and SudokuPad metadata resolution—must occur strictly server-side within queued jobs, console commands, or dedicated service classes.
* **No Client Leaks:** Never prefix third-party secrets with `VITE_` or expose `YOUTUBE_API_KEY` to client-side bundles.
* **External URL Validation & SSRF Prevention:** When fetching puzzle links or expanding shortened URLs found in video descriptions, strictly validate URLs using `filter_var($url, FILTER_VALIDATE_URL)`. Restrict outbound requests to `http` and `https` schemes and reject private IP ranges (127.0.0.1, 10.0.0.0/8, 192.168.0.0/16, 169.254.169.254).
* **API Quota & Fault Resilience:** Wrap all YouTube Data API calls in explicit try-catch blocks handling HTTP 403 (Quota Exceeded) and HTTP 429 without throwing uncaught exceptions or terminating background sync queues.

---

## 2. Eloquent, PostgreSQL & Data Layer
* **Enforce Strict Eloquent Execution:** Enforce `Model::shouldBeStrict(! app()->isProduction());` within `app/Providers/AppServiceProvider.php` to immediately catch lazy-loading (N+1), unguarding violations, and missing attributes during development.
* **Mass Assignment Defense:** Define explicit `$fillable` arrays on every model; do not use unconstrained `$guarded = []`.
* **SQL Injection & Raw Queries:** When utilizing raw PostgreSQL clauses (`to_tsvector`, full-text operators, or custom expressions), never concatenate unvalidated user input. Always use parameterized PDO bindings:
  `$query->whereRaw("to_tsvector('english', title) @@ plainto_tsquery('english', ?)", [$search]);`
* **PostgreSQL Constraints:** Enforce data integrity at the database engine level via unique indexes (e.g., `youtube_id` on videos, slug uniqueness, composite unique constraints on many-to-many pivots).

---

## 3. Filament v3 & Admin Panel Hardening
* **Explicit Panel Gate Contract:** Implement `Filament\Models\Contracts\FilamentUser` on the User model and register explicit Laravel Policies for every administrative resource (Videos, Puzzles, Setters, Constraints). Never rely purely on UI visibility for access control.
* **Form Upload Sanitization:** Secure any file uploads by validating MIME types, enforcing maximum file sizes, and storing uploads on private or non-executable disks.

---

## 4. Livewire & Front-End Security
* **Livewire Public Properties:** Livewire public properties are client-editable. Never rely on a client-submitted ID or state without running server-side model checks and Laravel Authorization (`$this->authorize(...)`) inside the mutation action.
* **Avoid Model Hydration Traps:** Avoid binding entire sensitive Eloquent models to Livewire public properties. Prefer binding primitive properties (`string`, `int`, `bool`) to prevent wire tampering.
* **Blade XSS Prevention:** Render video descriptions, setter notes, and puzzle rules using escaped Blade tags (`{{ $text }}`). Strictly avoid unescaped Blade (`{!! $text !!}`) and Alpine `x-html` unless the raw HTML is explicitly parsed through a backend sanitizer whitelist (e.g., `Purifier`).

---

## 5. Meilisearch Security
* **Key Scoping:** The `MEILI_MASTER_KEY` is reserved strictly for backend synchronization via Laravel Scout.
* **Front-End Search Isolation:** If direct browser-to-engine querying is introduced, generate and supply a scoped, read-only search key restricted to index-level query operations.

---

## 6. Local Quality & Dependency Verification
* Maintain clean dependency security audits before committing:
  `composer audit`
  `npm audit`

---

## 7. Privacy, GDPR & CCPA Compliance Standards
* **Self-Hosted Assets:** All fonts and front-end assets must be served locally (e.g. from `/fonts/` and local bundles). Never load fonts from Google Fonts or third-party CDNs to prevent unauthorized IP address transfers to external servers without prior consent.
* **Two-Click Privacy Façade for Embedded Media:** YouTube and other third-party video embeds must use an interactive privacy façade (poster image with play button). Do not load external `iframe` or YouTube API scripts on initial page render. Connections to `youtube-nocookie.com` must only initiate upon affirmative user click.
* **Strictly Necessary Cookies:** Restrict public cookies to strictly necessary functional tokens (`laravel_session`, `XSRF-TOKEN`). Do not introduce advertising, marketing, or cross-site tracking cookies.
* **Privacy Policy Disclosures:** Maintain an up-to-date, public Privacy Policy (`/privacy`) detailing data processing practices, legitimate interests for security server logs, CCPA non-sale declarations, and GDPR data subject rights.
* **Log Minimization:** Limit web server access log retention to a maximum of 30 days and rotate logs automatically.
