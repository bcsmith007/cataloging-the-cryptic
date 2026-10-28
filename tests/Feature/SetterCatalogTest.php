<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetterCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the homepage tiles for total videos and distinct setters link to their respective pages.
     */
    public function test_homepage_stats_tiles_link_to_videos_and_setters(): void
    {
        $video = Video::factory()->create([
            'title' => 'Featured Channel Video',
            'published_at' => now(),
        ]);

        $setter = Setter::factory()->create([
            'name' => 'Test Setter',
            'slug' => 'test-setter',
        ]);

        $category = Category::factory()->create();
        $puzzle = Puzzle::factory()->create(['category_id' => $category->id]);
        $puzzle->setters()->attach($setter->id);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Total videos tile links to /videos
        $response->assertSee(route('videos.index'));
        // Distinct setters tile links to /setters
        $response->assertSee(route('setters.index'));
    }

    /**
     * Test that /setters renders the sortable table with all requested columns and links.
     */
    public function test_setters_index_displays_table_with_required_columns_and_links(): void
    {
        $category = Category::factory()->create(['name' => 'Classic Sudoku']);

        // Setter 1: Clover with 2 puzzles across 2 videos
        $setterClover = Setter::factory()->create([
            'name' => 'Clover',
            'slug' => 'clover',
            'bio' => 'Prolific puzzle setter specializing in beautiful sudoku variants.',
        ]);

        $puzzleFirst = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Clover First Puzzle',
            'slug' => 'clover-first-puzzle',
        ]);
        $puzzleRecent = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Clover Recent Puzzle',
            'slug' => 'clover-recent-puzzle',
        ]);
        $puzzleFirst->setters()->attach($setterClover->id);
        $puzzleRecent->setters()->attach($setterClover->id);

        $videoEarlier = Video::factory()->create([
            'title' => 'Earlier Solve Video',
            'slug' => 'earlier-solve-video',
            'published_at' => now()->subMonths(6),
        ]);
        $videoEarlier->puzzles()->attach($puzzleFirst->id, ['order_in_video' => 1]);

        $videoLater = Video::factory()->create([
            'title' => 'Later Solve Video',
            'slug' => 'later-solve-video',
            'published_at' => now()->subDay(),
        ]);
        $videoLater->puzzles()->attach($puzzleRecent->id, ['order_in_video' => 1]);

        // Setter 2: Phistomefel with 0 puzzles
        $setterPhisto = Setter::factory()->create([
            'name' => 'Phistomefel',
            'slug' => 'phistomefel',
        ]);

        $response = $this->get('/setters');
        $response->assertStatus(200);

        // Check setter names present
        $response->assertSee('Clover');
        $response->assertSee('Phistomefel');

        // Check setter name links to /setters/setter-slug
        $response->assertSee(route('setters.show', 'clover'));
        $response->assertSee(route('setters.show', 'phistomefel'));

        // Check puzzle counts
        $response->assertSee('2');

        // Check first puzzle and recent puzzle titles
        $response->assertSee('Clover First Puzzle');
        $response->assertSee('Clover Recent Puzzle');

        // Check first puzzle links to earlier video and most recent puzzle links to later video
        $response->assertSee(route('videos.show', 'earlier-solve-video'));
        $response->assertSee(route('videos.show', 'later-solve-video'));
    }

    /**
     * Test that if a setter only has one video so far, it is only listed under first on channel,
     * leaving most recent on channel blank.
     */
    public function test_setter_with_only_one_video_leaves_most_recent_column_blank(): void
    {
        $category = Category::factory()->create();

        $setterSolo = Setter::factory()->create([
            'name' => 'Solo Video Setter',
            'slug' => 'solo-video-setter',
        ]);

        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Solo Puzzle Title',
            'slug' => 'solo-puzzle-title',
        ]);
        $puzzle->setters()->attach($setterSolo->id);

        $singleVideo = Video::factory()->create([
            'title' => 'The Only Solve Video',
            'slug' => 'the-only-solve-video',
            'published_at' => now()->subMonth(),
        ]);
        $singleVideo->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $response = $this->get('/setters');
        $response->assertStatus(200);

        // Verify that the video is listed under first on channel
        $response->assertSee('Solo Video Setter');
        $response->assertSee('Solo Puzzle Title');
        $response->assertSee(route('videos.show', 'the-only-solve-video'));

        // Verify the HTML table row: data-recent is 0 and the recent cell does not contain a link to the video
        $content = $response->getContent();
        // The link to the video should appear only ONCE in the row for Solo Video Setter
        $setterRowStart = strpos($content, 'data-name="solo video setter"');
        $this->assertNotFalse($setterRowStart);
        $setterRowEnd = strpos($content, '</tr>', $setterRowStart);
        $setterRowHtml = substr($content, $setterRowStart, $setterRowEnd - $setterRowStart);

        $firstColPos = strpos($setterRowHtml, 'First on Channel'); // Or check links in the row
        $videoLinkCount = substr_count($setterRowHtml, route('videos.show', 'the-only-solve-video'));
        $this->assertSame(1, $videoLinkCount, 'The video link should appear only once in the row (under First on Channel), with Most Recent left blank.');
    }

    /**
     * Test sorting on /setters page by name, puzzles, first, and recent.
     */
    public function test_setters_index_sorting(): void
    {
        $setterA = Setter::factory()->create([
            'name' => 'Alpha Setter',
            'slug' => 'alpha-setter',
        ]);
        $setterZ = Setter::factory()->create([
            'name' => 'Zulu Setter',
            'slug' => 'zulu-setter',
        ]);

        $category = Category::factory()->create();
        $puzzleA = Puzzle::factory()->create(['category_id' => $category->id]);
        $puzzleA->setters()->attach($setterA->id);

        $puzzleZ1 = Puzzle::factory()->create(['category_id' => $category->id]);
        $puzzleZ2 = Puzzle::factory()->create(['category_id' => $category->id]);
        $puzzleZ1->setters()->attach($setterZ->id);
        $puzzleZ2->setters()->attach($setterZ->id);

        // Sort by name asc
        $responseNameAsc = $this->get('/setters?sort=name&dir=asc');
        $responseNameAsc->assertStatus(200);
        $content = $responseNameAsc->getContent();
        $posA = strpos($content, 'Alpha Setter');
        $posZ = strpos($content, 'Zulu Setter');
        $this->assertTrue($posA < $posZ, 'Alpha Setter should appear before Zulu Setter.');

        // Sort by puzzles desc (Zulu has 2, Alpha has 1)
        $responsePuzzlesDesc = $this->get('/setters?sort=puzzles&dir=desc');
        $responsePuzzlesDesc->assertStatus(200);
        $contentPuzzles = $responsePuzzlesDesc->getContent();
        $posZ = strpos($contentPuzzles, 'Zulu Setter');
        $posA = strpos($contentPuzzles, 'Alpha Setter');
        $this->assertTrue($posZ < $posA, 'Zulu Setter should appear before Alpha Setter when sorted by puzzles desc.');

        // Filter by search query
        $responseSearch = $this->get('/setters?q=Alpha');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Alpha Setter');
        $responseSearch->assertDontSee('Zulu Setter');
    }

    /**
     * Test that /setters/{slug} displays setter name, bio, and featured videos.
     */
    public function test_setter_show_page_displays_name_bio_and_videos(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Master Solver',
            'slug' => 'master-solver',
            'bio' => 'World championship contender and renowned puzzle author.',
        ]);

        $category = Category::factory()->create();
        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Masterful Sudoku Creation',
        ]);
        $puzzle->setters()->attach($setter->id);

        $videoFinalized = Video::factory()->create([
            'title' => 'Masterful Solve Episode',
            'slug' => 'masterful-solve-episode',
            'is_finalized' => true,
            'published_at' => now(),
        ]);
        $videoFinalized->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $response = $this->get(route('setters.show', $setter->slug));
        $response->assertStatus(200);

        // Displays setter name and bio
        $response->assertSee('Master Solver');
        $response->assertSee('World championship contender and renowned puzzle author.');

        // Displays video featuring their puzzle
        $response->assertSee('Masterful Solve Episode');
        $response->assertSee(route('videos.show', $videoFinalized->slug));

        // Finalized video tile renders highlight classes and data-finalized="true"
        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');
        $response->assertSee('data-finalized="true"', false);
    }

    /**
     * Test that /setters/{invalid-slug} returns 404.
     */
    public function test_invalid_setter_slug_returns_404(): void
    {
        $response = $this->get('/setters/non-existent-setter-xyz');
        $response->assertStatus(404);
    }

    /**
     * Test that setter bio with markdown renders formatted HTML.
     */
    public function test_setter_show_renders_formatted_markdown_bio(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Markdown Author',
            'slug' => 'markdown-author',
            'bio' => "Notable setter known for:\n\n* **Thermosudoku**\n* Miracle sudoku variations",
        ]);

        $response = $this->get(route('setters.show', $setter->slug));
        $response->assertStatus(200);

        $response->assertSee('ctc-markdown-content', false);
        $response->assertSee('<strong>Thermosudoku</strong>', false);
        $response->assertSee('<li>', false);
    }

    /**
     * Test that /setters paginates at 20 items per page with working page links.
     */
    public function test_setters_index_paginates_at_twenty_items_per_page(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Setter::factory()->create([
                'name' => "Setter {$num}",
                'slug' => "setter-{$num}",
            ]);
        }

        // Request page 1 with sort by name ascending
        $responsePage1 = $this->get(route('setters.index', ['sort' => 'name', 'dir' => 'asc']));
        $responsePage1->assertStatus(200);

        // Header shows total count 25
        $responsePage1->assertSee('25');
        $responsePage1->assertSee('Showing 1–20 of 25');

        // Page 1 contains items 01 to 20
        $responsePage1->assertSee('Setter 01');
        $responsePage1->assertSee('Setter 20');
        // Page 1 does not contain item 21
        $responsePage1->assertDontSee('Setter 21');

        // Verify pagination link to page 2 is present
        $responsePage1->assertSee('page=2');

        // Request page 2
        $responsePage2 = $this->get(route('setters.index', ['sort' => 'name', 'dir' => 'asc', 'page' => 2]));
        $responsePage2->assertStatus(200);

        // Page 2 shows range 21–25 of 25
        $responsePage2->assertSee('Showing 21–25 of 25');
        // Page 2 contains items 21 to 25
        $responsePage2->assertSee('Setter 21');
        $responsePage2->assertSee('Setter 25');
        // Page 2 does not contain items from page 1
        $responsePage2->assertDontSee('Setter 01');
        $responsePage2->assertDontSee('Setter 20');
    }
}
