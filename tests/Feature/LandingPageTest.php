<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test for the catalog landing page.
 *
 * Verifies featured video hero, dynamic stats bar, and recent video feed.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the landing page renders with featured video, stats, and recent videos.
     */
    public function test_landing_page_displays_featured_video_stats_and_feed(): void
    {
        $host = Host::factory()->create(['name' => 'Simon Anthony']);
        $setter = Setter::factory()->create(['name' => 'Phistomefel']);
        $category = Category::factory()->create(['name' => 'Variant Sudoku']);
        $constraint = Constraint::factory()->create(['name' => 'Thermo']);

        $featuredVideo = Video::factory()->create([
            'title' => 'The Miracle Sudoku',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
        ]);
        $featuredVideo->hosts()->attach($host->id);

        $puzzle = Puzzle::factory()
            ->withSudokupad('https://sudokupad.app/miracle-demo')
            ->create([
                'category_id' => $category->id,
                'title' => 'Miracle Grid',
            ]);
        $puzzle->setters()->attach($setter->id);
        $puzzle->constraints()->attach($constraint->id);
        $featuredVideo->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $patreonVideo = Video::factory()->patreon()->create([
            'title' => 'Exclusive Patreon Masterclass',
            'published_at' => now()->subDay(),
        ]);
        $patreonVideo->hosts()->attach($host->id);

        $liveStreamVideo = Video::factory()->liveStream()->create([
            'title' => 'Sunday Live Stream Replay',
            'published_at' => now()->subDays(2),
        ]);
        $liveStreamVideo->hosts()->attach($host->id);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Cataloging the Cryptic');
        $response->assertSee('The Miracle Sudoku');
        // Hero video should not be tagged as featured video, but should keep other tags (Public, YouTube)
        $response->assertDontSee('Featured Upload');
        $response->assertDontSee('Featured Video');
        $response->assertSee('Public');
        // Verify hero playable YouTube embed iframe
        $response->assertSee('https://www.youtube-nocookie.com/embed/'.$featuredVideo->youtube_video_id);
        // Verify hero title links to our individual video page
        $response->assertSee(route('videos.show', $featuredVideo->slug));
        // Verify older video card title links to our individual video page
        $response->assertSee(route('videos.show', $patreonVideo->slug));
        $response->assertSee(route('videos.show', $liveStreamVideo->slug));
        $response->assertSee('Simon Anthony');
        $response->assertSee('Miracle Grid');
        $response->assertSee('Phistomefel');
        $response->assertSee('Thermo');
        $response->assertSee('https://sudokupad.app/miracle-demo');
        $response->assertSee('Exclusive Patreon Masterclass');
        $response->assertSee('Patreon');
        $response->assertSee('Live Stream');
        $response->assertSee('Total Cataloged Videos');
        $response->assertSee('Total Puzzles Indexed');
        $response->assertSee('Distinct Setters');
        $response->assertSee('Distinct Constraints');
        $response->assertSee(route('constraints.index'));
    }

    /**
     * Test that individual video detail page displays playable player, puzzles, and interactive bookmarks.
     */
    public function test_individual_video_page_renders_successfully(): void
    {
        $video = Video::factory()->create([
            'title' => 'Miracle Sudoku Solve',
            'video_type' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);

        $category = Category::factory()->create(['name' => 'Variant Sudoku']);
        $puzzle = Puzzle::factory()
            ->withSudokupad('https://sudokupad.app/miracle-grid')
            ->create([
                'category_id' => $category->id,
                'title' => 'The Miracle Grid',
                'rules_raw' => 'Normal Sudoku rules apply.',
            ]);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $video->bookmarks()->create([
            'timestamp_seconds' => 120,
            'label' => 'Rules Explanation',
        ]);

        $response = $this->get(route('videos.show', $video->slug));

        $response->assertStatus(200);
        $response->assertSee('Miracle Sudoku Solve');
        $response->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?enablejsapi=1');
        $response->assertSee('The Miracle Grid');
        $response->assertSee('https://sudokupad.app/miracle-grid');
        $response->assertSee('Rules Explanation');
        $response->assertSee('2:00');
        // Verify rules box renders trimmed text without leading blank line
        $response->assertSee('<div class="mt-2 p-3 rounded-lg bg-slate-900 border border-slate-800/60 leading-relaxed whitespace-pre-line text-slate-300">Normal Sudoku rules apply.</div>', false);
    }

    /**
     * Test that an exclusive video page suppresses the YouTube iframe player and renders thumbnail linking to Patreon.
     */
    public function test_exclusive_video_page_suppresses_youtube_embed_and_links_to_patreon(): void
    {
        $post = PatreonPost::create([
            'title' => 'Exclusive Patreon Masterclass Post',
            'slug' => 'exclusive-patreon-masterclass-post',
            'patreon_url' => 'https://www.patreon.com/posts/exclusive-masterclass-12345678',
            'published_at' => now(),
        ]);

        $video = Video::factory()->create([
            'patreon_post_id' => $post->id,
            'title' => 'Exclusive Masterclass Video',
            'video_type' => 'youtube',
            'access_level' => 'exclusive',
            'youtube_video_id' => 'unlisted1234',
            'youtube_thumbnail_url' => 'https://i.ytimg.com/vi/unlisted1234/hqdefault.jpg',
        ]);

        $response = $this->get(route('videos.show', $video->slug));

        $response->assertStatus(200);
        $response->assertSee('Exclusive Masterclass Video');
        // Assert iframe embed is suppressed for exclusive video
        $response->assertDontSee('https://www.youtube-nocookie.com/embed/unlisted1234');
        // Assert thumbnail and Patreon link are rendered
        $response->assertSee('https://i.ytimg.com/vi_webp/unlisted1234/hqdefault.webp');
        $response->assertSee('Watch on Patreon');
        $response->assertSee('https://www.patreon.com/posts/exclusive-masterclass-12345678');
    }

    /**
     * Test that an exclusive puzzle suppresses solve links on video and home pages while displaying metadata.
     */
    public function test_exclusive_puzzle_suppresses_solve_links_on_video_and_home_pages(): void
    {
        $category = Category::factory()->create(['name' => 'Variant Sudoku']);
        $setter = Setter::factory()->create(['name' => 'Phistomefel']);
        $constraint = Constraint::factory()->create(['name' => 'German Whispers']);

        $exclusivePuzzle = Puzzle::factory()
            ->exclusive()
            ->withSudokupad('https://sudokupad.app/whispering-shadows-secret')
            ->withLink('penpa', 'https://penpa-edit.appspot.com/whispering-shadows-secret')
            ->create([
                'category_id' => $category->id,
                'title' => 'Whispering Shadows',
                'rules_raw' => 'Normal Sudoku rules apply. German Whispers lines throughout.',
            ]);
        $exclusivePuzzle->setters()->attach($setter->id);
        $exclusivePuzzle->constraints()->attach($constraint->id);

        $video = Video::factory()->create([
            'title' => 'Masterclass Video Solve',
            'published_at' => now(),
        ]);
        $video->puzzles()->attach($exclusivePuzzle->id, ['order_in_video' => 1]);

        // 1. Verify Video Show Page
        $videoResponse = $this->get(route('videos.show', $video->slug));
        $videoResponse->assertStatus(200);
        $videoResponse->assertSee('Whispering Shadows');
        $videoResponse->assertSee('Phistomefel');
        $videoResponse->assertSee('German Whispers');
        $videoResponse->assertSee('Normal Sudoku rules apply');
        $videoResponse->assertSee('Patron Exclusive');
        $videoResponse->assertDontSee('https://sudokupad.app/whispering-shadows-secret');
        $videoResponse->assertDontSee('https://penpa-edit.appspot.com/whispering-shadows-secret');

        // 2. Verify Home Page (Featured Video)
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Whispering Shadows');
        $homeResponse->assertSee('Phistomefel');
        $homeResponse->assertSee('German Whispers');
        $homeResponse->assertSee('Patron Exclusive');
        $homeResponse->assertDontSee('https://sudokupad.app/whispering-shadows-secret');
    }

    /**
     * Test that multiple puzzle links of diverse types render correctly on video and home pages.
     */
    public function test_multiple_puzzle_link_types_render_on_video_and_home_pages(): void
    {
        $category = Category::factory()->create(['name' => 'Crossword']);
        $puzzle = Puzzle::factory()
            ->withSudokupad('https://sudokupad.app/multi-platform-demo')
            ->withLink('logic_masters_germany', 'https://logic-masters.de/Raetselportal/Raetsel/zeigen.php?id=99999')
            ->withLink('penpa', 'https://penpa-edit.appspot.com/multi-demo')
            ->withLink('times_puzzles', 'https://www.thetimes.co.uk/puzzles/crossword-1234')
            ->withLink('other', 'https://custom-archive.org/puzzle/42', 'Special CTC Archive')
            ->create([
                'category_id' => $category->id,
                'title' => 'Multi Platform Puzzle',
            ]);

        $video = Video::factory()->create([
            'title' => 'Multi Link Video Episode',
            'published_at' => now(),
        ]);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        // Video Show Page Verification
        $videoResponse = $this->get(route('videos.show', $video->slug));
        $videoResponse->assertStatus(200);
        $videoResponse->assertSee('https://sudokupad.app/multi-platform-demo');
        $videoResponse->assertSee('SudokuPad');
        $videoResponse->assertSee('https://logic-masters.de/Raetselportal/Raetsel/zeigen.php?id=99999');
        $videoResponse->assertSee('Logic Masters Germany');
        $videoResponse->assertSee('https://penpa-edit.appspot.com/multi-demo');
        $videoResponse->assertSee('Penpa');
        $videoResponse->assertSee('https://www.thetimes.co.uk/puzzles/crossword-1234');
        $videoResponse->assertSee('Times Puzzles');
        $videoResponse->assertSee('https://custom-archive.org/puzzle/42');
        $videoResponse->assertSee('Special CTC Archive');

        // Home Page Verification
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('https://sudokupad.app/multi-platform-demo');
        $homeResponse->assertSee('SudokuPad');
        $homeResponse->assertSee('Logic Masters Germany');
        $homeResponse->assertSee('Times Puzzles');
        $homeResponse->assertSee('Special CTC Archive');
    }

    /**
     * Test that the video page renders interactive bookmarks with seamless seeking and suppresses the section when empty.
     */
    public function test_video_page_renders_interactive_bookmarks_with_seamless_seeking(): void
    {
        $category = Category::create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $videoWithBookmarks = Video::factory()->create([
            'title' => 'Speed Solve with Multiple Bookmarks',
            'slug' => 'speed-solve-with-multiple-bookmarks',
            'access_level' => 'public',
            'youtube_video_id' => 'speed1234',
        ]);

        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Sprint Puzzle 1',
        ]);
        $videoWithBookmarks->puzzles()->attach($puzzle->id, [
            'order_in_video' => 1,
            'start_seconds' => 45,
            'solve_duration_seconds' => 300,
        ]);

        $videoWithBookmarks->bookmarks()->createMany([
            [
                'timestamp_seconds' => 0,
                'label' => 'Introduction and Welcome',
            ],
            [
                'timestamp_seconds' => 45,
                'label' => 'Sprint Puzzle 1 Start',
                'puzzle_id' => $puzzle->id,
            ],
            [
                'timestamp_seconds' => 420,
                'label' => 'Major Breakthrough',
            ],
        ]);

        $response = $this->get(route('videos.show', $videoWithBookmarks->slug));
        $response->assertStatus(200);

        // Verify Bookmarks Header & Count
        $response->assertSee('Video Bookmarks & Timestamps', false);
        $response->assertSee('Click any bookmark to jump directly to that moment without reloading the page.');

        // Verify Each Bookmark and its interactive attributes
        $response->assertSee('0');
        $response->assertSee('Introduction and Welcome');
        $response->assertSee('onclick="seekTo(0, this)"', false);
        $response->assertSee('data-timestamp="0"', false);

        $response->assertSee('45');
        $response->assertSee('Sprint Puzzle 1 Start');
        $response->assertSee('onclick="seekTo(45, this)"', false);
        $response->assertSee('data-timestamp="45"', false);
        $response->assertSee('Puzzle: Sprint Puzzle 1');

        $response->assertSee('7:00');
        $response->assertSee('Major Breakthrough');
        $response->assertSee('onclick="seekTo(420, this)"', false);
        $response->assertSee('data-timestamp="420"', false);

        // Verify Puzzle Card also has clickable start timestamp
        $response->assertSee('onclick="seekTo(45)"', false);

        // Verify YouTube Iframe API script is present
        $response->assertSee('https://www.youtube.com/iframe_api');

        // Verify video without bookmarks suppresses bookmarks container
        $videoWithoutBookmarks = Video::factory()->create([
            'title' => 'Video Without Any Bookmarks',
            'slug' => 'video-without-any-bookmarks',
            'access_level' => 'public',
            'youtube_video_id' => 'nobookmarks',
        ]);

        $emptyResponse = $this->get(route('videos.show', $videoWithoutBookmarks->slug));
        $emptyResponse->assertStatus(200);
        $emptyResponse->assertDontSee('Video Bookmarks & Timestamps', false);
    }

    /**
     * Test that the layout uses the external site.css stylesheet and does not embed header/footer style tags.
     */
    public function test_layout_uses_external_site_stylesheet_without_embedded_style_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('/css/site.css');
        $response->assertDontSee('.ctc-public-header {', false);
        $response->assertDontSee('.ctc-public-footer {', false);
    }

    /**
     * Test that the header renders both desktop navigation and the mobile hamburger menu with all links.
     */
    public function test_header_renders_desktop_navigation_and_mobile_hamburger_menu(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Desktop nav container & videos dropdown menu
        $response->assertSee('ctc-desktop-nav');
        $response->assertSee('ctc-nav-dropdown');
        $response->assertSee('ctc-nav-caret');
        $response->assertSee('ctc-dropdown-menu');
        $response->assertSee('All Videos');
        $response->assertSee('Most Viewed');
        $response->assertSee('Longest');
        $response->assertSee('Shortest');

        // Mobile hamburger toggle button & accessible attributes
        $response->assertSee('id="ctc-mobile-menu-btn"', false);
        $response->assertSee('aria-label="Toggle navigation menu"', false);
        $response->assertSee('aria-controls="ctc-mobile-menu"', false);
        $response->assertSee('id="ctc-icon-hamburger"', false);
        $response->assertSee('id="ctc-icon-close"', false);

        // Mobile menu container & links
        $response->assertSee('id="ctc-mobile-menu"', false);
        $response->assertSee('ctc-mobile-nav-link');
        $response->assertSee(route('videos.index'));
        $response->assertSee(route('videos.top'));
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.shortest'));
        $response->assertSee(route('setters.index'));
        $response->assertSee(route('stats'));
        $response->assertSee(route('videos.random'));
        $response->assertSee(route('search'));
    }
}
