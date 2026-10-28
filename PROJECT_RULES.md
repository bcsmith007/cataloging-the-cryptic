# Project Engineering Rules — Cataloging the Cryptic

## 1. Definition of Done (DoD)
You are not permitted to close out a task or signal completion until the following conditions are met completely:

1. **Code Formatting & Static Checks:** Run `vendor/bin/pint --dirty --format agent` whenever any PHP file is created or modified so all code matches project conventions with zero syntax errors.
2. **Automated Testing:** Write or update corresponding Feature/Unit tests asserting changed behavior and important failure modes.
3. **Terminal Verification:** Execute `php artisan test --compact` inside the terminal and verify that the test suite passes with 100% success before declaring completion.
4. **Dependency Security Audit:** When modifying `composer.json` or `package.json`, run `composer audit` and `npm audit` and verify zero unresolved advisories.

---

## 2. Think Before Coding

Don't assume. Don't hide confusion. Surface tradeoffs.

Before implementing:

* State your assumptions explicitly. If uncertain, ask.
* If multiple interpretations exist, present them - don't pick silently.
* If a simpler approach exists, say so. Push back when warranted.
* If something is unclear, stop. Name what's confusing. Ask.

---

## 3. Simplicity First

Minimum code that solves the problem. Nothing speculative.

* No features beyond what was asked.
* No abstractions for single-use code unless extracting a clear domain boundary (such as a testable service class).
* No "flexibility" or "configurability" that wasn't requested.
* No error handling for impossible scenarios.
* If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

---

## 4. Surgical Changes

Touch only what you must. Clean up only your own mess.

When editing existing code:

* Don't "improve" adjacent code, comments, or formatting unrelated to the task.
* Don't refactor things that aren't broken.
* Match existing style, even if you'd do it differently.
* If you notice unrelated dead code, mention it - don't delete it without asking.

When your changes create orphans:

* Remove imports/variables/functions that YOUR changes made unused.
* Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

---

## 5. Goal-Driven Execution

Define success criteria. Loop until verified.

Transform tasks into verifiable goals:

* "Add validation" -> "Write tests for invalid inputs, then make them pass"
* "Fix the bug" -> "Write a test that reproduces it, then make it pass"
* "Refactor X" -> "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

1. [Step] -> verify: [check]
2. [Step] -> verify: [check]
3. [Step] -> verify: [check]

---

## 6. Coding Style

Code should be clean, well documented, and easy to understand.

Documentation:
* All code should be well documented using PHPDoc blocks with explicit parameter and return types (including array shape annotations where helpful).
* Documentation should include explanations of the purpose of the code and how it works.
* Documentation should be kept up to date with code changes.
* If you don't change a code section, preserve existing comments and docstrings.

Consistency:
* Always use curly braces for control structures, even for single-line bodies.
* Use explicit return type declarations and type hints for all method parameters.
* Follow existing naming conventions, error handling, and PHPUnit testing patterns.

Code and Site Style:
* Do not use emojis anywhere in the code, site, or documentation.
* If you see an emoji, remove it and replace it with appropriate text.

---

## 7. File Locations

* All project files reside in the `/Users/bcsmith/Sites/ctc` directory.
* Stick to the existing directory structure; do not create new top-level directories without approval.

---

## 8. Environments, Security Hardening, and Front-End Assets

* **PHP2Shell & Runtime Requirements (CVE-2026-109618 / CVE-2026-109619):** Enforce patched PHP versions (`^8.3.36 || ^8.4.27 || ^8.5.12`) in `composer.json` and reject `PHP_SESSION_UPLOAD_PROGRESS` payloads in `SetSecurityHeaders`.
* **Scope Security Headers to Production Only:** In `app/Http/Middleware/SetSecurityHeaders.php`, bypass response security headers in non-production environments (`if (! app()->isProduction()) { return $next($request); }`) to avoid breaking local HTTP development (e.g., `ctc.test`).
* **Permissions-Policy:** Never include deprecated or non-standard directives such as `browsing-topics=()`.
* **Safe Markdown Rendering:** When rendering database fields via `{!! Str::markdown(...) !!}`, always pass `['html_input' => 'strip', 'allow_unsafe_links' => false]` to prevent stored XSS.
* **Unwrap YouTube Redirects (`UrlSanitizer`):** Ensure external URL inputs and model URL attributes unwrap YouTube redirect links via `App\Services\UrlSanitizer` and only unwrap `http://` or `https://` target destinations.
* **Standardize Front-End Assets with `@vite`:** Never hardcode paths to `/build/assets/...` in Blade templates. Always use `@vite(['resources/css/app.css', 'resources/js/app.js'])`. Never use the Tailwind Play CDN `<script>` tag.
* **Bundle Dependencies Locally:** All client-side dependencies (Chart.js, KaTeX, Alpine utilities) must be installed via npm (`package.json`) and imported in `resources/js/app.js` rather than loading external CDN scripts that violate the production Content Security Policy.
