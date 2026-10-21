<?php

namespace Tests\Feature;

use App\Filament\Pages\TestVideoBookmarks;
use App\Models\Bookmark;
use App\Models\Puzzle;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TestVideoBookmarksPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest users are redirected to login when visiting the test page.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/test-video-bookmarks');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Test that the convenience route redirects to the admin test page.
     */
    public function test_convenience_test_route_redirects(): void
    {
        $response = $this->get('/test-video-bookmarks');

        $response->assertRedirect('/admin/test-video-bookmarks');
    }

    /**
     * Test that authenticated users can access the test page successfully.
     */
    public function test_authenticated_user_can_access_test_page(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'youtube_video_id' => 'testvid12345',
            'title' => 'Test Cracking Video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/test-video-bookmarks');

        $response->assertStatus(200);
        $response->assertSee('Video Bookmark Player (Test Page)');
        $response->assertSee('Test Cracking Video');
    }

    /**
     * Test that the test page can add a bookmark with timestamp and persist it.
     */
    public function test_livewire_can_add_bookmark_with_timestamp_and_save(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'youtube_video_id' => 'testvid99999',
            'title' => 'Livewire Video Test',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id)
            ->call('addBookmarkWithTimestamp', '4:15', 'Rules explanation')
            ->call('saveBookmarks');

        $this->assertDatabaseHas('bookmarks', [
            'video_id' => $video->id,
            'timestamp_seconds' => 255,
            'label' => 'Rules explanation',
        ]);
    }

    /**
     * Test that switching videos updates the YouTube ID and dispatches the player event.
     */
    public function test_switching_video_updates_youtube_id_and_dispatches_event(): void
    {
        $user = User::factory()->create();
        $video1 = Video::create([
            'youtube_video_id' => 'firstvideo11',
            'title' => 'First Video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $video2 = Video::create([
            'youtube_video_id' => 'secondvideo2',
            'title' => 'Second Video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(TestVideoBookmarks::class)
            ->set('selectedVideoId', $video2->id)
            ->assertSet('youtubeVideoId', 'secondvideo2')
            ->assertSet('videoTitle', 'Second Video')
            ->assertDispatched('video-player-change', videoId: 'secondvideo2');
    }

    /**
     * Test that only puzzles associated with the selected video are allowable in dropdown options.
     */
    public function test_dropdown_only_allows_puzzles_associated_with_the_video(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video1 = Video::create([
            'youtube_video_id' => 'puzzleVideo1',
            'title' => 'Video One With Puzzle',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $puzzle1 = Puzzle::create(['title' => 'Miracle Sudoku']);
        $video1->puzzles()->attach($puzzle1->id);

        $video2 = Video::create([
            'youtube_video_id' => 'puzzleVideo2',
            'title' => 'Video Two With Different Puzzle',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $puzzle2 = Puzzle::create(['title' => 'The Thermo Classic']);
        $video2->puzzles()->attach($puzzle2->id);

        $unrelatedPuzzle = Puzzle::create(['title' => 'Unrelated Standalone Puzzle']);

        $test = Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video1->id);

        $options1 = $test->instance()->getAssociatedPuzzleOptions();

        $this->assertArrayHasKey($puzzle1->id, $options1);
        $this->assertArrayNotHasKey($puzzle2->id, $options1);
        $this->assertArrayNotHasKey($unrelatedPuzzle->id, $options1);

        // Switch to video 2
        $test->call('selectVideo', $video2->id);
        $options2 = $test->instance()->getAssociatedPuzzleOptions();

        $this->assertArrayNotHasKey($puzzle1->id, $options2);
        $this->assertArrayHasKey($puzzle2->id, $options2);
        $this->assertArrayNotHasKey($unrelatedPuzzle->id, $options2);
    }

    /**
     * Test that saving a bookmark with an associated puzzle persists correctly.
     */
    public function test_saving_bookmark_with_associated_puzzle_persists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'puzzleVideoSave',
            'title' => 'Video For Saving Bookmark With Puzzle',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $puzzle = Puzzle::create(['title' => 'Associated Test Puzzle']);
        $video->puzzles()->attach($puzzle->id);

        Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id)
            ->call('addBookmarkWithTimestamp', '12:34', 'Start of puzzle solve', $puzzle->id)
            ->call('saveBookmarks');

        $this->assertDatabaseHas('bookmarks', [
            'video_id' => $video->id,
            'timestamp_seconds' => 754,
            'label' => 'Start of puzzle solve',
            'puzzle_id' => $puzzle->id,
        ]);
    }

    /**
     * Test that an unassociated puzzle cannot be saved for a video.
     */
    public function test_unassociated_puzzle_is_not_allowed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'puzzleVideoReject',
            'title' => 'Video Rejecting Unassociated Puzzle',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $unassociatedPuzzle = Puzzle::create(['title' => 'Wrong Video Puzzle']);

        Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id)
            ->call('addBookmarkWithTimestamp', '05:00', 'Invalid bookmark', $unassociatedPuzzle->id)
            ->call('saveBookmarks');

        $this->assertDatabaseHas('bookmarks', [
            'video_id' => $video->id,
            'timestamp_seconds' => 300,
            'label' => 'Invalid bookmark',
            'puzzle_id' => null,
        ]);
    }

    /**
     * Test that previous bookmark labels from the database load in dropdown options.
     */
    public function test_previous_bookmark_labels_load_in_dropdown_options(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'labelVid123',
            'title' => 'Video For Label Test',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 120,
            'label' => 'Unique Custom Deduction',
        ]);

        $test = Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id);

        /** @var TestVideoBookmarks $instance */
        $instance = $test->instance();
        $labelOptions = $instance->getBookmarkLabelOptions();

        $this->assertArrayHasKey('Unique Custom Deduction', $labelOptions);
        $this->assertArrayHasKey('Rules Explanation', $labelOptions);
    }

    /**
     * Test that bookmarks are prepended at the top creating a reverse chronological list.
     */
    public function test_bookmarks_are_added_in_reverse_chronological_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'reverseOrderVid',
            'title' => 'Video Reverse Order Test',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $test = Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id)
            ->call('addBookmarkWithTimestamp', '1:00', 'Earlier Event')
            ->call('addBookmarkWithTimestamp', '5:00', 'Later Event');

        $bookmarks = array_values($test->get('data.bookmarks') ?? []);

        $this->assertCount(2, $bookmarks);
        $this->assertEquals('5:00', $bookmarks[0]['timestamp_seconds']);
        $this->assertEquals('Later Event', $bookmarks[0]['label']);
        $this->assertEquals('1:00', $bookmarks[1]['timestamp_seconds']);
        $this->assertEquals('Earlier Event', $bookmarks[1]['label']);
    }

    /**
     * Test that existing bookmarks load in reverse chronological order.
     */
    public function test_existing_bookmarks_load_in_reverse_chronological_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'existingReverseVid',
            'title' => 'Video Existing Reverse Test',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 60,
            'label' => 'First at 1 minute',
        ]);
        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 300,
            'label' => 'Second at 5 minutes',
        ]);

        $test = Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id);

        $bookmarks = array_values($test->get('data.bookmarks') ?? []);

        $this->assertCount(2, $bookmarks);
        $this->assertEquals('5:00', $bookmarks[0]['timestamp_seconds']);
        $this->assertEquals('Second at 5 minutes', $bookmarks[0]['label']);
        $this->assertEquals('1:00', $bookmarks[1]['timestamp_seconds']);
        $this->assertEquals('First at 1 minute', $bookmarks[1]['label']);
    }

    /**
     * Test that the convenience route preserves the video query parameter when redirecting.
     */
    public function test_convenience_test_route_preserves_query_string(): void
    {
        $response = $this->get('/test-video-bookmarks?video=42');

        $response->assertRedirect('/admin/test-video-bookmarks?video=42');
    }

    /**
     * Test that mount loads the video and its bookmarks specified in the query parameter.
     */
    public function test_mount_loads_specific_video_from_query_parameter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video1 = Video::create([
            'youtube_video_id' => 'firstVideoId1',
            'title' => 'First Video Title',
            'video_type' => 'youtube',
            'published_at' => now()->subDay(),
        ]);
        $video2 = Video::create([
            'youtube_video_id' => 'secondVideoId2',
            'title' => 'Target Video Title',
            'video_type' => 'youtube',
            'published_at' => now()->subDays(2),
        ]);

        Bookmark::create([
            'video_id' => $video2->id,
            'timestamp_seconds' => 125,
            'label' => 'Target Bookmark',
        ]);

        $response = $this->get('/admin/test-video-bookmarks?video='.$video2->id);
        $response->assertStatus(200);
        $response->assertSee('Target Video Title');

        $test = Livewire::test(TestVideoBookmarks::class, ['video' => $video2->id]);

        $this->assertEquals($video2->id, $test->get('selectedVideoId'));
        $this->assertEquals('secondVideoId2', $test->get('youtubeVideoId'));
        $this->assertEquals('Target Video Title', $test->get('videoTitle'));

        $bookmarks = array_values($test->get('data.bookmarks') ?? []);
        $this->assertCount(1, $bookmarks);
        $this->assertEquals('2:05', $bookmarks[0]['timestamp_seconds']);
        $this->assertEquals('Target Bookmark', $bookmarks[0]['label']);
    }

    /**
     * Test that the admin video edit page contains a link to the test video bookmarks page in the bookmarks section.
     */
    public function test_admin_video_edit_page_contains_link_to_bookmark_player_test_page(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'youtube_video_id' => 'editPageTestYt1',
            'title' => 'Edit Page Video For Test Player Link',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('filament.admin.resources.videos.edit', ['record' => $video->getRouteKey()]));

        $response->assertStatus(200);
        $expectedUrl = TestVideoBookmarks::getUrl(['video' => $video->id]);
        $response->assertSee($expectedUrl);
        $response->assertSee('Test in Bookmark Player');
    }

    /**
     * Test that the test page section description does not reference the removed capture current time button.
     */
    public function test_test_page_bookmarks_section_description_does_not_mention_capture_current_time(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Video::create([
            'youtube_video_id' => 'sectionDescTest1',
            'title' => 'Section Description Test Video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $response = $this->get('/admin/test-video-bookmarks');
        $response->assertStatus(200);
        $response->assertDontSee('Capture Current Time');
        $response->assertSee('Click the (+) Add Bookmark button to capture the current video playback timestamp.');
    }

    /**
     * Test that repeater add action can capture and add a bookmark timestamp.
     */
    public function test_repeater_add_action_captures_timestamp(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'repeaterAddTest1',
            'title' => 'Repeater Add Action Video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $test = Livewire::test(TestVideoBookmarks::class)
            ->call('selectVideo', $video->id)
            ->callFormComponentAction('bookmarks', 'add', arguments: ['timestamp' => '3:45']);

        $bookmarks = array_values($test->get('data.bookmarks') ?? []);
        $this->assertNotEmpty($bookmarks);
        $this->assertEquals('3:45', $bookmarks[0]['timestamp_seconds']);
    }
}
