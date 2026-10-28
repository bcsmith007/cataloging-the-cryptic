<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Host;
use App\Models\Puzzle;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test for the video catalog directory (/videos/).
 *
 * Verifies that all videos are listed in reverse chronological order,
 * displaying thumbnail, video name, host(s), puzzle type, video length, and views.
 */
class VideoCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that /videos/ and /videos return 200 and list all videos in reverse chronological order.
     */
    public function test_videos_index_page_lists_videos_in_reverse_chronological_order(): void
    {
        $host1 = Host::factory()->create(['name' => 'Simon Anthony']);
        $host2 = Host::factory()->create(['name' => 'Mark Goodliffe']);

        $category1 = Category::factory()->create(['name' => 'Variant Sudoku']);
        $category2 = Category::factory()->create(['name' => 'Classic Sudoku']);

        $puzzle1 = Puzzle::factory()->create([
            'category_id' => $category1->id,
            'title' => 'Miracle Sudoku Grid',
        ]);

        $puzzle2 = Puzzle::factory()->create([
            'category_id' => $category2->id,
            'title' => 'Daily Classic Grid',
        ]);

        // Older video (3 days ago)
        $olderVideo = Video::factory()->create([
            'title' => 'Older Video Puzzle Solve',
            'slug' => 'older-video-puzzle-solve',
            'published_at' => now()->subDays(3),
            'duration_seconds' => 2715, // 45:15
            'view_count' => 12500,
            'youtube_thumbnail_url' => 'https://img.youtube.com/vi/oldervid123/hqdefault.jpg',
        ]);
        $olderVideo->hosts()->attach($host1->id);
        $olderVideo->puzzles()->attach($puzzle1->id, ['order_in_video' => 1]);

        // Newer video (yesterday)
        $newerVideo = Video::factory()->create([
            'title' => 'Newer Video Puzzle Solve',
            'slug' => 'newer-video-puzzle-solve',
            'published_at' => now()->subDay(),
            'duration_seconds' => 5430, // 1:30:30
            'view_count' => 45000,
            'youtube_thumbnail_url' => 'https://img.youtube.com/vi/newervid456/hqdefault.jpg',
        ]);
        $newerVideo->hosts()->attach([$host1->id, $host2->id]);
        $newerVideo->puzzles()->attach($puzzle2->id, ['order_in_video' => 1]);

        // Brand newest video (today)
        $newestVideo = Video::factory()->create([
            'title' => 'Newest Video Masterclass',
            'slug' => 'newest-video-masterclass',
            'published_at' => now(),
            'duration_seconds' => 45, // 45
            'view_count' => 98765,
            'youtube_thumbnail_url' => 'https://img.youtube.com/vi/newestvid789/hqdefault.jpg',
        ]);
        $newestVideo->hosts()->attach($host2->id);

        // Test with /videos
        $response = $this->get('/videos');
        $response->assertStatus(200);

        // Verify reverse chronological ordering in content
        $content = $response->getContent();
        $newestPos = strpos($content, 'Newest Video Masterclass');
        $newerPos = strpos($content, 'Newer Video Puzzle Solve');
        $olderPos = strpos($content, 'Older Video Puzzle Solve');

        $this->assertNotFalse($newestPos);
        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertTrue($newestPos < $newerPos, 'Newest video should appear before newer video.');
        $this->assertTrue($newerPos < $olderPos, 'Newer video should appear before older video.');

        // Verify Thumbnail
        $response->assertSee('https://img.youtube.com/vi_webp/oldervid123/hqdefault.webp');
        $response->assertSee('https://img.youtube.com/vi_webp/newervid456/hqdefault.webp');
        $response->assertSee('https://img.youtube.com/vi_webp/newestvid789/hqdefault.webp');

        // Verify Video Name
        $response->assertSee('Newest Video Masterclass');
        $response->assertSee('Newer Video Puzzle Solve');
        $response->assertSee('Older Video Puzzle Solve');

        // Verify Host(s)
        $response->assertSee('Simon Anthony');
        $response->assertSee('Mark Goodliffe');

        // Verify Puzzle Type
        $response->assertSee('Variant Sudoku');
        $response->assertSee('Classic Sudoku');

        // Verify Video Length
        $response->assertSee('1:30:30');
        $response->assertSee('45:15');
        $response->assertSee('45');

        // Verify Views
        $response->assertSee('98,765 views');
        $response->assertSee('45,000 views');
        $response->assertSee('12,500 views');

        // Verify route link
        $response->assertSee(route('videos.show', $newerVideo->slug));

        // Test with trailing slash /videos/
        $slashResponse = $this->get('/videos/');
        $slashResponse->assertStatus(200);
        $slashResponse->assertSee('Newest Video Masterclass');
    }

    /**
     * Test that empty state is rendered when no videos exist.
     */
    public function test_videos_index_renders_empty_state_when_no_videos(): void
    {
        $response = $this->get('/videos');
        $response->assertStatus(200);
        $response->assertSee('No videos cataloged');
    }

    /**
     * Test pagination on /videos when exceeding per-page limit.
     */
    public function test_videos_index_paginates_when_many_records(): void
    {
        Video::factory()->count(30)->create();

        $response = $this->get('/videos');
        $response->assertStatus(200);
        $response->assertSee('Next');
    }

    /**
     * Test that finalized videos render a slight border glow on the /videos/ directory.
     */
    public function test_finalized_videos_render_border_glow(): void
    {
        $finalizedVideo = Video::factory()->create([
            'title' => 'Finalized Video',
            'is_finalized' => true,
        ]);

        $unfinalizedVideo = Video::factory()->create([
            'title' => 'Unfinalized Video',
            'is_finalized' => false,
        ]);

        $response = $this->get('/videos');
        $response->assertStatus(200);

        // Finalized video tile has slight border glow classes
        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');

        // Verify data-finalized attributes
        $response->assertSee('data-finalized="true"', false);
        $response->assertSee('data-finalized="false"', false);
    }

    /**
     * Test that finalized videos render a slight border glow on /videos/longest.
     */
    public function test_finalized_videos_render_border_glow_on_longest_videos_page(): void
    {
        $finalizedVideo = Video::factory()->create([
            'title' => 'Long Finalized Video',
            'is_finalized' => true,
            'duration_seconds' => 7200,
            'video_type' => 'youtube',
        ]);

        $unfinalizedVideo = Video::factory()->create([
            'title' => 'Long Unfinalized Video',
            'is_finalized' => false,
            'duration_seconds' => 7100,
            'video_type' => 'youtube',
        ]);

        $response = $this->get('/videos/longest');
        $response->assertStatus(200);

        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');
        $response->assertSee('data-finalized="true"', false);
        $response->assertSee('data-finalized="false"', false);
    }

    /**
     * Test that finalized videos render a slight border glow on /videos/top.
     */
    public function test_finalized_videos_render_border_glow_on_top_videos_page(): void
    {
        $finalizedVideo = Video::factory()->create([
            'title' => 'Top Finalized Video',
            'is_finalized' => true,
            'view_count' => 50000,
        ]);

        $unfinalizedVideo = Video::factory()->create([
            'title' => 'Top Unfinalized Video',
            'is_finalized' => false,
            'view_count' => 45000,
        ]);

        $response = $this->get('/videos/top');
        $response->assertStatus(200);

        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');
        $response->assertSee('data-finalized="true"', false);
        $response->assertSee('data-finalized="false"', false);
    }

    /**
     * Test that finalized videos render a slight border glow on the homepage recent video tiles.
     */
    public function test_finalized_videos_render_border_glow_on_homepage(): void
    {
        // First video will be featured hero, next videos will appear in recentVideos grid
        Video::factory()->create([
            'title' => 'Hero Video',
            'published_at' => now()->addMinutes(10),
        ]);

        $finalizedRecent = Video::factory()->create([
            'title' => 'Recent Finalized Video',
            'is_finalized' => true,
            'published_at' => now()->addMinutes(5),
        ]);

        $unfinalizedRecent = Video::factory()->create([
            'title' => 'Recent Unfinalized Video',
            'is_finalized' => false,
            'published_at' => now(),
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');
        $response->assertSee('data-finalized="true"', false);
        $response->assertSee('data-finalized="false"', false);
    }

    /**
     * Test that finalized videos render a slight border glow on the search results page.
     */
    public function test_finalized_videos_render_border_glow_on_search_page(): void
    {
        $finalizedVideo = Video::factory()->create([
            'title' => 'UniqueFinalizedSearchPuzzle Title',
            'is_finalized' => true,
        ]);

        $unfinalizedVideo = Video::factory()->create([
            'title' => 'UniqueUnfinalizedSearchPuzzle Title',
            'is_finalized' => false,
        ]);

        $response = $this->get('/search?q=UniqueFinalizedSearchPuzzle');
        $response->assertStatus(200);

        $response->assertSee('border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)]');
        $response->assertSee('data-finalized="true"', false);
    }

    /**
     * Test that /videos/top displays the top 24 most viewed videos ordered by views descending.
     */
    public function test_top_videos_page_displays_top_24_most_viewed_videos_ordered_by_views_desc(): void
    {
        // Create 28 videos with distinct view counts from 1,000 to 28,000
        for ($i = 1; $i <= 28; $i++) {
            Video::factory()->create([
                'title' => "Video Number {$i}",
                'slug' => "video-number-{$i}",
                'view_count' => $i * 1000,
                'published_at' => now()->subDays(30 - $i),
            ]);
        }

        $response = $this->get('/videos/top');
        $response->assertStatus(200);
        $response->assertSee('Top 24 Most Viewed Videos');
        $response->assertSee('#1');
        $response->assertSee('#2');
        $response->assertSee('#3');
        $response->assertSee('#24');

        $content = $response->getContent();

        // The top video should be Video Number 28 (28,000 views)
        $topPos = strpos($content, 'Video Number 28');
        $midPos = strpos($content, 'Video Number 20');
        $lowPos = strpos($content, 'Video Number 5');

        $this->assertNotFalse($topPos, 'Top viewed video (Video 28) should be present.');
        $this->assertNotFalse($midPos, 'Mid viewed video (Video 20) should be present.');
        $this->assertNotFalse($lowPos, '24th viewed video (Video 5) should be present.');

        // Top should appear before mid, and mid before low
        $this->assertTrue($topPos < $midPos, 'Video 28 should appear before Video 20.');
        $this->assertTrue($midPos < $lowPos, 'Video 20 should appear before Video 5.');

        // Videos 1 through 4 should NOT be in the top 24
        $this->assertStringNotContainsString('Video Number 1<', $content);
        $this->assertStringNotContainsString('Video Number 2<', $content);
        $this->assertStringNotContainsString('Video Number 3<', $content);
        $this->assertStringNotContainsString('Video Number 4<', $content);
    }

    /**
     * Test that /videos/most-viewed redirects to /videos/top.
     */
    public function test_most_viewed_redirects_to_top_videos(): void
    {
        $response = $this->get('/videos/most-viewed');
        $response->assertRedirect('/videos/top');
    }

    /**
     * Test that /videos/longest displays the top 24 longest videos excluding live streams.
     */
    public function test_longest_videos_page_displays_top_24_excluding_live_streams(): void
    {
        // 1. Create a super long live stream (3 hours = 10800s) which MUST be excluded
        $liveStream = Video::factory()->liveStream()->create([
            'title' => 'Sunday 3-Hour Marathon Live Stream',
            'slug' => 'sunday-3-hour-marathon-live-stream',
            'duration_seconds' => 10800,
            'published_at' => now(),
        ]);

        // 2. Create 28 standard/public videos with durations from 1000s to 6400s
        for ($i = 1; $i <= 28; $i++) {
            Video::factory()->create([
                'title' => "Dedicated Solve {$i}",
                'slug' => "dedicated-solve-{$i}",
                'video_type' => 'youtube',
                'duration_seconds' => $i * 200,
                'published_at' => now()->subDays(30 - $i),
            ]);
        }

        $response = $this->get('/videos/longest');
        $response->assertStatus(200);
        $response->assertSee('Top 24 Longest Videos');
        $response->assertSee('Excludes Live Streams');
        $response->assertSee('#1');
        $response->assertSee('#2');
        $response->assertSee('#3');
        $response->assertSee('#24');

        $content = $response->getContent();

        // Ensure the live stream is completely excluded
        $this->assertStringNotContainsString('Sunday 3-Hour Marathon Live Stream', $content);

        // Longest regular video is Dedicated Solve 28 (5600s)
        $firstPos = strpos($content, 'Dedicated Solve 28');
        $midPos = strpos($content, 'Dedicated Solve 20');
        $lastIncludedPos = strpos($content, 'Dedicated Solve 5');

        $this->assertNotFalse($firstPos, 'Longest dedicated solve should appear.');
        $this->assertNotFalse($midPos, 'Mid duration solve should appear.');
        $this->assertNotFalse($lastIncludedPos, '24th longest solve should appear.');

        $this->assertTrue($firstPos < $midPos, 'Dedicated Solve 28 should appear before Dedicated Solve 20.');
        $this->assertTrue($midPos < $lastIncludedPos, 'Dedicated Solve 20 should appear before Dedicated Solve 5.');

        // Solves 1 through 4 should be omitted from top 24
        $this->assertStringNotContainsString('Dedicated Solve 1<', $content);
        $this->assertStringNotContainsString('Dedicated Solve 2<', $content);
        $this->assertStringNotContainsString('Dedicated Solve 3<', $content);
        $this->assertStringNotContainsString('Dedicated Solve 4<', $content);
    }

    /**
     * Test that /videos/random redirects to the show page of a random video.
     */
    public function test_random_video_redirects_to_show_page(): void
    {
        $video = Video::factory()->create([
            'title' => 'Random Mystery Solve',
            'slug' => 'random-mystery-solve',
        ]);

        $response = $this->get('/videos/random');
        $response->assertRedirect(route('videos.show', $video->slug));
    }

    /**
     * Test that /videos/random redirects to /videos if no videos exist.
     */
    public function test_random_video_redirects_to_index_when_no_videos(): void
    {
        $response = $this->get('/videos/random');
        $response->assertRedirect(route('videos.index'));
    }

    /**
     * Test that the site navigation header contains links to Longest and Random video.
     */
    public function test_site_navigation_contains_longest_and_random_links(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.random'));
    }

    /**
     * Test that Admin Panel and Profile links appear in the footer only when an admin is logged in,
     * while guests see Login and no Admin Panel. Top nav contains neither.
     */
    public function test_admin_panel_and_profile_links_in_footer_for_authenticated_admins(): void
    {
        // 1. Guest visitor should see Login in footer, but NOT Admin Panel or Profile
        $guestResponse = $this->get('/');
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee('Login');
        $guestResponse->assertSee(route('filament.admin.auth.login'));
        $guestResponse->assertDontSee('Admin Panel');
        $guestResponse->assertDontSee('Profile');

        // Top nav should not contain Admin Panel or Profile
        $guestContent = $guestResponse->getContent();
        $this->assertStringNotContainsString('ctc-admin-link', $guestContent);
        $this->assertStringNotContainsString('ctc-profile-link', $guestContent);

        // 2. Authenticated admin should see Admin Panel and Profile in footer, and Login should be replaced by Profile
        $admin = User::factory()->create();
        $adminResponse = $this->actingAs($admin)->get('/');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Admin Panel');
        $adminResponse->assertSee('Profile');
        $adminResponse->assertDontSee(route('filament.admin.auth.login'));

        // Top nav should still not contain Admin Panel or Profile
        $adminContent = $adminResponse->getContent();
        $this->assertStringNotContainsString('ctc-admin-link', $adminContent);
        $this->assertStringNotContainsString('ctc-profile-link', $adminContent);
    }

    /**
     * Test that the edit button appears on /videos/ for logged-in admins, opens in a new tab, but is hidden for guests.
     */
    public function test_edit_button_visible_on_videos_page_only_for_logged_in_admins(): void
    {
        $video = Video::factory()->create([
            'title' => 'Admin Edit Target Video',
            'slug' => 'admin-edit-target-video',
            'video_type' => 'youtube',
            'duration_seconds' => 1200,
        ]);

        $editUrl = route('filament.admin.resources.videos.edit', ['record' => $video]);

        // 1. Guest does NOT see Edit button or edit URL
        $guestResponse = $this->get('/videos');
        $guestResponse->assertStatus(200);
        $guestResponse->assertDontSee($editUrl);
        $guestResponse->assertDontSee('Edit</span>', false);

        // 2. Authenticated admin sees Edit button configured with target="_blank"
        $admin = User::factory()->create();
        $adminResponse = $this->actingAs($admin)->get('/videos');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee($editUrl);
        $adminResponse->assertSee('Edit</span>', false);
        $adminResponse->assertSee('target="_blank"', false);
        $adminResponse->assertSee('rel="noopener noreferrer"', false);

        // 3. Authenticated admin sees Edit in Admin button on video show page opening in a new tab
        $showResponse = $this->actingAs($admin)->get(route('videos.show', $video->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($editUrl);
        $showResponse->assertSee('Edit in Admin</span>', false);
        $showResponse->assertSee('target="_blank"', false);
        $showResponse->assertSee('rel="noopener noreferrer"', false);

        // 4. Authenticated admin sees Edit button on top and longest pages opening in a new tab
        $topResponse = $this->actingAs($admin)->get(route('videos.top'));
        $topResponse->assertStatus(200);
        $topResponse->assertSee($editUrl);
        $topResponse->assertSee('target="_blank"', false);

        $longestResponse = $this->actingAs($admin)->get(route('videos.longest'));
        $longestResponse->assertStatus(200);
        $longestResponse->assertSee($editUrl);
        $longestResponse->assertSee('target="_blank"', false);
    }

    /**
     * Test that /videos/{id}/edit redirects to the Filament admin edit page.
     */
    public function test_numeric_video_edit_url_redirects_to_admin_edit_page(): void
    {
        $video = Video::factory()->create();

        $response = $this->get("/videos/{$video->id}/edit");
        $response->assertRedirect(route('filament.admin.resources.videos.edit', ['record' => $video]));
    }

    /**
     * Test that when a video has multiple puzzles, they are listed in the entered order (from first to last).
     */
    public function test_multiple_puzzles_associated_with_video_are_listed_in_entered_order(): void
    {
        $category = Category::factory()->create(['name' => 'Classic Sudoku']);

        $puzzleFirst = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'First Puzzle In Video',
        ]);
        $puzzleSecond = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Second Puzzle In Video',
        ]);
        $puzzleThird = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Third Puzzle In Video',
        ]);

        $video = Video::factory()->create([
            'title' => 'Multi Puzzle Video Showcase',
            'slug' => 'multi-puzzle-video-showcase',
        ]);

        // Attach intentionally out of order (3, then 1, then 2)
        $video->puzzles()->attach($puzzleThird->id, ['order_in_video' => 3]);
        $video->puzzles()->attach($puzzleFirst->id, ['order_in_video' => 1]);
        $video->puzzles()->attach($puzzleSecond->id, ['order_in_video' => 2]);

        // 1. Verify model relation returns them strictly in order_in_video order (1, 2, 3)
        $orderedPuzzles = $video->fresh()->puzzles;
        $this->assertCount(3, $orderedPuzzles);
        $this->assertSame($puzzleFirst->id, $orderedPuzzles[0]->id);
        $this->assertSame($puzzleSecond->id, $orderedPuzzles[1]->id);
        $this->assertSame($puzzleThird->id, $orderedPuzzles[2]->id);

        // 2. Verify public video show page renders them in order from first to last
        $response = $this->get(route('videos.show', $video->slug));
        $response->assertStatus(200);

        $content = $response->getContent();
        $firstPos = strpos($content, 'First Puzzle In Video');
        $secondPos = strpos($content, 'Second Puzzle In Video');
        $thirdPos = strpos($content, 'Third Puzzle In Video');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertNotFalse($thirdPos);
        $this->assertTrue($firstPos < $secondPos, 'First puzzle should appear before second puzzle.');
        $this->assertTrue($secondPos < $thirdPos, 'Second puzzle should appear before third puzzle.');

        // 3. Verify order badges appear for multi-puzzle video
        $response->assertSee('#1');
        $response->assertSee('#2');
        $response->assertSee('#3');
    }

    /**
     * Test that the longest videos page contains host filter links for All Hosts, Simon, and Mark.
     */
    public function test_longest_page_has_host_filter_links(): void
    {
        $response = $this->get('/videos/longest');
        $response->assertStatus(200);

        // Host filter links
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.longest', ['host' => 'simon']));
        $response->assertSee(route('videos.longest', ['host' => 'mark']));
        $response->assertSee('Simon Anthony');
        $response->assertSee('Mark Goodliffe');

        // Global header navigation links are present
        $response->assertSee(route('videos.top'));
        $response->assertSee(route('videos.shortest'));

        // Redundant quick link buttons on the right are removed
        $response->assertDontSee(route('videos.top', ['host' => 'simon']));
        $response->assertDontSee(route('videos.top', ['host' => 'mark']));
    }

    /**
     * Test that the longest videos page filters by Simon or Mark while still excluding live streams.
     */
    public function test_longest_page_filters_by_simon_and_mark_excluding_live_streams(): void
    {
        $simon = Host::factory()->create(['name' => 'Simon Anthony', 'slug' => 'simon-anthony']);
        $mark = Host::factory()->create(['name' => 'Mark Goodliffe', 'slug' => 'mark-goodliffe']);

        // Simon's regular video (3000s)
        $simonVideo = Video::factory()->create([
            'title' => 'Simon Long Solve Marathon',
            'video_type' => 'youtube',
            'duration_seconds' => 3000,
        ]);
        $simonVideo->hosts()->attach($simon->id);

        // Simon's live stream (7200s) - MUST be excluded
        $simonLiveStream = Video::factory()->liveStream()->create([
            'title' => 'Simon 2-Hour Live Stream Event',
            'duration_seconds' => 7200,
        ]);
        $simonLiveStream->hosts()->attach($simon->id);

        // Mark's regular video (4000s)
        $markVideo = Video::factory()->create([
            'title' => 'Mark Masterclass Long Solve',
            'video_type' => 'youtube',
            'duration_seconds' => 4000,
        ]);
        $markVideo->hosts()->attach($mark->id);

        // Query Simon's longest
        $responseSimon = $this->get('/videos/longest?host=simon');
        $responseSimon->assertStatus(200);
        $responseSimon->assertSee('Top 24 Longest with Simon Anthony');
        $responseSimon->assertSee('Simon Long Solve Marathon');
        $responseSimon->assertDontSee('Simon 2-Hour Live Stream Event');
        $responseSimon->assertDontSee('Mark Masterclass Long Solve');

        // Query Mark's longest
        $responseMark = $this->get('/videos/longest?host=mark');
        $responseMark->assertStatus(200);
        $responseMark->assertSee('Top 24 Longest with Mark Goodliffe');
        $responseMark->assertSee('Mark Masterclass Long Solve');
        $responseMark->assertDontSee('Simon Long Solve Marathon');
        $responseMark->assertDontSee('Simon 2-Hour Live Stream Event');

        // Default query (all hosts) sees both regular solves but still excludes live stream
        $responseAll = $this->get('/videos/longest');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Top 24 Longest Videos');
        $responseAll->assertSee('Simon Long Solve Marathon');
        $responseAll->assertSee('Mark Masterclass Long Solve');
        $responseAll->assertDontSee('Simon 2-Hour Live Stream Event');
    }

    /**
     * Test that the most viewed page contains host filter links for All Hosts, Simon, and Mark.
     */
    public function test_top_page_has_host_filter_links(): void
    {
        $response = $this->get('/videos/top');
        $response->assertStatus(200);

        // Host filter links
        $response->assertSee(route('videos.top'));
        $response->assertSee(route('videos.top', ['host' => 'simon']));
        $response->assertSee(route('videos.top', ['host' => 'mark']));
        $response->assertSee('Simon Anthony');
        $response->assertSee('Mark Goodliffe');

        // Global header navigation links are present
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.shortest'));

        // Redundant quick link buttons on the right are removed
        $response->assertDontSee(route('videos.longest', ['host' => 'simon']));
        $response->assertDontSee(route('videos.longest', ['host' => 'mark']));
    }

    /**
     * Test that the most viewed page filters by Simon or Mark.
     */
    public function test_top_page_filters_by_simon_and_mark(): void
    {
        $simon = Host::factory()->create(['name' => 'Simon Anthony', 'slug' => 'simon-anthony']);
        $mark = Host::factory()->create(['name' => 'Mark Goodliffe', 'slug' => 'mark-goodliffe']);

        $simonVideo = Video::factory()->create([
            'title' => 'Simon Viral Miracle Solve',
            'view_count' => 950000,
        ]);
        $simonVideo->hosts()->attach($simon->id);

        $markVideo = Video::factory()->create([
            'title' => 'Mark Viral GAS Speed Solve',
            'view_count' => 850000,
        ]);
        $markVideo->hosts()->attach($mark->id);

        // Filter by Simon
        $responseSimon = $this->get('/videos/top?host=simon');
        $responseSimon->assertStatus(200);
        $responseSimon->assertSee('Top 24 Most Viewed with Simon Anthony');
        $responseSimon->assertSee('Simon Viral Miracle Solve');
        $responseSimon->assertDontSee('Mark Viral GAS Speed Solve');

        // Filter by Mark
        $responseMark = $this->get('/videos/top?host=mark');
        $responseMark->assertStatus(200);
        $responseMark->assertSee('Top 24 Most Viewed with Mark Goodliffe');
        $responseMark->assertSee('Mark Viral GAS Speed Solve');
        $responseMark->assertDontSee('Simon Viral Miracle Solve');
    }

    /**
     * Test that /videos/shortest displays the top 24 shortest videos excluding YouTube Shorts.
     */
    public function test_shortest_videos_page_displays_top_24_excluding_youtube_shorts(): void
    {
        // 1. Create a YouTube Short which MUST be excluded
        $youtubeShort = Video::factory()->short()->create([
            'title' => 'Viral YouTube Short Solve',
            'slug' => 'viral-youtube-short-solve',
            'duration_seconds' => 45,
            'published_at' => now(),
        ]);

        // 2. Create 28 standard/public videos with durations from 300s to 8400s
        for ($i = 1; $i <= 28; $i++) {
            Video::factory()->create([
                'title' => "Short Solve Number {$i}",
                'slug' => "short-solve-number-{$i}",
                'video_type' => 'youtube',
                'duration_seconds' => $i * 200,
                'published_at' => now()->subDays(30 - $i),
            ]);
        }

        $response = $this->get('/videos/shortest');
        $response->assertStatus(200);
        $response->assertSee('Top 24 Shortest Videos');
        $response->assertSee('Excludes YouTube Shorts');
        $response->assertSee('#1');
        $response->assertSee('#2');
        $response->assertSee('#3');
        $response->assertSee('#24');

        $content = $response->getContent();

        // Ensure the YouTube Short is completely excluded
        $this->assertStringNotContainsString('Viral YouTube Short Solve', $content);

        // Shortest regular video is Short Solve Number 1 (200s)
        $firstPos = strpos($content, 'Short Solve Number 1');
        $midPos = strpos($content, 'Short Solve Number 10');
        $lastIncludedPos = strpos($content, 'Short Solve Number 24');

        $this->assertNotFalse($firstPos, 'Shortest dedicated solve should appear.');
        $this->assertNotFalse($midPos, 'Mid duration solve should appear.');
        $this->assertNotFalse($lastIncludedPos, '24th shortest solve should appear.');

        $this->assertTrue($firstPos < $midPos, 'Short Solve 1 should appear before Short Solve 10.');
        $this->assertTrue($midPos < $lastIncludedPos, 'Short Solve 10 should appear before Short Solve 24.');

        // Solves 25 through 28 should be omitted from top 24
        $this->assertStringNotContainsString('Short Solve Number 25<', $content);
        $this->assertStringNotContainsString('Short Solve Number 26<', $content);
        $this->assertStringNotContainsString('Short Solve Number 27<', $content);
        $this->assertStringNotContainsString('Short Solve Number 28<', $content);
    }

    /**
     * Test that the shortest videos page contains host filter links for All Hosts, Simon, and Mark.
     */
    public function test_shortest_page_has_host_filter_links(): void
    {
        $response = $this->get('/videos/shortest');
        $response->assertStatus(200);

        // Host filter links
        $response->assertSee(route('videos.shortest'));
        $response->assertSee(route('videos.shortest', ['host' => 'simon']));
        $response->assertSee(route('videos.shortest', ['host' => 'mark']));
        $response->assertSee('Simon Anthony');
        $response->assertSee('Mark Goodliffe');

        // Global header navigation links are present
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.top'));

        // Redundant quick link buttons on the right are removed
        $response->assertDontSee(route('videos.longest', ['host' => 'simon']));
        $response->assertDontSee(route('videos.longest', ['host' => 'mark']));
        $response->assertDontSee(route('videos.top', ['host' => 'simon']));
        $response->assertDontSee(route('videos.top', ['host' => 'mark']));
    }

    /**
     * Test that the shortest videos page filters by Simon or Mark while still excluding YouTube Shorts.
     */
    public function test_shortest_page_filters_by_simon_and_mark_excluding_youtube_shorts(): void
    {
        $simon = Host::factory()->create(['name' => 'Simon Anthony', 'slug' => 'simon-anthony']);
        $mark = Host::factory()->create(['name' => 'Mark Goodliffe', 'slug' => 'mark-goodliffe']);

        // Simon's regular short solve (450s)
        $simonVideo = Video::factory()->create([
            'title' => 'Simon Quick Speed Solve',
            'video_type' => 'youtube',
            'duration_seconds' => 450,
        ]);
        $simonVideo->hosts()->attach($simon->id);

        // Simon's YouTube Short (50s) - MUST be excluded
        $simonShort = Video::factory()->short()->create([
            'title' => 'Simon 50-Second Short Clip',
            'duration_seconds' => 50,
        ]);
        $simonShort->hosts()->attach($simon->id);

        // Mark's regular short solve (350s)
        $markVideo = Video::factory()->create([
            'title' => 'Mark GAS Quick Solve',
            'video_type' => 'youtube',
            'duration_seconds' => 350,
        ]);
        $markVideo->hosts()->attach($mark->id);

        // Mark's YouTube Short (30s) - MUST be excluded
        $markShort = Video::factory()->short()->create([
            'title' => 'Mark 30-Second Short Clip',
            'duration_seconds' => 30,
        ]);
        $markShort->hosts()->attach($mark->id);

        // Query Simon's shortest
        $responseSimon = $this->get('/videos/shortest?host=simon');
        $responseSimon->assertStatus(200);
        $responseSimon->assertSee('Top 24 Shortest with Simon Anthony');
        $responseSimon->assertSee('Simon Quick Speed Solve');
        $responseSimon->assertDontSee('Simon 50-Second Short Clip');
        $responseSimon->assertDontSee('Mark GAS Quick Solve');

        // Query Mark's shortest
        $responseMark = $this->get('/videos/shortest?host=mark');
        $responseMark->assertStatus(200);
        $responseMark->assertSee('Top 24 Shortest with Mark Goodliffe');
        $responseMark->assertSee('Mark GAS Quick Solve');
        $responseMark->assertDontSee('Mark 30-Second Short Clip');
        $responseMark->assertDontSee('Simon Quick Speed Solve');

        // Default query (all hosts) sees both regular solves but still excludes both shorts
        $responseAll = $this->get('/videos/shortest');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Top 24 Shortest Videos');
        $responseAll->assertSee('Simon Quick Speed Solve');
        $responseAll->assertSee('Mark GAS Quick Solve');
        $responseAll->assertDontSee('Simon 50-Second Short Clip');
        $responseAll->assertDontSee('Mark 30-Second Short Clip');
    }

    /**
     * Test that Video model previousVideo() and nextVideo() work as expected.
     */
    public function test_video_previous_and_next_model_helpers(): void
    {
        $video1 = Video::factory()->create([
            'title' => 'Video One',
            'slug' => 'video-one',
            'published_at' => now()->subDays(3),
        ]);

        $video2 = Video::factory()->create([
            'title' => 'Video Two',
            'slug' => 'video-two',
            'published_at' => now()->subDays(2),
        ]);

        $video3 = Video::factory()->create([
            'title' => 'Video Three',
            'slug' => 'video-three',
            'published_at' => now()->subDays(1),
        ]);

        // Middle video
        $this->assertSame($video1->id, $video2->previousVideo()?->id);
        $this->assertSame($video3->id, $video2->nextVideo()?->id);

        // First / oldest video
        $this->assertNull($video1->previousVideo());
        $this->assertSame($video2->id, $video1->nextVideo()?->id);

        // Last / newest video
        $this->assertSame($video2->id, $video3->previousVideo()?->id);
        $this->assertNull($video3->nextVideo());
    }

    /**
     * Test that video show page displays previous and next navigation buttons.
     */
    public function test_video_show_page_displays_previous_and_next_navigation_buttons(): void
    {
        $olderVideo = Video::factory()->create([
            'title' => 'Older Video Solve',
            'slug' => 'older-video-solve',
            'published_at' => now()->subDays(2),
        ]);

        $currentVideo = Video::factory()->create([
            'title' => 'Current Video Solve',
            'slug' => 'current-video-solve',
            'published_at' => now()->subDay(),
        ]);

        $newerVideo = Video::factory()->create([
            'title' => 'Newer Video Solve',
            'slug' => 'newer-video-solve',
            'published_at' => now(),
        ]);

        // When viewing middle video: both buttons are active with titles and links
        $response = $this->get(route('videos.show', $currentVideo->slug));
        $response->assertStatus(200);
        $response->assertSee(route('videos.show', $olderVideo->slug));
        $response->assertSee('Older Video Solve');
        $response->assertSee(route('videos.show', $newerVideo->slug));
        $response->assertSee('Newer Video Solve');
        $response->assertSee('Previous Video');
        $response->assertSee('Next Video');

        // When viewing oldest video: no previous video (displays None)
        $oldestResponse = $this->get(route('videos.show', $olderVideo->slug));
        $oldestResponse->assertStatus(200);
        $oldestResponse->assertSee(route('videos.show', $currentVideo->slug));
        $oldestResponse->assertSee('Current Video Solve');

        // When viewing newest video: no next video (displays None)
        $newestResponse = $this->get(route('videos.show', $newerVideo->slug));
        $newestResponse->assertStatus(200);
        $newestResponse->assertSee(route('videos.show', $currentVideo->slug));
        $newestResponse->assertSee('Current Video Solve');
    }
}
