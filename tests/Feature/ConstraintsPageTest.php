<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Constraint;
use App\Models\Puzzle;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstraintsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that /constraints renders the directory table with expected columns and data.
     */
    public function test_constraints_index_displays_directory_table_and_columns(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku', 'slug' => 'variant-sudoku']);

        $constraint = Constraint::factory()->create([
            'name' => 'German Whispers',
            'slug' => 'german-whispers',
            'description' => 'Adjacent digits along a green line must have a difference of at least 5.',
        ]);

        $puzzle1 = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Whisper Puzzle 1']);
        $puzzle2 = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Whisper Puzzle 2']);

        $puzzle1->constraints()->attach($constraint->id);
        $puzzle2->constraints()->attach($constraint->id);

        $video1 = Video::factory()->create([
            'title' => 'First Whisper Video',
            'slug' => 'first-whisper-video',
            'published_at' => Carbon::parse('2023-01-15 12:00:00'),
        ]);
        $video1->puzzles()->attach($puzzle1->id, ['order_in_video' => 1]);

        $video2 = Video::factory()->create([
            'title' => 'Latest Whisper Video',
            'slug' => 'latest-whisper-video',
            'published_at' => Carbon::parse('2024-05-20 18:00:00'),
        ]);
        $video2->puzzles()->attach($puzzle2->id, ['order_in_video' => 1]);

        $response = $this->get(route('constraints.index'));
        $response->assertStatus(200);

        // Header and metrics
        $response->assertSee('Puzzle Constraints & Rulesets', false);
        $response->assertSee('Distinct Constraint');

        // Table headers and content
        $response->assertSee('German Whispers');
        $response->assertSee('Adjacent digits along a green line must have a difference of at least 5.');
        $response->assertSee(route('constraints.show', 'german-whispers'));
        $response->assertSee('First Whisper Video');
        $response->assertSee('Latest Whisper Video');
    }

    /**
     * Test that /constraints/{slug} displays the constraint name, definition, stat tiles, and video listing.
     */
    public function test_constraints_show_displays_definition_stat_tiles_and_video_listing(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku', 'slug' => 'variant-sudoku']);

        $constraint = Constraint::factory()->create([
            'name' => 'Thermo Sudoku',
            'slug' => 'thermo-sudoku',
            'description' => 'Numbers along a thermometer must strictly increase from the bulb to the flat end.',
        ]);

        $puzzle1 = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Thermo Solve 1']);
        $puzzle2 = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Thermo Solve 2']);

        $puzzle1->constraints()->attach($constraint->id);
        $puzzle2->constraints()->attach($constraint->id);

        $firstVideo = Video::factory()->create([
            'title' => 'First Thermometer Masterpiece',
            'slug' => 'first-thermometer-masterpiece',
            'duration_seconds' => 1800,
            'view_count' => 75000,
            'published_at' => Carbon::parse('2021-03-10 10:00:00'),
        ]);
        $firstVideo->puzzles()->attach($puzzle1->id, ['order_in_video' => 1]);

        $recentVideo = Video::factory()->create([
            'title' => 'Recent Thermometer Challenge',
            'slug' => 'recent-thermometer-challenge',
            'duration_seconds' => 2400,
            'view_count' => 45000,
            'published_at' => Carbon::parse('2024-08-15 14:00:00'),
        ]);
        $recentVideo->puzzles()->attach($puzzle2->id, ['order_in_video' => 1]);

        $response = $this->get(route('constraints.show', $constraint->slug));
        $response->assertStatus(200);

        // Name and definition
        $response->assertSee('Thermo Sudoku');
        $response->assertSee('Numbers along a thermometer must strictly increase from the bulb to the flat end.');

        // Stat tiles
        $response->assertSee('Videos with Constraint');
        $response->assertSee('2'); // 2 videos
        $response->assertSee('Featured across 2 distinct puzzle solves');
        $response->assertSee('First on Channel');
        $response->assertSee('First Thermometer Masterpiece');
        $response->assertSee('Most Recent Video');
        $response->assertSee('Recent Thermometer Challenge');
        $response->assertSee('Total Views');
        $response->assertSee(number_format(120000)); // 75,000 + 45,000

        // Full video listing
        $response->assertSee('Videos Featuring Thermo Sudoku');
        $response->assertSee(route('videos.show', 'first-thermometer-masterpiece'));
        $response->assertSee(route('videos.show', 'recent-thermometer-challenge'));
    }

    /**
     * Test that /constraints/{slug} handles empty state cleanly when no videos are linked.
     */
    public function test_constraints_show_handles_empty_video_state_cleanly(): void
    {
        $constraint = Constraint::factory()->create([
            'name' => 'Kropki Dots',
            'slug' => 'kropki-dots',
            'description' => 'White dots separate consecutive digits; black dots separate digits in a 1:2 ratio.',
        ]);

        $response = $this->get(route('constraints.show', $constraint->slug));
        $response->assertStatus(200);

        $response->assertSee('Kropki Dots');
        $response->assertSee('No videos cataloged for this constraint yet.');
        $response->assertSee('Back to all constraints');
    }

    /**
     * Test that /contraints and /contraints/{slug} typo URLs redirect to /constraints with 301.
     */
    public function test_contraints_typo_urls_redirect_to_constraints(): void
    {
        $constraint = Constraint::factory()->create([
            'name' => 'Arrow Sudoku',
            'slug' => 'arrow-sudoku',
        ]);

        // Index redirect
        $indexResponse = $this->get('/contraints');
        $indexResponse->assertRedirect(route('constraints.index'));
        $indexResponse->assertStatus(301);

        // Show redirect
        $showResponse = $this->get('/contraints/arrow-sudoku');
        $showResponse->assertRedirect(route('constraints.show', 'arrow-sudoku'));
        $showResponse->assertStatus(301);
    }

    /**
     * Test that navigation header and footer contain constraints links.
     */
    public function test_constraints_links_in_header_and_footer(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $response->assertSee(route('constraints.index'));
        $response->assertSee('Constraints');
    }

    /**
     * Test that video show page links constraint badges to the constraint detail page.
     */
    public function test_video_show_page_links_to_constraint_page(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku']);
        $constraint = Constraint::factory()->create(['name' => 'Palindromes', 'slug' => 'palindromes']);

        $puzzle = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Palindrome Puzzle']);
        $puzzle->constraints()->attach($constraint->id);

        $video = Video::factory()->create([
            'title' => 'Palindrome Video Solve',
            'slug' => 'palindrome-video-solve',
            'published_at' => now(),
        ]);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $response = $this->get(route('videos.show', $video->slug));
        $response->assertStatus(200);

        $response->assertSee(route('constraints.show', 'palindromes'));
        $response->assertSee('Palindromes');
    }

    /**
     * Test that puzzle constraints are always listed in alphabetical order.
     */
    public function test_puzzle_constraints_are_listed_in_alphabetical_order(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku']);

        // Create constraints in non-alphabetical order
        $thermo = Constraint::factory()->create(['name' => 'Thermo', 'slug' => 'thermo']);
        $arrow = Constraint::factory()->create(['name' => 'Arrow', 'slug' => 'arrow']);
        $kropki = Constraint::factory()->create(['name' => 'Kropki', 'slug' => 'kropki']);
        $whispers = Constraint::factory()->create(['name' => 'German Whispers', 'slug' => 'german-whispers']);

        $puzzle = Puzzle::factory()->create(['category_id' => $category->id, 'title' => 'Multi Constraint Puzzle']);
        // Attach in random order
        $puzzle->constraints()->attach([$thermo->id, $arrow->id, $kropki->id, $whispers->id]);

        $video = Video::factory()->create([
            'title' => 'Quad Variant Solve',
            'slug' => 'quad-variant-solve',
            'published_at' => now(),
        ]);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        // 1. Check video show page
        $videoResponse = $this->get(route('videos.show', $video->slug));
        $videoResponse->assertStatus(200);

        $videoHtml = $videoResponse->getContent();
        $posArrow = strpos($videoHtml, 'Arrow');
        $posWhispers = strpos($videoHtml, 'German Whispers');
        $posKropki = strpos($videoHtml, 'Kropki');
        $posThermo = strpos($videoHtml, 'Thermo');

        $this->assertNotFalse($posArrow);
        $this->assertNotFalse($posWhispers);
        $this->assertNotFalse($posKropki);
        $this->assertNotFalse($posThermo);

        // Assert strictly ascending alphabetical order
        $this->assertTrue($posArrow < $posWhispers, 'Arrow must appear before German Whispers');
        $this->assertTrue($posWhispers < $posKropki, 'German Whispers must appear before Kropki');
        $this->assertTrue($posKropki < $posThermo, 'Kropki must appear before Thermo');

        // 2. Check constraint show page (e.g. Thermo page where other constraints are listed under video card)
        $constraintResponse = $this->get(route('constraints.show', $thermo->slug));
        $constraintResponse->assertStatus(200);

        $constraintHtml = $constraintResponse->getContent();
        // Check "Also features:" section on the video card
        $cPosArrow = strpos($constraintHtml, 'Arrow');
        $cPosWhispers = strpos($constraintHtml, 'German Whispers');
        $cPosKropki = strpos($constraintHtml, 'Kropki');

        $this->assertNotFalse($cPosArrow);
        $this->assertNotFalse($cPosWhispers);
        $this->assertNotFalse($cPosKropki);

        $this->assertTrue($cPosArrow < $cPosWhispers, 'Arrow must appear before German Whispers on constraint video card');
        $this->assertTrue($cPosWhispers < $cPosKropki, 'German Whispers must appear before Kropki on constraint video card');
    }

    /**
     * Test that constraint definition with markdown renders formatted HTML including lists and bold text.
     */
    public function test_constraint_detail_renders_formatted_markdown_description(): void
    {
        $markdown = "Rules of this variant:\n\n* **Adjacent digits** must differ by at least 5.\n* Lines do not loop.\n\nEnjoy the puzzle!";

        $constraint = Constraint::factory()->create([
            'name' => 'Whispers Ruleset',
            'slug' => 'whispers-ruleset',
            'description' => $markdown,
        ]);

        $response = $this->get(route('constraints.show', $constraint->slug));
        $response->assertStatus(200);

        // Assert markdown content container and converted HTML elements
        $response->assertSee('ctc-markdown-content', false);
        $response->assertSee('<strong>Adjacent digits</strong>', false);
        $response->assertSee('<ul>', false);
        $response->assertSee('<li>', false);
        $response->assertSee('Lines do not loop.');
    }

    /**
     * Test that the constraints index table renders rich markdown and math preview.
     */
    public function test_constraints_index_renders_live_markdown_and_math_preview(): void
    {
        $markdown = "Normal Sudoku rules apply. Adjacent digits along green lines differ by at least 5 ($|a - b| \ge 5$).";

        $constraint = Constraint::factory()->create([
            'name' => 'Whispers Math Variant',
            'slug' => 'whispers-math-variant',
            'description' => $markdown,
        ]);

        $response = $this->get(route('constraints.index'));
        $response->assertStatus(200);

        $response->assertSee('ctc-markdown-content ctc-markdown-preview', false);
        $response->assertSee('Whispers Math Variant');
        $response->assertSee('$|a - b| \ge 5$');
    }

    /**
     * Test that the constraints index table paginates at 20 items per page with working page links.
     */
    public function test_constraints_index_paginates_at_twenty_items_per_page(): void
    {
        // Create 25 constraints with distinct names
        for ($i = 1; $i <= 25; $i++) {
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Constraint::factory()->create([
                'name' => "Constraint {$num}",
                'slug' => "constraint-{$num}",
                'description' => "Description for constraint {$num}",
            ]);
        }

        // Request page 1 with sort by name ascending
        $responsePage1 = $this->get(route('constraints.index', ['sort' => 'name', 'dir' => 'asc']));
        $responsePage1->assertStatus(200);

        // Header shows total count 25
        $responsePage1->assertSee('25');
        $responsePage1->assertSee('Showing 1–20 of 25');

        // Page 1 should contain items 01 through 20
        $responsePage1->assertSee('Constraint 01');
        $responsePage1->assertSee('Constraint 20');
        // Page 1 should NOT contain item 21
        $responsePage1->assertDontSee('Constraint 21');

        // Verify pagination link to page 2 is present
        $responsePage1->assertSee('page=2');

        // Request page 2
        $responsePage2 = $this->get(route('constraints.index', ['sort' => 'name', 'dir' => 'asc', 'page' => 2]));
        $responsePage2->assertStatus(200);

        // Page 2 shows range 21–25 of 25
        $responsePage2->assertSee('Showing 21–25 of 25');
        // Page 2 should contain items 21 through 25
        $responsePage2->assertSee('Constraint 21');
        $responsePage2->assertSee('Constraint 25');
        // Page 2 should NOT contain items from page 1
        $responsePage2->assertDontSee('Constraint 01');
        $responsePage2->assertDontSee('Constraint 20');
    }

    /**
     * Test that searching on /constraints filters across all pagination groupings and shows all matching constraints,
     * even if the user was on page 2 when filtering down to fewer than 20 results.
     */
    public function test_constraints_search_filters_across_all_pages_and_resets_out_of_bounds_page(): void
    {
        // Create 25 regular constraints
        for ($i = 1; $i <= 25; $i++) {
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Constraint::factory()->create([
                'name' => "Standard Rule {$num}",
                'slug' => "standard-rule-{$num}",
                'description' => "Generic ruleset number {$num}",
            ]);
        }

        // Create 3 matching constraints that would sort across different pages alphabetically
        Constraint::factory()->create([
            'name' => 'Alpha Whisper Line',
            'slug' => 'alpha-whisper-line',
            'description' => 'First whisper variant.',
        ]);
        Constraint::factory()->create([
            'name' => 'Middle Whisper Loop',
            'slug' => 'middle-whisper-loop',
            'description' => 'Second whisper variant.',
        ]);
        Constraint::factory()->create([
            'name' => 'Zeta Whisper Cage',
            'slug' => 'zeta-whisper-cage',
            'description' => 'Third whisper variant.',
        ]);

        // Searching for "Whisper" (including from page 2) must return all 3 matching constraints on a single view
        $response = $this->get(route('constraints.index', [
            'sort' => 'name',
            'dir' => 'asc',
            'q' => 'Whisper',
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Showing 1–3 of 3');
        $response->assertSee('Alpha Whisper Line');
        $response->assertSee('Middle Whisper Loop');
        $response->assertSee('Zeta Whisper Cage');
        $response->assertDontSee('Standard Rule 01');
        $response->assertSee('performLiveSearch', false);
    }
}
