<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\LinkType;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserAuditTrackingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating content records tags the authenticated user as creator and updater.
     */
    public function test_creating_records_tags_authenticated_user_as_creator_and_updater(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Word Puzzles',
        ]);

        $setter = Setter::create([
            'name' => 'Logician',
        ]);

        $constraint = Constraint::create([
            'name' => 'Thermometers',
        ]);

        $host = Host::create([
            'name' => 'Simon Anthony',
        ]);

        $linkType = LinkType::create([
            'name' => 'Custom Link Destination',
        ]);

        $patreonPost = PatreonPost::create([
            'title' => 'Exclusive Sunday Puzzle',
            'patreon_url' => 'https://patreon.com/posts/12345',
            'published_at' => now(),
        ]);

        $puzzle = Puzzle::create([
            'title' => 'Miracle Sudoku',
            'category_id' => $category->id,
        ]);

        $video = Video::create([
            'youtube_video_id' => 'abc123xyz89',
            'title' => 'The Miracle Sudoku Solve',
            'video_type' => 'regular',
            'access_level' => 'public',
            'published_at' => now(),
        ]);

        $bookmark = Bookmark::create([
            'video_id' => $video->id,
            'puzzle_id' => $puzzle->id,
            'timestamp_seconds' => 120,
            'label' => 'Start of rules',
        ]);

        $this->assertSame($user->id, $category->created_by_id);
        $this->assertSame($user->id, $category->updated_by_id);
        $this->assertTrue($category->creator->is($user));
        $this->assertTrue($category->updater->is($user));

        $this->assertSame($user->id, $setter->created_by_id);
        $this->assertSame($user->id, $setter->updated_by_id);

        $this->assertSame($user->id, $constraint->created_by_id);
        $this->assertSame($user->id, $host->created_by_id);
        $this->assertSame($user->id, $linkType->created_by_id);
        $this->assertSame($user->id, $patreonPost->created_by_id);
        $this->assertSame($user->id, $puzzle->created_by_id);
        $this->assertSame($user->id, $video->created_by_id);
        $this->assertSame($user->id, $bookmark->created_by_id);
    }

    /**
     * Test updating a record updates updated_by_id to the modifying user while preserving creator.
     */
    public function test_updating_record_updates_updated_by_id_to_current_user(): void
    {
        $creator = User::factory()->create([
            'name' => 'Alice',
            'is_admin' => true,
        ]);

        $updater = User::factory()->create([
            'name' => 'Bob',
            'is_admin' => true,
        ]);

        $this->actingAs($creator);

        $setter = Setter::create([
            'name' => 'Original Setter Name',
        ]);

        $this->assertSame($creator->id, $setter->created_by_id);
        $this->assertSame($creator->id, $setter->updated_by_id);

        $this->actingAs($updater);

        $setter->update([
            'bio' => 'New bio added by Bob',
        ]);

        $setter->refresh();

        $this->assertSame($creator->id, $setter->created_by_id);
        $this->assertSame($updater->id, $setter->updated_by_id);
        $this->assertSame('Alice', $setter->creator->name);
        $this->assertSame('Bob', $setter->updater->name);
    }

    /**
     * Test videos created by the scheduled YouTube sync command are tagged with the System user.
     */
    public function test_sync_youtube_videos_command_tags_new_videos_with_system_user(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'sysvid001'],
                        'snippet' => ['title' => 'Sync Ingested Video'],
                    ],
                ],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 1],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'sysvid001',
                        'snippet' => [
                            'title' => 'Sync Ingested Video',
                            'publishedAt' => '2026-10-22T00:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/sysvid001/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT20M00S',
                        ],
                        'statistics' => [
                            'viewCount' => '12000',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --all')
            ->assertSuccessful();

        $video = Video::where('youtube_video_id', 'sysvid001')->firstOrFail();

        $this->assertNotNull($video->created_by_id);
        $this->assertNotNull($video->updated_by_id);

        $systemUser = User::getSystemUser();
        $this->assertSame($systemUser->id, $video->created_by_id);
        $this->assertSame($systemUser->id, $video->updated_by_id);
        $this->assertSame('System', $video->creator->name);
        $this->assertSame('System', $video->updater->name);
    }

    /**
     * Test User::getBenUser returns the default user named Ben.
     */
    public function test_ben_user_helper_returns_user_named_ben(): void
    {
        $ben = User::getBenUser();

        $this->assertSame('Ben', $ben->name);
        $this->assertSame('benjamin.c.smith@gmail.com', $ben->email);
        $this->assertTrue($ben->is_admin);

        // Calling it again returns the same existing user
        $sameBen = User::getBenUser();
        $this->assertSame($ben->id, $sameBen->id);
    }

    /**
     * Test unauthenticated creation leaves creator as null without errors.
     */
    public function test_unauthenticated_creation_leaves_creator_as_null(): void
    {
        $category = Category::create([
            'name' => 'Unauthenticated Category',
        ]);

        $this->assertNull($category->created_by_id);
        $this->assertNull($category->updated_by_id);
    }
}
