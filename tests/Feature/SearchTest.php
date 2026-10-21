<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for catalog cross-entity search across videos, puzzles, setters, categories, constraints, and hosts.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the search page loads successfully without a query and displays discovery guidance.
     */
    public function test_search_page_loads_with_empty_query(): void
    {
        $response = $this->get(route('search'));

        $response->assertStatus(200);
        $response->assertSee('Search the');
        $response->assertSee('Catalog');
        $response->assertSee('What can you search for?');
        $response->assertSee('Videos');
        $response->assertSee('Setters');
        $response->assertSee('Puzzle Types');
        $response->assertSee('Variants &amp; Rules', false);
        $response->assertSee('Hosts');
        $response->assertSee('Simon');
        $response->assertSee('Mark');
    }

    /**
     * Test searching for a video by title.
     */
    public function test_search_finds_video_by_title(): void
    {
        $video = Video::factory()->create([
            'title' => 'Astonishing Miracle Sudoku Solve',
            'slug' => 'astonishing-miracle-sudoku-solve',
        ]);

        $otherVideo = Video::factory()->create([
            'title' => 'Standard Daily Crossword',
            'slug' => 'standard-daily-crossword',
        ]);

        $response = $this->get(route('search', ['q' => 'Miracle Sudoku']));

        $response->assertStatus(200);
        $response->assertSee('Astonishing Miracle Sudoku Solve');
        $response->assertSee(route('videos.show', $video->slug));
        $response->assertDontSee('Standard Daily Crossword');
    }

    /**
     * Test searching for a puzzle by name and rules.
     */
    public function test_search_finds_puzzle_by_title_and_rules(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku']);

        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'The Phistomefel Ring',
            'rules_raw' => 'Digits along the marked ring cannot contain duplicates and must obey chess knight constraints.',
        ]);

        // Search by puzzle title
        $titleResponse = $this->get(route('search', ['q' => 'Phistomefel Ring']));
        $titleResponse->assertStatus(200);
        $titleResponse->assertSee('The Phistomefel Ring');

        // Search by rules content
        $rulesResponse = $this->get(route('search', ['q' => 'chess knight']));
        $rulesResponse->assertStatus(200);
        $rulesResponse->assertSee('The Phistomefel Ring');
    }

    /**
     * Test searching for a puzzle setter by name.
     */
    public function test_search_finds_setter_and_author_puzzles(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'BremSter',
            'bio' => 'Renowned constructor of geometric and constraint sudoku puzzles.',
        ]);

        $category = Category::factory()->create(['name' => 'Classic Sudoku']);

        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'BremSter Classic Masterpiece',
        ]);
        $puzzle->setters()->attach($setter->id);

        $response = $this->get(route('search', ['q' => 'BremSter']));

        $response->assertStatus(200);
        $response->assertSee('BremSter');
        $response->assertSee('Setters (Puzzle Authors)');
        $response->assertSee('BremSter Classic Masterpiece');
    }

    /**
     * Test searching for a puzzle type (category).
     */
    public function test_search_finds_puzzle_type_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Thermo Sudoku',
            'description' => 'Puzzles where numbers along a thermometer line must strictly increase from the bulb.',
        ]);

        $response = $this->get(route('search', ['q' => 'Thermo Sudoku']));

        $response->assertStatus(200);
        $response->assertSee('Thermo Sudoku');
        $response->assertSee('Puzzle Types (Categories)');
        $response->assertSee('strictly increase from the bulb');
    }

    /**
     * Test searching for a variant constraint.
     */
    public function test_search_finds_variant_constraint(): void
    {
        $constraint = Constraint::factory()->create([
            'name' => 'German Whispers',
            'description' => 'Adjacent cells on green lines must have a difference of at least 5.',
        ]);

        $response = $this->get(route('search', ['q' => 'German Whispers']));

        $response->assertStatus(200);
        $response->assertSee('German Whispers');
        $response->assertSee('Variants &amp; Constraints', false);
        $response->assertSee('difference of at least 5');
    }

    /**
     * Test searching for a video host and their hosted videos.
     */
    public function test_search_finds_host_and_their_videos(): void
    {
        $host = Host::factory()->create(['name' => 'Simon Anthony']);

        $video = Video::factory()->create([
            'title' => 'Simon Tackles An Impossible Grid',
            'slug' => 'simon-tackles-an-impossible-grid',
        ]);
        $video->hosts()->attach($host->id);

        $response = $this->get(route('search', ['q' => 'Simon Anthony']));

        $response->assertStatus(200);
        $response->assertSee('Simon Anthony');
        $response->assertSee('Hosts');
        $response->assertSee('Simon Tackles An Impossible Grid');
    }

    /**
     * Test filtering search results by entity type tabs.
     */
    public function test_search_filtering_by_type_tabs(): void
    {
        $video = Video::factory()->create([
            'title' => 'Shared Keyword Exploration',
            'slug' => 'shared-keyword-exploration',
        ]);

        $setter = Setter::factory()->create([
            'name' => 'Shared Keyword Creator',
        ]);

        // Filter to videos only
        $videoTabResponse = $this->get(route('search', ['q' => 'Shared Keyword', 'type' => 'videos']));
        $videoTabResponse->assertStatus(200);
        $videoTabResponse->assertSee('Shared Keyword Exploration');
        $videoTabResponse->assertDontSee('Setters (Puzzle Authors)');

        // Filter to setters only
        $setterTabResponse = $this->get(route('search', ['q' => 'Shared Keyword', 'type' => 'setters']));
        $setterTabResponse->assertStatus(200);
        $setterTabResponse->assertSee('Shared Keyword Creator');
        $setterTabResponse->assertDontSee('Shared Keyword Exploration');
    }

    /**
     * Test that no results state displays a friendly empty notice.
     */
    public function test_search_zero_results_state(): void
    {
        $response = $this->get(route('search', ['q' => 'xyzunmatchedgibberish98765']));

        $response->assertStatus(200);
        $response->assertSee('No results found');
        $response->assertSee('Browse all videos instead');
    }

    /**
     * Test that the navigation header and footer contain the search link and that no input bar is in the header.
     */
    public function test_header_and_footer_contain_search(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('search'));
        $response->assertSee('Search</span>', false);
        $response->assertDontSee('id="ctc-global-header-search"', false);
    }

    /**
     * Test that searches returning more than 30 items are paginated with page controls.
     */
    public function test_search_results_with_more_than_thirty_items_are_paginated(): void
    {
        // Create 35 videos matching "Wordle"
        for ($i = 1; $i <= 35; $i++) {
            Video::factory()->create([
                'title' => sprintf('Wordle Episode #%02d Solve', $i),
                'slug' => sprintf('wordle-episode-%02d-solve', $i),
                'published_at' => now()->subDays(40 - $i),
            ]);
        }

        // 1. First page should show 30 items and pagination info
        $page1Response = $this->get(route('search', ['q' => 'Wordle']));
        $page1Response->assertStatus(200);
        $page1Response->assertSee('Results for &ldquo;<span class="text-emerald-400">Wordle</span>&rdquo;', false);
        $page1Response->assertSee('(35 total found)');
        $page1Response->assertSee('Showing');
        $page1Response->assertSee('1');
        $page1Response->assertSee('30');
        $page1Response->assertSee('35');
        $page1Response->assertSee('page=2');

        // Newest episodes on page 1
        $page1Response->assertSee('Wordle Episode #35 Solve');
        $page1Response->assertSee('Wordle Episode #06 Solve');
        // Older episodes should NOT appear on page 1
        $page1Response->assertDontSee('Wordle Episode #05 Solve');
        $page1Response->assertDontSee('Wordle Episode #01 Solve');

        // 2. Second page should show remaining 5 items and previous controls
        $page2Response = $this->get(route('search', ['q' => 'Wordle', 'page' => 2]));
        $page2Response->assertStatus(200);
        $page2Response->assertSee('Wordle Episode #05 Solve');
        $page2Response->assertSee('Wordle Episode #01 Solve');
        $page2Response->assertDontSee('Wordle Episode #35 Solve');
        $page2Response->assertSee('page=1');

        // 3. Paginated within the Videos tab as well
        $tabResponse = $this->get(route('search', ['q' => 'Wordle', 'type' => 'videos', 'page' => 2]));
        $tabResponse->assertStatus(200);
        $tabResponse->assertSee('Wordle Episode #05 Solve');
        $tabResponse->assertDontSee('Wordle Episode #35 Solve');
    }

    /**
     * Test searching for common timestamp bookmarks across videos.
     */
    public function test_search_finds_bookmarks_by_label_and_links_to_timestamps(): void
    {
        $video1 = Video::factory()->create([
            'title' => 'Miracle Sudoku Solve Episode',
            'slug' => 'miracle-sudoku-solve-episode',
            'published_at' => now()->subDays(5),
        ]);

        $video2 = Video::factory()->create([
            'title' => 'Incredible German Whispers Episode',
            'slug' => 'incredible-german-whispers-episode',
            'published_at' => now()->subDays(2),
        ]);

        $bookmark1 = Bookmark::create([
            'video_id' => $video1->id,
            'timestamp_seconds' => 862,
            'label' => 'Simon rage over digit 2',
        ]);

        $bookmark2 = Bookmark::create([
            'video_id' => $video2->id,
            'timestamp_seconds' => 1695,
            'label' => 'Simon rage over column 9',
        ]);

        Bookmark::create([
            'video_id' => $video1->id,
            'timestamp_seconds' => 60,
            'label' => 'Unrelated non-matching moment',
        ]);

        $response = $this->get(route('search', ['q' => 'Simon rage']));

        $response->assertStatus(200);
        $response->assertSee('Video Bookmarks &amp; Timestamps', false);
        $response->assertSee('(2)');
        $response->assertSee('Simon rage over digit 2');
        $response->assertSee('Simon rage over column 9');
        $response->assertSee('14:22');
        $response->assertSee('28:15');
        $response->assertSee(route('videos.show', $video1->slug).'#t=862');
        $response->assertSee(route('videos.show', $video2->slug).'#t=1695');
        $response->assertDontSee('Unrelated non-matching moment');
    }

    /**
     * Test filtering search results by the dedicated bookmarks tab.
     */
    public function test_search_filter_by_bookmarks_tab(): void
    {
        $video = Video::factory()->create([
            'title' => 'Daily Video With Recurring Moments',
            'slug' => 'daily-video-with-recurring-moments',
        ]);

        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 312,
            'label' => 'Coffee spill on keyboard',
        ]);

        $response = $this->get(route('search', ['q' => 'Coffee spill', 'type' => 'bookmarks']));

        $response->assertStatus(200);
        $response->assertSee('Coffee spill on keyboard');
        $response->assertSee('5:12');
        $response->assertSee(route('videos.show', $video->slug).'#t=312');
    }

    /**
     * Test video cards display matching bookmark highlights when a video matches by bookmark.
     */
    public function test_video_card_shows_matching_bookmark_highlight(): void
    {
        $video = Video::factory()->create([
            'title' => 'Classic Sudoku Live Session',
            'slug' => 'classic-sudoku-live-session',
        ]);

        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 745,
            'label' => 'Brilliant break-in found',
        ]);

        $response = $this->get(route('search', ['q' => 'break-in']));

        $response->assertStatus(200);
        $response->assertSee('Classic Sudoku Live Session');
        $response->assertSee('Matching Bookmarks:');
        $response->assertSee('12:25');
        $response->assertSee('Brilliant break-in found');
    }
}
