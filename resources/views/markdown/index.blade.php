@extends('layouts.app')

@section('title', 'Markdown Guide & Formatting Tutorial | Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">

    {{-- Breadcrumb & Hero Header --}}
    <div class="space-y-4">
        <nav class="flex items-center gap-2 text-xs font-mono text-slate-400">
            <a href="{{ url('/') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <span class="text-slate-200">Documentation</span>
            <span>/</span>
            <span class="text-emerald-400 font-semibold">Markdown Guide</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 pb-6 border-b border-slate-800">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Formatting Reference &amp; Tutorial
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Markdown Formatting Guide
                </h1>
                <p class="text-sm sm:text-base text-slate-400 max-w-3xl leading-relaxed">
                    Learn how to format constraint definitions, puzzle rulesets, author biographies, and app descriptions with bullet points, arrows, coordinates, and emphasis across Cataloging the Cryptic.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="#playground" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Try Live Playground</span>
                </a>
            </div>
        </div>
    </div>

    {{-- 1-Click Symbol Copy Palette --}}
    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 font-mono">
                    Quick-Copy Puzzle Symbols &amp; Arrows
                </span>
            </div>
            <span class="text-[11px] text-slate-400 font-mono" id="copy-feedback-label">
                Click any symbol to copy directly to your clipboard
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            @php
                $quickSymbols = [
                    ['symbol' => '→', 'name' => 'Right arrow (imply / sequence)'],
                    ['symbol' => '←', 'name' => 'Left arrow'],
                    ['symbol' => '↑', 'name' => 'Up arrow'],
                    ['symbol' => '↓', 'name' => 'Down arrow'],
                    ['symbol' => '↔', 'name' => 'Bidirectional arrow'],
                    ['symbol' => '⇒', 'name' => 'Double right arrow'],
                    ['symbol' => '•', 'name' => 'Bullet point'],
                    ['symbol' => '✓', 'name' => 'Checkmark'],
                    ['symbol' => '★', 'name' => 'Star'],
                    ['symbol' => '≥', 'name' => 'Greater than or equal'],
                    ['symbol' => '≤', 'name' => 'Less than or equal'],
                    ['symbol' => '≠', 'name' => 'Not equal'],
                    ['symbol' => '±', 'name' => 'Plus-or-minus'],
                    ['symbol' => '∑', 'name' => 'Summation'],
                    ['symbol' => '≡', 'name' => 'Congruent / Modular equivalence'],
                    ['symbol' => '√', 'name' => 'Square root'],
                    ['symbol' => '∈', 'name' => 'Element of (set membership)'],
                    ['symbol' => '∞', 'name' => 'Infinity'],
                    ['symbol' => '○', 'name' => 'Circle (circle cell)'],
                    ['symbol' => '●', 'name' => 'Filled circle (Kropki dot)'],
                    ['symbol' => '◇', 'name' => 'Diamond'],
                    ['symbol' => '△', 'name' => 'Triangle'],
                ];
            @endphp

            @foreach ($quickSymbols as $item)
                <button
                    type="button"
                    class="quick-symbol-btn inline-flex items-center justify-center min-w-9 h-9 px-2.5 rounded-lg bg-slate-950/80 hover:bg-emerald-500/20 text-slate-200 hover:text-emerald-400 border border-slate-800 hover:border-emerald-500/40 text-base font-mono transition-all group relative cursor-pointer"
                    data-symbol="{{ $item['symbol'] }}"
                    title="{{ $item['name'] }} (Click to copy)"
                    aria-label="Copy {{ $item['symbol'] }}"
                >
                    <span>{{ $item['symbol'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Quick Jump Navigation --}}
    <div class="flex flex-wrap items-center gap-2 text-xs font-mono">
        <span class="text-slate-500">Jump to:</span>
        <a href="#typography" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">1. Text &amp; Emphasis</a>
        <a href="#lists" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">2. Bullet &amp; Numbered Lists</a>
        <a href="#arrows" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">3. Arrows &amp; HTML Entities</a>
        <a href="#callouts" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">4. Callout Quotes</a>
        <a href="#coordinates" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">5. Coordinates &amp; Code</a>
        <a href="#links-tables" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">6. Links &amp; Tables</a>
        <a href="#math" class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300 hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">7. Inline Math ($...$)</a>
        <a href="#playground" class="px-2.5 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-colors">8. Live Playground</a>
    </div>

    {{-- Tutorial Section 1: Text & Emphasis --}}
    <section id="typography" class="space-y-4 pt-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                1
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Text Formatting, Headings &amp; Emphasis
                </h2>
                <p class="text-xs text-slate-400">
                    Use asterisks or underscores for emphasis, and hashtags for hierarchical section titles.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-1">Copy</button>
                </div>
                <pre id="snippet-1" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">### Standard Sudoku Rules
Normal **Sudoku** rules apply. Each row, column, and 3x3 box must contain the digits 1 through 9 without *repetition*.

#### Variations
Use ~~strikethrough~~ for deprecated rules, or ***bold-and-italic*** for paramount restrictions.</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("### Standard Sudoku Rules\nNormal **Sudoku** rules apply. Each row, column, and 3x3 box must contain the digits 1 through 9 without *repetition*.\n\n#### Variations\nUse ~~strikethrough~~ for deprecated rules, or ***bold-and-italic*** for paramount restrictions.") !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Tutorial Section 2: Bullet & Numbered Lists --}}
    <section id="lists" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                2
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Bullet Lists &amp; Numbered Steps
                </h2>
                <p class="text-xs text-slate-400">
                    Prefix lines with asterisks (`*`) or hyphens (`-`) for bullet points, and numbers (`1.`, `2.`) for step-by-step rules.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-2">Copy</button>
                </div>
                <pre id="snippet-2" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">* **German Whispers:** Adjacent digits along green lines must differ by at least 5.
* **Renban Lines:** Digits along purple lines must be consecutive in any order:
  * No digits may repeat on a Renban line.
* **Kropki Pairs:**
  * White dot: Digits differ by exactly 1.
  * Black dot: Digits are in a 1:2 ratio.

1. First, deduce parity on row 1.
2. Next, disambiguate the center box.</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("* **German Whispers:** Adjacent digits along green lines must differ by at least 5.\n* **Renban Lines:** Digits along purple lines must be consecutive in any order:\n  * No digits may repeat on a Renban line.\n* **Kropki Pairs:**\n  * White dot: Digits differ by exactly 1.\n  * Black dot: Digits are in a 1:2 ratio.\n\n1. First, deduce parity on row 1.\n2. Next, disambiguate the center box.") !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Tutorial Section 3: Arrows & HTML Character Entities --}}
    <section id="arrows" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                3
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Arrows &amp; Mathematical Entities
                </h2>
                <p class="text-xs text-slate-400">
                    Use standard HTML entities or copy Unicode characters directly into your text.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN / ENTITIES)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-3">Copy</button>
                </div>
                <pre id="snippet-3" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">* **Arrow Sums:** Digits along the arrow sum to the bulb:
  * Cell A &rarr; Cell B &rarr; Bulb
* **Bidirectional:** Connection goes both ways (&harr;).
* **Parity Flow:** Odd digits &rArr; Low digits.
* **Range Restrictions:**
  * Box 1 sum &ge; 25
  * Box 9 sum &le; 18
  * Adjacent difference &ne; 1</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("* **Arrow Sums:** Digits along the arrow sum to the bulb:\n  * Cell A &rarr; Cell B &rarr; Bulb\n* **Bidirectional:** Connection goes both ways (&harr;).\n* **Parity Flow:** Odd digits &rArr; Low digits.\n* **Range Restrictions:**\n  * Box 1 sum &ge; 25\n  * Box 9 sum &le; 18\n  * Adjacent difference &ne; 1") !!}
                </div>
            </div>
        </div>

        {{-- Entity Reference Quick Table --}}
        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4 overflow-x-auto text-xs font-mono">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono mb-2">
                Common HTML Entities Reference
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="py-2 pr-4">Entity Code</th>
                        <th class="py-2 px-4">Result</th>
                        <th class="py-2 px-4">Entity Code</th>
                        <th class="py-2 px-4">Result</th>
                        <th class="py-2 px-4">Entity Code</th>
                        <th class="py-2 pl-4">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <tr>
                        <td class="py-2 pr-4 font-mono text-emerald-400">&amp;rarr;</td>
                        <td class="py-2 px-4 text-base">&rarr;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;larr;</td>
                        <td class="py-2 px-4 text-base">&larr;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;harr;</td>
                        <td class="py-2 pl-4 text-base">&harr;</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-mono text-emerald-400">&amp;uarr;</td>
                        <td class="py-2 px-4 text-base">&uarr;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;darr;</td>
                        <td class="py-2 px-4 text-base">&darr;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;rArr;</td>
                        <td class="py-2 pl-4 text-base">&rArr;</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-mono text-emerald-400">&amp;ge;</td>
                        <td class="py-2 px-4 text-base">&ge;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;le;</td>
                        <td class="py-2 px-4 text-base">&le;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;ne;</td>
                        <td class="py-2 pl-4 text-base">&ne;</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-mono text-emerald-400">&amp;plusmn;</td>
                        <td class="py-2 px-4 text-base">&plusmn;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;bull;</td>
                        <td class="py-2 px-4 text-base">&bull;</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">&amp;infin;</td>
                        <td class="py-2 pl-4 text-base">&infin;</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Tutorial Section 4: Callout Quotes --}}
    <section id="callouts" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                4
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Callout Quotes &amp; Rule Notes
                </h2>
                <p class="text-xs text-slate-400">
                    Begin lines with a greater-than symbol (`>`) to emphasize critical puzzle mechanics or cautionary notes.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-4">Copy</button>
                </div>
                <pre id="snippet-4" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">> **Important Note:** Digits along an arrow may repeat if permitted by normal Sudoku rules (e.g. crossing across multiple boxes).

> **Negative Constraint:** All possible black and white Kropki dots are given.</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("> **Important Note:** Digits along an arrow may repeat if permitted by normal Sudoku rules (e.g. crossing across multiple boxes).\n\n> **Negative Constraint:** All possible black and white Kropki dots are given.") !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Tutorial Section 5: Coordinates & Code --}}
    <section id="coordinates" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                5
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Cell Coordinates, Notation &amp; Monospace
                </h2>
                <p class="text-xs text-slate-400">
                    Enclose grid coordinates or specific digits in backticks (<code>`R1C1`</code>) for monospaced highlighting.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-5">Copy</button>
                </div>
                <pre id="snippet-5" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">The bulb of the thermo begins at `r3c5` and extends through `r4c5` to `r5c5`.

Given digits are placed at:
* `r1c1 = 7`
* `r9c9 = 4`
* Digits in cells `r2c2` and `r8c8` must sum to `10`.</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("The bulb of the thermo begins at `r3c5` and extends through `r4c5` to `r5c5`.\n\nGiven digits are placed at:\n* `r1c1 = 7`\n* `r9c9 = 4`\n* Digits in cells `r2c2` and `r8c8` must sum to `10`.") !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Tutorial Section 6: Links & Tables --}}
    <section id="links-tables" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                6
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Links &amp; Structured Tables
                </h2>
                <p class="text-xs text-slate-400">
                    Add external solve links and structured comparison tables for complex variant mechanics.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-6">Copy</button>
                </div>
                <pre id="snippet-6" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">Play this puzzle online at [SudokuPad by Sven](https://sudokupad.app).

| Line Type | Required Difference | Can Digits Repeat? |
| :--- | :--- | :--- |
| **German Whispers** | Difference &ge; 5 | Yes, if in different boxes |
| **Dutch Whispers** | Difference &ge; 4 | Yes, if in different boxes |
| **Renban** | Consecutive digits | No (all digits unique) |</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("Play this puzzle online at [SudokuPad by Sven](https://sudokupad.app).\n\n| Line Type | Required Difference | Can Digits Repeat? |\n| :--- | :--- | :--- |\n| **German Whispers** | Difference &ge; 5 | Yes, if in different boxes |\n| **Dutch Whispers** | Difference &ge; 4 | Yes, if in different boxes |\n| **Renban** | Consecutive digits | No (all digits unique) |") !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Tutorial Section 7: Inline Math & LaTeX Formulas --}}
    <section id="math" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                7
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Inline Math &amp; LaTeX Formulas</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">KaTeX</span>
                </h2>
                <p class="text-xs text-slate-400">
                    Enclose expressions in single dollar signs (<code>$...$</code>) for inline formulas, or double dollar signs (<code>$$...$$</code>) for centered display equations.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Input --}}
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>WHAT YOU TYPE (MARKDOWN + LATEX)</span>
                    <button type="button" class="copy-snippet-btn hover:text-white transition-colors" data-clipboard-target="#snippet-7">Copy</button>
                </div>
                <pre id="snippet-7" class="p-4 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre leading-relaxed flex-1">* **Cell Arithmetic:** $r_1c_1 + r_2c_2 = 10$
* **Summation Formula:** $\sum_{i=1}^{n} c_i = 45$
* **Inequalities &amp; Differences:** $|a - b| \ge 5$ and $x \ne y$
* **Fractions &amp; Roots:** $\frac{A + B}{2} \le \sqrt{C}$
* **Modular Arithmetic:** $r_5c_5 \equiv 1 \pmod{3}$
* **Set Membership:** $d \in \{1, 2, 4, 8\}$

Centered block equation:
$$
\sum_{k=1}^{9} k = 45 \quad \text{and} \quad \prod_{j=1}^{3} x_j = 72
$$</pre>
            </div>

            {{-- Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>HOW IT RENDERS IN CTC</span>
                </div>
                <div class="p-4 ctc-markdown-content flex-1">
                    {!! Str::markdown("* **Cell Arithmetic:** \$r_1c_1 + r_2c_2 = 10\$\n* **Summation Formula:** \$\\sum_{i=1}^{n} c_i = 45\$\n* **Inequalities & Differences:** \$|a - b| \\ge 5\$ and \$x \\ne y\$\n* **Fractions & Roots:** \$\\frac{A + B}{2} \\le \\sqrt{C}\$\n* **Modular Arithmetic:** \$r_5c_5 \\equiv 1 \\pmod{3}\$\n* **Set Membership:** \$d \\in \\{1, 2, 4, 8\\}\$\n\nCentered block equation:\n\$\$\n\\sum_{k=1}^{9} k = 45 \\quad \\text{and} \\quad \\prod_{j=1}^{3} x_j = 72\n\$\$") !!}
                </div>
            </div>
        </div>

        {{-- LaTeX Syntax Quick Reference Table --}}
        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4 overflow-x-auto text-xs font-mono">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono mb-2">
                Common Math &amp; LaTeX Cheat Sheet
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="py-2 pr-4">Concept</th>
                        <th class="py-2 px-4">LaTeX Code</th>
                        <th class="py-2 pl-4">Description / Usage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Inline Math</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">$r_1c_1 = 5$</td>
                        <td class="py-2 pl-4 text-slate-400">Single dollar signs for flowing in-sentence math</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Display / Centered</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">$$\sum_{i=1}^n x_i = 45$$</td>
                        <td class="py-2 pl-4 text-slate-400">Double dollar signs for standout standalone equations</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Subscripts &amp; Superscripts</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">$r_1, c_2, x^2$</td>
                        <td class="py-2 pl-4 text-slate-400"><code>_</code> for subscript digits, <code>^</code> for exponents</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Sums &amp; Products</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">\sum_{i=1}^n, \prod_{j=1}^k</td>
                        <td class="py-2 pl-4 text-slate-400">Upper and lower limits for killer cage or arrow totals</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Inequalities &amp; Relations</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">\le, \ge, \ne, \equiv</td>
                        <td class="py-2 pl-4 text-slate-400">Less-or-equal, greater-or-equal, not-equal, congruence</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Fractions &amp; Roots</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">\frac{a}{b}, \sqrt{x}</td>
                        <td class="py-2 pl-4 text-slate-400">Ratios and radical roots (e.g. geometric means)</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-semibold text-white">Modular Arithmetic</td>
                        <td class="py-2 px-4 font-mono text-emerald-400">x \pmod{3}</td>
                        <td class="py-2 pl-4 text-slate-400">Modulo lines, parity equivalence, and remainder arithmetic</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Interactive Live Playground Section --}}
    <section id="playground" class="space-y-4 pt-6 border-t border-slate-800/80">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-400 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm">
                    8
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <span>Interactive Live Markdown Playground</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Live</span>
                    </h2>
                    <p class="text-xs text-slate-400">
                        Type or paste your Markdown text below to preview how it will appear on public CTC pages in real time.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="btn-playground-sample"
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors cursor-pointer"
                >
                    Reset Sample
                </button>
                <button
                    type="button"
                    id="btn-playground-copy"
                    class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-xs font-semibold text-emerald-300 transition-colors cursor-pointer"
                >
                    Copy Markdown
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Live Input Editor --}}
            <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950 border-b border-slate-800 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                    <span>EDIT MARKDOWN</span>
                    <span class="text-slate-500">Auto-updates right column</span>
                </div>
                <textarea
                    id="playground-input"
                    class="w-full h-80 p-4 bg-transparent text-xs font-mono text-slate-200 focus:outline-none resize-y leading-relaxed"
                    placeholder="Type your markdown here..."
                ></textarea>
            </div>

            {{-- Live Rendered Output --}}
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden flex flex-col">
                <div class="px-4 py-2.5 bg-slate-950 border-b border-slate-800 text-[11px] font-mono text-emerald-400 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>LIVE PREVIEW (.ctc-markdown-content)</span>
                    </span>
                    <span id="playground-char-count" class="text-[10px] text-slate-500 font-mono">0 chars</span>
                </div>
                <div id="playground-preview" class="p-4 ctc-markdown-content overflow-y-auto max-h-[30rem] flex-1">
                    {{-- Dynamically populated via JavaScript --}}
                </div>
            </div>
        </div>
    </section>

    {{-- Where to use this in CTC Admin --}}
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-white font-mono flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span>Where is Markdown Supported in CTC?</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>Constraints &amp; Rulesets</span>
                </div>
                <p class="text-slate-400">
                    Rules and definitions edited in Admin appear formatted on <a href="{{ route('constraints.index') }}" class="text-emerald-400 hover:underline">/constraints/{slug}</a>.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                    <span>Puzzle Setters</span>
                </div>
                <p class="text-slate-400">
                    Author biographies and notes appear formatted on <a href="{{ route('setters.index') }}" class="text-emerald-400 hover:underline">/setters/{slug}</a>.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Apps &amp; Games</span>
                </div>
                <p class="text-slate-400">
                    Feature lists and app descriptions appear formatted on <a href="{{ route('apps.index') }}" class="text-emerald-400 hover:underline">/apps</a>.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                <div class="font-bold text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                    <span>Categories</span>
                </div>
                <p class="text-slate-400">
                    Category descriptions appear across catalog searches and puzzle filters.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Interactive Client-Side Script for Copy Palette and Live Playground --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var defaultSample = "### Whispers, Arrow & Math Variant Rules\n\nNormal Sudoku rules apply. Additionally, the following variant mechanics are active:\n\n* **German Whispers Lines:** Adjacent digits along green lines must differ by at least 5: $|a - b| \\ge 5$.\n* **Arrow Sums:** Digits along an arrow sum to the number in the attached bulb:\n  * `r2c2` &rarr; `r3c2` &rarr; Bulb at `r4c2`\n  * Total sum formula: $\\sum_{i=1}^{n} c_i = B$ where $B \\ge 3$\n* **Parity & Modulo:** The center cell satisfies $r_5c_5 \\equiv 0 \\pmod{2}$.\n\n> **Important Note:** Digits along an arrow may repeat if permitted by normal Sudoku rules (e.g. crossing between boxes).\n\n| Line Color | Rule Type | Mathematical Formula |\n| :--- | :--- | :--- |\n| Green | German Whispers | $|x - y| \\ge 5$ |\n| Purple | Renban | Consecutive: $\\max - \\min = n - 1$ |\n| Peach | Entropic | Set: $\\{1, 2, 3\\} \\pmod{3}$ |\n\nCentered block equation:\n$$\n\\sum_{k=1}^{9} k = 45 \\quad \\text{and} \\quad \\prod_{j=1}^{3} x_j = 72\n$$\n\nPlay online with [SudokuPad](https://sudokupad.app).";

    // 1. Quick Symbol Copy Buttons
    var feedbackLabel = document.getElementById('copy-feedback-label');
    var symbolButtons = document.querySelectorAll('.quick-symbol-btn');

    symbolButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var symbol = btn.dataset.symbol;
            if (!symbol) return;

            navigator.clipboard.writeText(symbol).then(function () {
                var prevText = feedbackLabel.textContent;
                feedbackLabel.textContent = 'Copied "' + symbol + '" to clipboard!';
                feedbackLabel.classList.add('text-emerald-400');
                feedbackLabel.classList.remove('text-slate-400');

                btn.classList.add('ring-2', 'ring-emerald-400');
                setTimeout(function () {
                    btn.classList.remove('ring-2', 'ring-emerald-400');
                    feedbackLabel.textContent = prevText;
                    feedbackLabel.classList.remove('text-emerald-400');
                    feedbackLabel.classList.add('text-slate-400');
                }, 2000);
            });
        });
    });

    // 2. Snippet Copy Buttons
    document.querySelectorAll('.copy-snippet-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetSelector = btn.getAttribute('data-clipboard-target');
            var targetEl = document.querySelector(targetSelector);
            if (!targetEl) return;

            navigator.clipboard.writeText(targetEl.textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('text-emerald-400');
                setTimeout(function () {
                    btn.textContent = orig;
                    btn.classList.remove('text-emerald-400');
                }, 2000);
            });
        });
    });

    // 3. Client-side Markdown Parser for the Live Playground
    function parseClientMarkdown(src) {
        if (!src) return '<p class="text-slate-500 italic">Start typing in the left box to see your rendered output...</p>';

        // Protect LaTeX math formulas ($...$ and $$...$$) from markdown syntax replacements
        var mathBlocks = [];
        var text = src.replace(/(\$\$[\s\S]+?\$\$|\$[^\$\n]+?\$)/g, function (match) {
            mathBlocks.push(match);
            return '___CTC_MATH_' + (mathBlocks.length - 1) + '___';
        });

        // Escape raw HTML tags (except safe entities &amp;, &rarr;, etc.)
        text = text.replace(/&(?!([a-zA-Z0-9]+|#[0-9]{1,6});)/g, '&amp;');

        // Tables
        text = text.replace(/((?:\|[^\n]+\|\r?\n)+)/g, function (match) {
            var lines = match.trim().split(/\r?\n/).filter(function (l) { return l.trim().length > 0; });
            if (lines.length < 2) return match;

            var html = '<table>';
            var isHeader = true;

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();
                // Check if delimiter row
                if (/^\|[\s\-:]+(\|[\s\-:]+)+\|?$/.test(line)) {
                    isHeader = false;
                    continue;
                }

                var cells = line.split('|').slice(1, -1);
                if (cells.length === 0) continue;

                html += '<tr>';
                for (var c = 0; c < cells.length; c++) {
                    var cellContent = cells[c].trim();
                    var tag = isHeader ? 'th' : 'td';
                    html += '<' + tag + '>' + cellContent + '</' + tag + '>';
                }
                html += '</tr>';
                if (isHeader && i === 0 && lines.length > 1 && /^\|[\s\-:]+/.test(lines[1])) {
                    // Handled delimiter
                }
            }
            html += '</table>';
            return html;
        });

        // Blockquotes
        text = text.replace(/^>[ \t]?(.*)$/gm, '<blockquote>$1</blockquote>');
        text = text.replace(/<\/blockquote>\n<blockquote>/g, '\n');

        // Headers
        text = text.replace(/^#### (.*$)/gm, '<h4>$1</h4>');
        text = text.replace(/^### (.*$)/gm, '<h3>$1</h3>');
        text = text.replace(/^## (.*$)/gm, '<h2>$1</h2>');
        text = text.replace(/^# (.*$)/gm, '<h1>$1</h1>');

        // Bold & Italic
        text = text.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        text = text.replace(/~~(.*?)~~/g, '<del>$1</del>');

        // Inline code
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');

        // Unordered Lists
        text = text.replace(/^[\*\-][ \t]+(.*)$/gm, '<li>$1</li>');
        text = text.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');

        // Paragraphs
        var rawParagraphs = text.split(/\n{2,}/);
        var formatted = rawParagraphs.map(function (block) {
            var trimmed = block.trim();
            if (!trimmed) return '';
            if (/^<(h[1-6]|ul|ol|table|blockquote)/.test(trimmed)) {
                return trimmed;
            }
            return '<p>' + trimmed.replace(/\n/g, '<br>') + '</p>';
        }).join('\n');

        // Restore protected math blocks
        formatted = formatted.replace(/___CTC_MATH_(\d+)___/g, function (match, index) {
            return mathBlocks[parseInt(index, 10)] || '';
        });

        return formatted;
    }

    // 4. Live Playground Setup
    var inputEl = document.getElementById('playground-input');
    var previewEl = document.getElementById('playground-preview');
    var charCountEl = document.getElementById('playground-char-count');
    var sampleBtn = document.getElementById('btn-playground-sample');
    var copyBtn = document.getElementById('btn-playground-copy');

    if (inputEl && previewEl) {
        function updatePlayground() {
            var val = inputEl.value;
            previewEl.innerHTML = parseClientMarkdown(val);
            if (window.renderMath) {
                window.renderMath(previewEl);
            }
            if (charCountEl) {
                charCountEl.textContent = val.length.toLocaleString() + ' chars';
            }
        }

        inputEl.value = defaultSample;
        updatePlayground();

        // Retry KaTeX render briefly after load in case Vite app.js finished just after DOMContentLoaded
        if (!window.renderMath) {
            setTimeout(function () {
                if (window.renderMath) {
                    window.renderMath(previewEl);
                }
            }, 150);
        }

        inputEl.addEventListener('input', updatePlayground);

        if (sampleBtn) {
            sampleBtn.addEventListener('click', function () {
                inputEl.value = defaultSample;
                updatePlayground();
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                navigator.clipboard.writeText(inputEl.value).then(function () {
                    var orig = copyBtn.textContent;
                    copyBtn.textContent = 'Copied!';
                    setTimeout(function () {
                        copyBtn.textContent = orig;
                    }, 2000);
                });
            });
        }
    }
});
</script>
@endsection
