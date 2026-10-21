<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use App\Services\YouTubeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration test verifying Eloquent domain models, relationships, and schema constraints.
 */
class CatalogDomainModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test video relationships with hosts, puzzles, and bookmarks.
     */
    public function test_video_relationships_and_pivots(): void
    {
        $video = Video::factory()->create([
            'video_type' => 'youtube',
            'duration_seconds' => 3665,
        ]);

        $host1 = Host::factory()->create(['name' => 'Simon Anthony']);
        $host2 = Host::factory()->create(['name' => 'Mark Goodliffe']);
        $video->hosts()->attach([$host1->id, $host2->id]);

        $category = Category::factory()->create(['name' => 'Variant Sudoku']);
        $puzzle1 = Puzzle::factory()->create(['category_id' => $category->id]);
        $puzzle2 = Puzzle::factory()->create(['category_id' => $category->id]);

        $video->puzzles()->attach($puzzle1->id, [
            'order_in_video' => 1,
            'start_seconds' => 120,
            'solve_duration_seconds' => 1500,
        ]);
        $video->puzzles()->attach($puzzle2->id, [
            'order_in_video' => 2,
            'start_seconds' => 1700,
            'solve_duration_seconds' => 1800,
        ]);

        Bookmark::factory()->create([
            'video_id' => $video->id,
            'puzzle_id' => $puzzle1->id,
            'timestamp_seconds' => 300,
            'label' => 'Break-in Step',
        ]);
        Bookmark::factory()->create([
            'video_id' => $video->id,
            'puzzle_id' => null,
            'timestamp_seconds' => 60,
            'label' => 'Introduction',
        ]);

        $this->assertCount(2, $video->hosts);
        $this->assertCount(2, $video->puzzles);
        $this->assertTrue($video->isPublic());
        $this->assertFalse($video->isPatreon());
        $this->assertSame('1:01:05', $video->formatted_duration);

        $orderedBookmarks = $video->bookmarks;
        $this->assertCount(2, $orderedBookmarks);
        $this->assertSame('Introduction', $orderedBookmarks->first()->label);
        $this->assertSame('1:00', $orderedBookmarks->first()->formatted_timestamp);

        // Verify Bookmark formatSecondsToTimecode rules (omits units that do not exist: HH when < 60m, MM when < 60s)
        $this->assertSame('0', Bookmark::formatSecondsToTimecode(0));
        $this->assertSame('45', Bookmark::formatSecondsToTimecode(45));
        $this->assertSame('1:00', Bookmark::formatSecondsToTimecode(60));
        $this->assertSame('2:05', Bookmark::formatSecondsToTimecode(125));
        $this->assertSame('11:20', Bookmark::formatSecondsToTimecode(680));
        $this->assertSame('1:01:05', Bookmark::formatSecondsToTimecode(3665));

        // Verify Bookmark parseTimecodeToSeconds rules
        $this->assertSame(0, Bookmark::parseTimecodeToSeconds('0'));
        $this->assertSame(45, Bookmark::parseTimecodeToSeconds('45'));
        $this->assertSame(45, Bookmark::parseTimecodeToSeconds('0:45'));
        $this->assertSame(60, Bookmark::parseTimecodeToSeconds('1:00'));
        $this->assertSame(125, Bookmark::parseTimecodeToSeconds('2:05'));
        $this->assertSame(680, Bookmark::parseTimecodeToSeconds('11:20'));
        $this->assertSame(3665, Bookmark::parseTimecodeToSeconds('1:01:05'));
    }

    /**
     * Test puzzle relationships with category, setters, and constraints.
     */
    public function test_puzzle_relationships_and_slug_generation(): void
    {
        $category = Category::factory()->create(['name' => 'Classic Sudoku']);
        $setter1 = Setter::factory()->create(['name' => 'Phistomefel']);
        $setter2 = Setter::factory()->create(['name' => 'Sven']);
        $constraint1 = Constraint::factory()->create(['name' => 'Thermo']);
        $constraint2 = Constraint::factory()->create(['name' => 'Arrow']);

        $puzzle = Puzzle::factory()->create([
            'category_id' => $category->id,
            'title' => 'Chessboard',
            'slug' => 'chessboard-phistomefel',
        ]);

        $puzzle->setters()->attach([$setter1->id, $setter2->id]);
        $puzzle->constraints()->attach([$constraint1->id, $constraint2->id]);

        $this->assertSame('Classic Sudoku', $puzzle->category->name);
        $this->assertCount(2, $puzzle->setters);
        $this->assertCount(2, $puzzle->constraints);

        $defaultSlug = Puzzle::generateDefaultSlug('Chessboard', 'Phistomefel');
        $this->assertSame('chessboard-phistomefel', $defaultSlug);
    }

    /**
     * Test foreign key restrictions when deleting a category with attached puzzles.
     */
    public function test_category_cannot_be_deleted_when_referenced_by_puzzles(): void
    {
        $category = Category::factory()->create();
        Puzzle::factory()->create(['category_id' => $category->id]);

        $this->expectException(QueryException::class);
        $category->delete();
    }

    /**
     * Test cascade deletion when a video is deleted.
     */
    public function test_deleting_video_cascades_bookmarks_and_pivots(): void
    {
        $video = Video::factory()->create();
        $host = Host::factory()->create();
        $puzzle = Puzzle::factory()->create();

        $video->hosts()->attach($host->id);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $bookmark = Bookmark::factory()->create([
            'video_id' => $video->id,
            'puzzle_id' => $puzzle->id,
        ]);

        $video->delete();

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('video_hosts', ['video_id' => $video->id]);
        $this->assertDatabaseMissing('video_puzzles', ['video_id' => $video->id]);
        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
        $this->assertDatabaseHas('hosts', ['id' => $host->id]);
        $this->assertDatabaseHas('puzzles', ['id' => $puzzle->id]);
    }

    /**
     * Test that deleting a puzzle nullifies puzzle_id on bookmarks and cascades pivots.
     */
    public function test_deleting_puzzle_nullifies_bookmark_puzzle_reference(): void
    {
        $video = Video::factory()->create();
        $puzzle = Puzzle::factory()->create();
        $setter = Setter::factory()->create();
        $constraint = Constraint::factory()->create();

        $puzzle->setters()->attach($setter->id);
        $puzzle->constraints()->attach($constraint->id);
        $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);

        $bookmark = Bookmark::factory()->create([
            'video_id' => $video->id,
            'puzzle_id' => $puzzle->id,
        ]);

        $puzzle->delete();

        $this->assertDatabaseMissing('puzzles', ['id' => $puzzle->id]);
        $this->assertDatabaseMissing('puzzle_setters', ['puzzle_id' => $puzzle->id]);
        $this->assertDatabaseMissing('puzzle_constraints', ['puzzle_id' => $puzzle->id]);
        $this->assertDatabaseMissing('video_puzzles', ['puzzle_id' => $puzzle->id]);

        $bookmark->refresh();
        $this->assertNull($bookmark->puzzle_id);
    }

    /**
     * Test that video stores intro and outro music.
     */
    public function test_video_stores_intro_and_outro_music(): void
    {
        $video = Video::create([
            'youtube_video_id' => 'musictest123',
            'title' => 'Video with Soundtrack Credits',
            'video_type' => 'youtube',
            'published_at' => now(),
            'intro_music' => 'Equinox (CTC Theme) - Simon Anthony',
            'outro_music' => 'Nocturne in C Minor - Chopin',
        ]);

        $this->assertEquals('Equinox (CTC Theme) - Simon Anthony', $video->intro_music);
        $this->assertEquals('Nocturne in C Minor - Chopin', $video->outro_music);
        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'intro_music' => 'Equinox (CTC Theme) - Simon Anthony',
            'outro_music' => 'Nocturne in C Minor - Chopin',
        ]);
    }

    /**
     * Test video types: YouTube, YouTube Short, and Patreon.
     */
    public function test_video_types_and_watch_urls(): void
    {
        $ytVideo = Video::factory()->create([
            'video_type' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);
        $this->assertTrue($ytVideo->isPublic());
        $this->assertTrue($ytVideo->isYouTube());
        $this->assertFalse($ytVideo->isYouTubeShort());
        $this->assertFalse($ytVideo->isPatreon());
        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $ytVideo->getWatchUrl());

        $shortVideo = Video::factory()->short()->create([
            'youtube_video_id' => 'abcdefghijk',
        ]);
        $this->assertTrue($shortVideo->isPublic());
        $this->assertFalse($shortVideo->isYouTube());
        $this->assertTrue($shortVideo->isYouTubeShort());
        $this->assertFalse($shortVideo->isPatreon());
        $this->assertEquals('https://www.youtube.com/shorts/abcdefghijk', $shortVideo->getWatchUrl());

        $patreonVideo = Video::factory()->patreon()->create([
            'patreon_post_url' => 'https://www.patreon.com/posts/12345678',
        ]);
        $this->assertFalse($patreonVideo->isPublic());
        $this->assertFalse($patreonVideo->isYouTube());
        $this->assertFalse($patreonVideo->isYouTubeShort());
        $this->assertFalse($patreonVideo->isLiveStream());
        $this->assertTrue($patreonVideo->isPatreon());
        $this->assertEquals('https://www.patreon.com/posts/12345678', $patreonVideo->getWatchUrl());

        $liveVideo = Video::factory()->liveStream()->create([
            'youtube_video_id' => 'hj-J9oRDHOU',
        ]);
        $this->assertTrue($liveVideo->isPublic());
        $this->assertFalse($liveVideo->isYouTube());
        $this->assertFalse($liveVideo->isYouTubeShort());
        $this->assertTrue($liveVideo->isLiveStream());
        $this->assertFalse($liveVideo->isPatreon());
        $this->assertEquals('https://www.youtube.com/live/hj-J9oRDHOU', $liveVideo->getWatchUrl());
    }

    /**
     * Test YouTubeService extracts video ID and detects live stream URL format.
     */
    public function test_youtube_service_extracts_live_stream_urls(): void
    {
        $id1 = YouTubeService::extractVideoId('https://www.youtube.com/live/hj-J9oRDHOU');
        $this->assertEquals('hj-J9oRDHOU', $id1);

        $id2 = YouTubeService::extractVideoId('https://www.youtube.com/live/hj-J9oRDHOU?si=abc123xyz');
        $this->assertEquals('hj-J9oRDHOU', $id2);

        $typeLive = YouTubeService::detectVideoType('https://www.youtube.com/live/hj-J9oRDHOU');
        $this->assertEquals('live_stream', $typeLive);

        $typeShort = YouTubeService::detectVideoType('https://www.youtube.com/shorts/dQw4w9WgXcQ');
        $this->assertEquals('youtube_short', $typeShort);
    }

    /**
     * Test PatreonPost model relationships with public and exclusive videos and nested puzzles.
     */
    public function test_patreon_post_relationships_and_access_levels(): void
    {
        $post = PatreonPost::create([
            'title' => 'Special Patreon Release',
            'slug' => 'special-patreon-release',
            'patreon_url' => 'https://www.patreon.com/posts/special-12345',
            'published_at' => now(),
            'notes' => 'Patreon release with public and exclusive videos.',
        ]);

        $category = Category::factory()->create();

        // 1. Exclusive Video inside post
        $exclusiveVideo = Video::factory()->create([
            'patreon_post_id' => $post->id,
            'title' => 'Exclusive Masterclass Video',
            'video_type' => 'youtube',
            'access_level' => 'exclusive',
            'youtube_video_id' => 'excl1234567',
        ]);

        $puzzle1 = Puzzle::factory()->create(['category_id' => $category->id]);
        $exclusiveVideo->puzzles()->attach($puzzle1->id, ['order_in_video' => 1]);

        // 2. Public Video inside post
        $publicVideo = Video::factory()->create([
            'patreon_post_id' => $post->id,
            'title' => 'Public Companion Video',
            'video_type' => 'youtube',
            'access_level' => 'public',
            'youtube_video_id' => 'publ1234567',
        ]);

        $puzzle2 = Puzzle::factory()->create(['category_id' => $category->id]);
        $publicVideo->puzzles()->attach($puzzle2->id, ['order_in_video' => 1]);

        // Verify relationships on PatreonPost
        $this->assertCount(2, $post->videos);
        $this->assertCount(1, $post->exclusiveVideos);
        $this->assertCount(1, $post->publicVideos);
        $this->assertEquals('Exclusive Masterclass Video', $post->exclusiveVideos->first()->title);
        $this->assertEquals('Public Companion Video', $post->publicVideos->first()->title);
        $this->assertCount(2, $post->puzzles);

        // Verify Video model relationship and helpers
        $this->assertTrue($exclusiveVideo->isExclusive());
        $this->assertFalse($exclusiveVideo->isPublic());
        $this->assertTrue($exclusiveVideo->hasPatreonPost());
        $this->assertEquals($post->id, $exclusiveVideo->patreonPost->id);
        $this->assertEquals('https://www.patreon.com/posts/special-12345', $exclusiveVideo->getWatchUrl());

        $this->assertFalse($publicVideo->isExclusive());
        $this->assertTrue($publicVideo->isPublic());
        $this->assertTrue($publicVideo->hasPatreonPost());
        $this->assertEquals('https://www.youtube.com/watch?v=publ1234567', $publicVideo->getWatchUrl());
    }

    /**
     * Test puzzle access levels and helper methods.
     */
    public function test_puzzle_access_levels_and_helpers(): void
    {
        $publicPuzzle = Puzzle::factory()->create();
        $this->assertTrue($publicPuzzle->isPublic());
        $this->assertFalse($publicPuzzle->isExclusive());
        $this->assertEquals('public', $publicPuzzle->access_level);

        $exclusivePuzzle = Puzzle::factory()->exclusive()->create();
        $this->assertFalse($exclusivePuzzle->isPublic());
        $this->assertTrue($exclusivePuzzle->isExclusive());
        $this->assertEquals('exclusive', $exclusivePuzzle->access_level);
    }

    /**
     * Test puzzle links relationship, display labels, ordering, and backward compatibility accessors.
     */
    public function test_puzzle_links_relationship_and_types(): void
    {
        $puzzle = Puzzle::factory()
            ->withSudokupad('https://sudokupad.app/classic-demo')
            ->withLink('logic_masters_germany', 'https://logic-masters.de/1234')
            ->withLink('penpa', 'https://penpa-edit.appspot.com/classic-demo')
            ->withLink('times_puzzles', 'https://thetimes.co.uk/puzzles/123')
            ->withLink('other', 'https://custom-site.com/puz', 'Custom Solver')
            ->create(['title' => 'Multi Link Puzzle']);

        $this->assertCount(5, $puzzle->links);
        $this->assertSame('https://sudokupad.app/classic-demo', $puzzle->play_url_sudokupad);
        $this->assertSame('https://penpa-edit.appspot.com/classic-demo', $puzzle->play_url_penpa);

        $labels = $puzzle->links->map(fn ($link) => $link->display_label)->toArray();
        $this->assertSame([
            'SudokuPad',
            'Logic Masters Germany',
            'Penpa',
            'Times Puzzles',
            'Custom Solver',
        ], $labels);

        // Assert cascade deletion
        $puzzleId = $puzzle->id;
        $puzzle->delete();
        $this->assertDatabaseMissing('puzzle_links', ['puzzle_id' => $puzzleId]);
    }
}
