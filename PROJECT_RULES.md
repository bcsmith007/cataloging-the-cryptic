
## 1. Definition of Done (DoD)
You are not permitted to close out a task or signal completion until the following conditions are met completely:

1. **Compilation Check:** Code must pass standard static analysis checks with zero syntax errors.
2. **Automated Testing:** You must write a corresponding Feature/Integration test asserting the new logic. If the component modifies a tenant table, you must write a negative integration test proving that a separate, un-authenticated tenant cannot access or view that resource row.
3. **Terminal Verification:** You must execute `php artisan test` inside the terminal window and verify that the entire suite passes with 100% success before declaring victory.

---

## 2. Think Before Coding

Don't assume. Don't hide confusion. Surface tradeoffs.

Before implementing:

State your assumptions explicitly. If uncertain, ask.
* If multiple interpretations exist, present them - don't pick silently.
* If a simpler approach exists, say so. Push back when warranted.
* If something is unclear, stop. Name what's confusing. Ask.

---

## 3. Simplicity First

Minimum code that solves the problem. Nothing speculative.

* No features beyond what was asked.
* No abstractions for single-use code.
* No "flexibility" or "configurability" that wasn't requested.
* No error handling for impossible scenarios.
* If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

---

## 4. Surgical Changes

Touch only what you must. Clean up only your own mess.

When editing existing code:

* Don't "improve" adjacent code, comments, or formatting.
* Don't refactor things that aren't broken.
* Match existing style, even if you'd do it differently.
* If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:

* Remove imports/variables/functions that YOUR changes made unused.
* Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

---

## 5. Goal-Driven Execution

Define success criteria. Loop until verified.

Transform tasks into verifiable goals:

* "Add validation" → "Write tests for invalid inputs, then make them pass"
* "Fix the bug" → "Write a test that reproduces it, then make it pass"
* "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

---

## 6. Coding Style

Code should be clean, well documented, and easy to understand.

Documentation:
* All code should be well documented
* Documentation should follow PHPDoc formatting
* Documentation should include explanations of the purpose of the code, how it works, and how to use it
* Documentation should be kept up to date with code changes
* Documentation should be concise but comprehensive
* If a file is not currently documented, you should document it
* If you don't change a code section, don't change the documentation

Consistency:
* All code should follow the same coding style
* All code should use the same naming conventions
* All code should use the same formatting
* All code should use the same error handling strategy
* All code should use the same testing strategy

Code and Site Style:
* Do not use emojis anywere in the code, site or documentation
* If you see an emoji, remove it and replace with appropriate text

---

## 7. File Locations

* All files for this project are in the `/Users/bcsmith/Sites/ctc` directory
* No other files should be used, edited, or referenced without explicit permission
