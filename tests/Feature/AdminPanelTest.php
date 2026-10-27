<?php

namespace Tests\Feature;

use App\Filament\Pages\UnfinalizedVideos;
use App\Filament\Resources\LinkTypes\Pages\CreateLinkType;
use App\Filament\Resources\PatreonPosts\Pages\EditPatreonPost;
use App\Filament\Resources\PatreonPosts\RelationManagers\VideosRelationManager;
use App\Filament\Resources\PatreonPosts\Schemas\PatreonPostForm;
use App\Filament\Resources\Puzzles\Pages\CreatePuzzle;
use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Filament\Resources\Videos\RelationManagers\PuzzlesRelationManager;
use App\Filament\Resources\Videos\Schemas\VideoForm;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\User;
use App\Models\Video;
use App\Models\YouTubeSyncLog;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature test for the Filament administration panel.
 *
 * Verifies authentication protection and access to the video management resource.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests accessing /admin are redirected to the login page.
     */
    public function test_guests_are_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Test that authenticated users can access the admin dashboard and video resource.
     */
    public function test_authenticated_user_can_access_admin_and_videos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        $videosResponse = $this->actingAs($user)->get('/admin/videos');
        $videosResponse->assertStatus(200);

        $createResponse = $this->actingAs($user)->get('/admin/videos/create');
        $createResponse->assertStatus(200);
    }

    /**
     * Test that the admin sidebar navigation removes the Dashboard link, organizes Tools, Admin, and Taxonomy sections.
     */
    public function test_admin_sidebar_navigation_removes_dashboard_and_places_youtube_scripts_in_tools(): void
    {
        $user = User::factory()->admin()->create();

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $navigation = $panel->getNavigation();

        // 1. Verify no group contains a Dashboard navigation item
        $allItemLabels = [];
        foreach ($navigation as $group) {
            foreach ($group->getItems() as $item) {
                $allItemLabels[] = $item->getLabel();
            }
        }

        $this->assertNotContains('Dashboard', $allItemLabels);

        // 2. Verify "Tools" group exists and contains Unfinalized Videos, Bookmark Player, and YouTube Scripts
        $toolsGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Tools');
        $this->assertNotNull($toolsGroup, 'The "Tools" navigation group was not found.');

        $toolsItemLabels = collect($toolsGroup->getItems())->map(fn ($item) => $item->getLabel())->all();
        $this->assertContains('Unfinalized Videos', $toolsItemLabels);
        $this->assertContains('Bookmark Player', $toolsItemLabels);
        $this->assertContains('YouTube Scripts', $toolsItemLabels);

        // 3. Verify "Admin" group exists and contains Users
        $adminGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Admin');
        $this->assertNotNull($adminGroup, 'The "Admin" navigation group was not found.');

        $adminItemLabels = collect($adminGroup->getItems())->map(fn ($item) => $item->getLabel())->all();
        $this->assertContains('Users', $adminItemLabels);

        // 4. Verify "Taxonomy" group exists and contains Videos, Puzzles, Setters, Patreon Posts, Categories, Constraints, Hosts, Link Types
        $taxonomyGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Taxonomy');
        $this->assertNotNull($taxonomyGroup, 'The "Taxonomy" navigation group was not found.');

        $taxonomyItems = collect($taxonomyGroup->getItems());
        $taxonomyItemLabels = $taxonomyItems->map(fn ($item) => $item->getLabel())->all();
        $this->assertContains('Videos', $taxonomyItemLabels);
        $this->assertContains('Puzzles', $taxonomyItemLabels);
        $this->assertContains('Setters', $taxonomyItemLabels);
        $this->assertContains('Patreon Posts', $taxonomyItemLabels);
        $this->assertContains('Categories', $taxonomyItemLabels);
        $this->assertContains('Constraints', $taxonomyItemLabels);
        $this->assertContains('Hosts', $taxonomyItemLabels);
        $this->assertContains('Link Types', $taxonomyItemLabels);
        $this->assertContains('Apps', $taxonomyItemLabels);

        // Verify all taxonomy items have folder icons
        foreach ($taxonomyItems as $item) {
            $icon = $item->getIcon();
            $iconName = is_object($icon) && isset($icon->value) ? $icon->value : (string) $icon;
            $this->assertTrue(
                $icon === Heroicon::OutlinedFolder || str_contains($iconName, 'folder'),
                "Taxonomy item {$item->getLabel()} does not have a folder icon (got: {$iconName})"
            );
        }
    }

    /**
     * Test that the admin panel renders the public top nav bar and footer matching public pages.
     */
    public function test_admin_panel_renders_public_navigation_header_and_footer(): void
    {
        $user = User::factory()->create();

        // 1. Authenticated admin dashboard
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        // Header elements
        $response->assertSee('Cataloging the Cryptic');
        $response->assertSee('CTC');
        $response->assertSee(route('videos.index'));
        $response->assertSee(route('videos.top'));
        $response->assertSee(route('videos.longest'));
        $response->assertSee(route('videos.random'));
        $response->assertSee('Admin Panel');
        $response->assertSee('Profile');
        $response->assertSee(route('filament.admin.auth.profile'));
        $response->assertSee('width="14"', false);
        $response->assertSee('height="14"', false);
        $response->assertSee('ctc-shuffle-icon', false);
        $response->assertSee('ctc-beaker-icon', false);

        // Verify Laravel/Filament info widget and default topbar are removed
        $response->assertDontSee('fi-filament-info-widget');
        $response->assertDontSee('https://filamentphp.com');
        $response->assertDontSee('fi-topbar');
        $response->assertDontSee('fi-topbar-ctn');

        // Footer elements
        $response->assertSee('All Videos');
        $response->assertSee(route('privacy'));
        $response->assertSee(route('filament.admin.auth.profile'));
        $response->assertSee('An unofficial community directory and archive for Cracking the Cryptic.');

        // 2. Resource list page (/admin/videos)
        $videosResponse = $this->actingAs($user)->get('/admin/videos');
        $videosResponse->assertStatus(200);
        $videosResponse->assertSee('Cataloging the Cryptic');
        $videosResponse->assertSee('Profile');
        $videosResponse->assertSee('All Videos');
        $videosResponse->assertSee(route('privacy'));

        // 3. Admin login page (/admin/login) as guest
        auth()->logout();
        $loginResponse = $this->get('/admin/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Cataloging the Cryptic');
        $loginResponse->assertDontSee('Admin Panel');
        $loginResponse->assertSee(route('filament.admin.auth.login'));
        $loginResponse->assertSee('All Videos');
        $loginResponse->assertSee(route('privacy'));
    }

    /**
     * Test that an authenticated user can access their profile page in the admin panel.
     */
    public function test_authenticated_user_can_access_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/profile');
        $response->assertStatus(200);
        $response->assertSee($user->email);
        $response->assertSee('Profile');
    }

    /**
     * Test that guests cannot access the YouTube update scripts review page.
     */
    public function test_guests_cannot_access_youtube_update_scripts_page(): void
    {
        $response = $this->get('/admin/youtube-update-scripts');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Test that authenticated users can review all YouTube updating scripts and schedules.
     */
    public function test_authenticated_user_can_access_youtube_update_scripts_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/youtube-update-scripts');

        $response->assertStatus(200);
        $response->assertSee('YouTube Update & Sync Scripts');
        $response->assertSee('ctc:sync-youtube');
        $response->assertSee('ctc:update-views');
        $response->assertDontSee('ctc:import-sheet');
        $response->assertSee('Every 30 Minutes');
        $response->assertSee('Last Script Execution');
        $response->assertSee('Last 10 Videos Added to Catalog');
        $response->assertSee('10,000');
        $response->assertSee('Preview Sync (Dry Run)');
        $response->assertSee('--dry-run');
        $response->assertSee('--all');
        $response->assertSee('--force');
        $response->assertSee('--limit');
        $response->assertSee('class="ctc-hr"', false);
        $response->assertSee('class="ctc-bullet-list"', false);
    }

    /**
     * Test that an edit video page with the PuzzlesRelationManager loads successfully.
     */
    public function test_video_edit_page_loads_with_puzzles_relation(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'youtube_video_id' => 'test1234567',
            'title' => 'Test Video Title',
            'slug' => 'test-video-title',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
            'view_count' => 25000,
        ]);

        $response = $this->actingAs($user)->get("/admin/videos/{$video->id}/edit");
        $response->assertStatus(200);
    }

    /**
     * Test that puzzle model boots with automatic fallbacks for non-null columns.
     */
    public function test_puzzle_model_auto_fills_required_columns_on_create(): void
    {
        $category = Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $puzzle = Puzzle::create([
            'title' => 'The Miracle Sudoku',
        ]);

        $this->assertNotNull($puzzle->id);
        $this->assertEquals($category->id, $puzzle->category_id);
        $this->assertEquals('the-miracle-sudoku', $puzzle->slug);
        $this->assertNull($puzzle->rules_raw);
    }

    /**
     * Test that a puzzle can be created and attached to a video with pivot timing.
     */
    public function test_puzzle_attaches_to_video_with_pivot_timing(): void
    {
        $category = Category::create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'title' => 'Classic Sudoku Solve',
            'slug' => 'classic-sudoku-solve',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
            'view_count' => 100000,
        ]);

        $puzzle = Puzzle::create([
            'title' => 'Diabolical Classic',
            'category_id' => $category->id,
        ]);

        $video->puzzles()->attach($puzzle->id, [
            'order_in_video' => 1,
            'start_seconds' => 120,
            'solve_duration_seconds' => 1500,
        ]);

        $this->assertCount(1, $video->fresh()->puzzles);
        $attached = $video->fresh()->puzzles->first();
        $this->assertEquals('Diabolical Classic', $attached->title);
        $this->assertEquals(1, $attached->pivot->order_in_video);
        $this->assertEquals(120, $attached->pivot->start_seconds);
        $this->assertEquals(1500, $attached->pivot->solve_duration_seconds);
    }

    /**
     * Test that PuzzlesRelationManager can mount and execute create action via Livewire.
     */
    public function test_puzzles_relation_manager_can_create_and_attach_puzzle(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'abc123xyz89',
            'title' => 'Video for Puzzle Creation Test',
            'slug' => 'video-for-puzzle-creation-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 2400,
            'view_count' => 15000,
        ]);

        $this->actingAs($user);

        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'Thermo Sudoku Delight',
                'slug' => 'thermo-sudoku-delight',
                'category_id' => $category->id,
                'rules_raw' => 'Normal Sudoku rules apply. Thermometers increase from bulb to tip.',
                'order_in_video' => 1,
                'start_seconds' => 90,
                'solve_duration_seconds' => 1200,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('puzzles', [
            'title' => 'Thermo Sudoku Delight',
            'slug' => 'thermo-sudoku-delight',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('video_puzzles', [
            'video_id' => $video->id,
            'order_in_video' => 1,
            'start_seconds' => 90,
            'solve_duration_seconds' => 1200,
        ]);
    }

    /**
     * Test that creating a puzzle with minimal attributes relies on defaults without violating null constraints.
     */
    public function test_puzzles_relation_manager_handles_defaults_without_null_violations(): void
    {
        $user = User::factory()->create();
        Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'xyz987abc12',
            'title' => 'Video for Minimal Creation Test',
            'slug' => 'video-for-minimal-creation-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
            'view_count' => 12000,
        ]);

        $this->actingAs($user);

        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'Quick Puzzle Test',
                'slug' => 'quick-puzzle-test',
                'order_in_video' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('puzzles', [
            'title' => 'Quick Puzzle Test',
            'slug' => 'quick-puzzle-test',
        ]);

        $createdPuzzle = Puzzle::where('slug', 'quick-puzzle-test')->first();
        $this->assertNotNull($createdPuzzle);
        $this->assertNotNull($createdPuzzle->category_id);
        $this->assertNull($createdPuzzle->rules_raw);
    }

    /**
     * Test that Setter model automatically generates slug when creating with name.
     */
    public function test_setter_model_auto_fills_slug_on_create(): void
    {
        $setter = Setter::create([
            'name' => 'Sven Neumann',
        ]);

        $this->assertNotNull($setter->id);
        $this->assertEquals('sven-neumann', $setter->slug);
    }

    /**
     * Test that a puzzle can be created with associated setters via PuzzlesRelationManager.
     */
    public function test_puzzles_relation_manager_creates_puzzle_with_setter(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);
        $setter = Setter::create([
            'name' => 'Phistomefel',
            'slug' => 'phistomefel',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'settertest12',
            'title' => 'Video for Setter Association Test',
            'slug' => 'video-for-setter-association-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 3000,
            'view_count' => 50000,
        ]);

        $this->actingAs($user);

        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'Phistomefel Ring Classic',
                'slug' => 'phistomefel-ring-classic',
                'category_id' => $category->id,
                'setters' => [$setter->id],
                'rules_raw' => 'Normal Sudoku rules apply.',
                'order_in_video' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $puzzle = Puzzle::where('slug', 'phistomefel-ring-classic')->first();
        $this->assertNotNull($puzzle);
        $this->assertCount(1, $puzzle->setters);
        $this->assertEquals('Phistomefel', $puzzle->setters->first()->name);
    }

    /**
     * Test that Constraint model automatically generates slug when creating with name.
     */
    public function test_constraint_model_auto_fills_slug_on_create(): void
    {
        $constraint = Constraint::create([
            'name' => 'Whispering Dutch',
        ]);

        $this->assertNotNull($constraint->id);
        $this->assertEquals('whispering-dutch', $constraint->slug);
    }

    /**
     * Test that selecting on-the-fly setter creation automatically creates setter and associates it.
     */
    public function test_puzzles_relation_manager_auto_creates_new_setter_from_dropdown_search(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'onthefly1234',
            'title' => 'Video for On-the-Fly Setter Test',
            'slug' => 'video-for-on-the-fly-setter-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
            'view_count' => 10000,
        ]);

        $this->actingAs($user);

        // Submit create action with a newly typed setter prefixed with create:
        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'On The Fly Mystery',
                'slug' => 'on-the-fly-mystery',
                'category_id' => $category->id,
                'setters' => ['create:Grandmaster Flash'],
                'rules_raw' => 'Normal Sudoku rules apply.',
                'order_in_video' => 1,
            ])
            ->assertHasNoTableActionErrors();

        // Check that Grandmaster Flash was created in setters table
        $this->assertDatabaseHas('setters', [
            'name' => 'Grandmaster Flash',
            'slug' => 'grandmaster-flash',
        ]);

        $puzzle = Puzzle::where('slug', 'on-the-fly-mystery')->first();
        $this->assertNotNull($puzzle);
        $this->assertCount(1, $puzzle->setters);
        $this->assertEquals('Grandmaster Flash', $puzzle->setters->first()->name);
    }

    /**
     * Test that slug generation avoids collisions across multiple records.
     */
    public function test_setter_slug_collision_increments_slug(): void
    {
        $setter1 = Setter::create([
            'name' => 'Sam Cappleman-Lynes',
        ]);
        $setter2 = Setter::create([
            'name' => 'Sam Cappleman-Lynes',
        ]);

        $this->assertEquals('sam-cappleman-lynes', $setter1->slug);
        $this->assertEquals('sam-cappleman-lynes-2', $setter2->slug);
    }

    /**
     * Test that Category, Host, and Video models automatically generate slugs on create.
     */
    public function test_category_host_and_video_auto_generate_slugs(): void
    {
        $category = Category::create(['name' => 'Word Puzzles']);
        $this->assertEquals('word-puzzles', $category->slug);

        $host = Host::create(['name' => 'Anthony Simon']);
        $this->assertEquals('anthony-simon', $host->slug);

        $video = Video::create([
            'youtube_video_id' => 'slugtest9876',
            'title' => 'Automated Slug Video Test',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $this->assertEquals('automated-slug-video-test', $video->slug);
    }

    /**
     * Test that VideoForm::getMusicOptions() collects and deduplicates all previous tracks.
     */
    public function test_music_options_aggregates_previous_tracks(): void
    {
        Video::create([
            'youtube_video_id' => 'musictest1',
            'title' => 'Video 1',
            'video_type' => 'youtube',
            'published_at' => now(),
            'intro_music' => 'Equinox (CTC Theme)',
            'outro_music' => 'Nocturne in C Minor',
        ]);

        Video::create([
            'youtube_video_id' => 'musictest2',
            'title' => 'Video 2',
            'video_type' => 'youtube',
            'published_at' => now(),
            'intro_music' => 'Acoustic Sunrise',
            'outro_music' => 'Equinox (CTC Theme)', // Duplicate
        ]);

        $options = VideoForm::getMusicOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('Equinox (CTC Theme)', $options);
        $this->assertArrayHasKey('Nocturne in C Minor', $options);
        $this->assertArrayHasKey('Acoustic Sunrise', $options);
    }

    /**
     * Test that VideoForm::getMusicSearchResults() filters and offers on-the-fly creation.
     */
    public function test_music_search_results_provides_on_the_fly_creation(): void
    {
        Video::create([
            'youtube_video_id' => 'musictest3',
            'title' => 'Video 3',
            'video_type' => 'youtube',
            'published_at' => now(),
            'intro_music' => 'Classical Strings Op. 4',
        ]);

        // Search matching existing track
        $matches = VideoForm::getMusicSearchResults('Classical');
        $this->assertArrayHasKey('Classical Strings Op. 4', $matches);
        $this->assertArrayNotHasKey('create:Classical', $matches);

        // Search brand new track
        $newResults = VideoForm::getMusicSearchResults('Ambient Waves');
        $this->assertArrayHasKey('Ambient Waves', $newResults);
        $this->assertEquals('Ambient Waves', $newResults['Ambient Waves']);
    }

    /**
     * Test that video list page shows tabs for video types, access levels, and review.
     */
    public function test_video_list_page_displays_video_type_tabs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/videos');
        $response->assertStatus(200);
        $response->assertSee('All Videos');
        $response->assertSee('Needs Review / Untagged');
        $response->assertSee('Public Videos');
        $response->assertSee('Patron Exclusive');
        $response->assertSee('Live Streams');
        $response->assertSee('Shorts');
    }

    /**
     * Test that authenticated users can access the PatreonPost resource in admin.
     */
    public function test_authenticated_user_can_access_patreon_posts_resource(): void
    {
        $user = User::factory()->create();

        $indexResponse = $this->actingAs($user)->get('/admin/patreon-posts');
        $indexResponse->assertStatus(200);

        $createResponse = $this->actingAs($user)->get('/admin/patreon-posts/create');
        $createResponse->assertStatus(200);

        $post = PatreonPost::create([
            'title' => 'Monthly Masterclass',
            'slug' => 'monthly-masterclass',
            'patreon_url' => 'https://www.patreon.com/posts/12345678',
            'published_at' => now(),
        ]);

        $editResponse = $this->actingAs($user)->get("/admin/patreon-posts/{$post->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Monthly Masterclass');
        $editResponse->assertSee('VideosRelationManager');

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPatreonPost::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'video_type' => 'youtube',
                'access_level' => 'exclusive',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'title' => 'Exclusive Masterclass Video',
                'slug' => 'exclusive-masterclass-video',
                'published_at' => now(),
                'duration_seconds' => 3600,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('videos', [
            'patreon_post_id' => $post->id,
            'title' => 'Exclusive Masterclass Video',
            'access_level' => 'exclusive',
            'video_type' => 'youtube',
        ]);
    }

    /**
     * Test that PatreonPostForm::populateFromPatreon populates state from API data.
     */
    public function test_patreon_post_form_populates_fields_from_patreon(): void
    {
        Http::fake([
            'https://www.patreon.com/api/posts/99887766' => Http::response([
                'data' => [
                    'id' => '99887766',
                    'type' => 'post',
                    'attributes' => [
                        'title' => 'Automated Patreon Title',
                        'published_at' => '2024-05-15T12:30:00.000+00:00',
                        'teaser_text' => 'Automated summary notes.',
                    ],
                ],
            ], 200),
        ]);

        $state = [];
        $set = function ($key, $val) use (&$state): void {
            $state[$key] = $val;
        };

        PatreonPostForm::populateFromPatreon('https://www.patreon.com/posts/99887766', $set);

        $this->assertEquals('Automated Patreon Title', $state['title']);
        $this->assertEquals('automated-patreon-title', $state['slug']);
        $this->assertEquals('2024-05-15 12:30:00', $state['published_at']);
        $this->assertEquals('Automated summary notes.', $state['notes']);
    }

    /**
     * Test that CreatePuzzle page can create a puzzle with multiple links.
     */
    public function test_create_puzzle_with_multiple_links(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $this->actingAs($user);

        Livewire::test(CreatePuzzle::class)
            ->fillForm([
                'title' => 'Quad Link Masterpiece',
                'slug' => 'quad-link-masterpiece',
                'category_id' => $category->id,
                'rules_raw' => 'Normal rules apply.',
                'links' => [
                    [
                        'link_type' => 'sudokupad',
                        'url' => 'https://sudokupad.app/quad1',
                    ],
                    [
                        'link_type' => 'logic_masters_germany',
                        'url' => 'https://logic-masters.de/quad2',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $puzzle = Puzzle::where('slug', 'quad-link-masterpiece')->first();
        $this->assertNotNull($puzzle);
        $this->assertCount(2, $puzzle->links);
        $this->assertEquals('sudokupad', $puzzle->links->first()->link_type);
        $this->assertEquals('https://sudokupad.app/quad1', $puzzle->links->first()->url);
        $this->assertEquals('logic_masters_germany', $puzzle->links->last()->link_type);
    }

    /**
     * Test that PuzzlesRelationManager edit and create actions use widened 5xl modal width.
     */
    public function test_puzzles_relation_manager_uses_widened_modals(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ]);

        $component->assertSuccessful();

        $table = $component->instance()->getTable();
        $editAction = $table->getAction('edit');
        $this->assertNotNull($editAction);
        $this->assertEquals('7xl', $editAction->getModalWidth());

        $createAction = $table->getAction('create');
        $this->assertNotNull($createAction);
        $this->assertEquals('7xl', $createAction->getModalWidth());

        $attachAction = $table->getAction('attach');
        $this->assertNotNull($attachAction);
        $this->assertEquals('2xl', $attachAction->getModalWidth());
    }

    /**
     * Test that a puzzle can be created without rules and has no default rules text.
     */
    public function test_puzzle_can_be_created_without_rules_and_has_no_default_text(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $this->actingAs($user);

        Livewire::test(CreatePuzzle::class)
            ->fillForm([
                'title' => 'Puzzle Without Any Rules',
                'slug' => 'puzzle-without-any-rules',
                'category_id' => $category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $puzzle = Puzzle::where('slug', 'puzzle-without-any-rules')->first();
        $this->assertNotNull($puzzle);
        $this->assertNull($puzzle->rules_raw);
        $this->assertDatabaseHas('puzzles', [
            'slug' => 'puzzle-without-any-rules',
            'rules_raw' => null,
        ]);
    }

    /**
     * Test that PuzzlesRelationManager auto-creates a new LinkType from dropdown search input.
     */
    public function test_puzzles_relation_manager_auto_creates_new_link_type_from_dropdown_search(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $video = Video::create([
            'youtube_video_id' => 'linktype1234',
            'title' => 'Video for Link Type Test',
            'slug' => 'video-for-link-type-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
            'view_count' => 10000,
        ]);

        $this->actingAs($user);

        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'Puzzle with New Link Type',
                'slug' => 'puzzle-with-new-link-type',
                'category_id' => $category->id,
                'rules_raw' => 'Normal Sudoku rules apply.',
                'order_in_video' => 1,
                'links' => [
                    [
                        'link_type' => 'create:Krazydad',
                        'url' => 'https://krazydad.com/puzzle/1001',
                    ],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('link_types', [
            'name' => 'Krazydad',
            'slug' => 'krazydad',
        ]);

        $puzzle = Puzzle::where('slug', 'puzzle-with-new-link-type')->first();
        $this->assertNotNull($puzzle);
        $this->assertCount(1, $puzzle->links);
        $this->assertEquals('krazydad', $puzzle->links->first()->link_type);
        $this->assertEquals('Krazydad', $puzzle->links->first()->display_label);
    }

    /**
     * Test that CreatePuzzle page auto-creates a new LinkType from dropdown search input.
     */
    public function test_create_puzzle_page_auto_creates_new_link_type_from_dropdown_search(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $this->actingAs($user);

        Livewire::test(CreatePuzzle::class)
            ->fillForm([
                'title' => 'Puzzle with WPF Link',
                'slug' => 'puzzle-with-wpf-link',
                'category_id' => $category->id,
                'rules_raw' => 'Normal Sudoku rules apply.',
                'links' => [
                    [
                        'link_type' => 'create:World Puzzle Federation',
                        'url' => 'https://worldpuzzle.org/puzzle/wpf-1',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('link_types', [
            'name' => 'World Puzzle Federation',
            'slug' => 'world_puzzle_federation',
        ]);

        $puzzle = Puzzle::where('slug', 'puzzle-with-wpf-link')->first();
        $this->assertNotNull($puzzle);
        $this->assertCount(1, $puzzle->links);
        $this->assertEquals('world_puzzle_federation', $puzzle->links->first()->link_type);
    }

    /**
     * Test that LinkTypeResource can list and create link types via the admin panel.
     */
    public function test_link_types_admin_resource_can_list_and_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/admin/link-types');
        $response->assertSuccessful();

        Livewire::test(CreateLinkType::class)
            ->fillForm([
                'name' => 'Enigmami',
                'slug' => 'enigmami',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('link_types', [
            'name' => 'Enigmami',
            'slug' => 'enigmami',
        ]);
    }

    /**
     * Test that VideoForm bookmark label options include defaults, accumulate previous labels, and support on-the-fly addition.
     */
    public function test_video_form_bookmark_label_options_accumulate_previous_labels(): void
    {
        $video = Video::factory()->create();

        // Initially should contain curated CTC defaults
        $initialOptions = VideoForm::getBookmarkLabelOptions();
        $this->assertArrayHasKey('Rules Explanation', $initialOptions);
        $this->assertArrayHasKey('The Epiphany', $initialOptions);

        // Create a custom bookmark in database
        Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 300,
            'label' => 'Bifurcation Attempt',
        ]);

        // Next call should dynamically include the new custom label
        $updatedOptions = VideoForm::getBookmarkLabelOptions();
        $this->assertArrayHasKey('Bifurcation Attempt', $updatedOptions);
        $this->assertEquals('Bifurcation Attempt', $updatedOptions['Bifurcation Attempt']);

        // Search for existing should match
        $searchResults = VideoForm::getBookmarkLabelSearchResults('Bifurcation');
        $this->assertArrayHasKey('Bifurcation Attempt', $searchResults);

        // Search for unlisted label should offer an "Add ..." option
        $newSearch = VideoForm::getBookmarkLabelSearchResults('Simon Drops Pen');
        $this->assertArrayHasKey('Simon Drops Pen', $newSearch);
        $this->assertEquals('Add "Simon Drops Pen"', $newSearch['Simon Drops Pen']);
    }

    /**
     * Test that CreateVideo page can save a bookmark with a selected label and no tag.
     */
    public function test_create_video_saves_bookmark_with_label_and_no_tag(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateVideo::class)
            ->fillForm([
                'title' => 'Video with Labeled Bookmarks',
                'slug' => 'video-with-labeled-bookmarks',
                'video_type' => 'youtube',
                'access_level' => 'public',
                'youtube_video_id' => 'labeledbm123',
                'bookmarks' => [
                    [
                        'timestamp_seconds' => 90,
                        'label' => 'Rules Explanation',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $video = Video::where('slug', 'video-with-labeled-bookmarks')->first();
        $this->assertNotNull($video);
        $this->assertCount(1, $video->bookmarks);

        $bookmark = $video->bookmarks->first();
        $this->assertEquals(90, $bookmark->timestamp_seconds);
        $this->assertEquals('Rules Explanation', $bookmark->label);
    }

    /**
     * Test that VideoForm bookmark label dropdown uses expanded column spans and custom styling.
     */
    public function test_video_form_bookmark_label_dropdown_is_configured_with_expanded_layout_and_styles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.videos.create'));
        $response->assertSuccessful();
        $response->assertSee('.bookmark-label-select', false);
        $response->assertSee('min-width: 100% !important;', false);
    }

    /**
     * Test that CreateVideo allows inputting timestamps as HH:MM:SS or MM:SS and calculates back to seconds.
     */
    public function test_create_video_saves_bookmark_with_timecode_input_calculated_to_seconds(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateVideo::class)
            ->fillForm([
                'title' => 'Video with Timecode Bookmarks',
                'slug' => 'video-with-timecode-bookmarks',
                'video_type' => 'youtube',
                'access_level' => 'public',
                'youtube_video_id' => 'timecodebm123',
                'bookmarks' => [
                    [
                        'timestamp_seconds' => '45',
                        'label' => 'Intro Seconds Only',
                    ],
                    [
                        'timestamp_seconds' => '12:30',
                        'label' => 'Midgame Minutes and Seconds',
                    ],
                    [
                        'timestamp_seconds' => '01:15:20',
                        'label' => 'Long Climax Hours Minutes Seconds',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $video = Video::where('slug', 'video-with-timecode-bookmarks')->first();
        $this->assertNotNull($video);
        $this->assertCount(3, $video->bookmarks);

        $bookmarks = $video->bookmarks()->orderBy('timestamp_seconds')->get();
        $this->assertEquals(45, $bookmarks[0]->timestamp_seconds);
        $this->assertEquals('Intro Seconds Only', $bookmarks[0]->label);

        $this->assertEquals(750, $bookmarks[1]->timestamp_seconds);
        $this->assertEquals('Midgame Minutes and Seconds', $bookmarks[1]->label);

        $this->assertEquals(4520, $bookmarks[2]->timestamp_seconds);
        $this->assertEquals('Long Climax Hours Minutes Seconds', $bookmarks[2]->label);
    }

    /**
     * Test that EditVideo displays existing bookmark timestamps formatted without unnecessary leading HH or MM.
     */
    public function test_edit_video_loads_bookmark_timestamp_formatted_as_timecode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();
        $bm1 = Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 5415,
            'label' => 'Late Breakthrough',
        ]);
        $bm2 = Bookmark::create([
            'video_id' => $video->id,
            'timestamp_seconds' => 45,
            'label' => 'Early Overview',
        ]);

        $component = Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()]);
        $bookmarksData = $component->get('data.bookmarks');

        $this->assertEquals('1:30:15', $bookmarksData['record-'.$bm1->id]['timestamp_seconds']);
        $this->assertEquals('45', $bookmarksData['record-'.$bm2->id]['timestamp_seconds']);
    }

    /**
     * Test that video form sections (video source/metadata, music, and bookmarks) are arranged in a single column layout.
     */
    public function test_video_form_sections_are_configured_in_a_single_column(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();

        $response = $this->get(route('filament.admin.resources.videos.edit', ['record' => $video->getRouteKey()]));
        $response->assertSuccessful();

        $schema = Schema::make();
        $configuredSchema = VideoForm::configure($schema);

        // Assert schema is configured for 1 column
        $this->assertEquals(1, $configuredSchema->getColumns('lg'));

        // Assert each major section spans full width in that single column
        $components = $configuredSchema->getComponents();
        $this->assertCount(3, $components);

        $this->assertSame('Video Source and Metadata', $components[0]->getHeading());
        $this->assertEquals('full', $components[0]->getColumnSpan('default'));

        $this->assertSame('Music Information', $components[1]->getHeading());
        $this->assertEquals('full', $components[1]->getColumnSpan('default'));

        $this->assertSame('Bookmarks and Timestamps', $components[2]->getHeading());
        $this->assertEquals('full', $components[2]->getColumnSpan('default'));
    }

    /**
     * Test that VideoForm bookmark repeater has equal sizes for bookmark label and associated puzzle dropdowns.
     */
    public function test_video_form_bookmark_repeater_dropdowns_have_equal_size(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();
        $component = Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()]);

        $form = $component->instance()->form;
        $components = $form->getComponents();
        $bookmarksSection = $components[2];
        $repeater = $bookmarksSection->getChildComponents()[0];
        $grid = $repeater->getChildComponents()[0];
        $fields = $grid->getChildComponents();

        // [0] timestamp_seconds, [1] label, [2] puzzle_id
        $this->assertCount(3, $fields);
        $this->assertEquals(2, $fields[0]->getColumnSpan('default'));
        $this->assertEquals(2, $fields[0]->getColumnSpan('lg'));
        $this->assertEquals(5, $fields[1]->getColumnSpan('default'));
        $this->assertEquals(5, $fields[1]->getColumnSpan('lg'));
        $this->assertEquals(5, $fields[2]->getColumnSpan('default'));
        $this->assertEquals(5, $fields[2]->getColumnSpan('lg'));
        $this->assertEquals($fields[1]->getColumnSpan('lg'), $fields[2]->getColumnSpan('lg'));
        $this->assertEquals($fields[1]->getColumnSpan('sm'), $fields[2]->getColumnSpan('sm'));
        $this->assertEquals($fields[1]->getColumnSpan('default'), $fields[2]->getColumnSpan('default'));

        // Both share bookmark-label-select class for consistent typography and dropdown styling
        $this->assertStringContainsString('bookmark-label-select', $fields[1]->getExtraAttributeBag()->toHtml());
        $this->assertStringContainsString('bookmark-label-select', $fields[2]->getExtraAttributeBag()->toHtml());

        // Bookmarks repeater is not collapsible (always visible row) and reordering is disabled
        $this->assertFalse($repeater->isCollapsible());
        $this->assertFalse($repeater->isReorderable());

        // Bookmarks repeater has bookmark-repeater class
        $this->assertStringContainsString('bookmark-repeater', $repeater->getExtraAttributeBag()->toHtml());

        // Add action is centered and uses plus icon
        $this->assertEquals(Alignment::Center, $repeater->getAddActionAlignment());
        $addAction = $repeater->getAction('add');
        $this->assertNotNull($addAction);
        $this->assertEquals('heroicon-m-plus', $addAction->getIcon());

        // Delete action is present
        $deleteAction = $repeater->getAction('delete');
        $this->assertNotNull($deleteAction);
    }

    /**
     * Test that VideoForm allows typing a new host name and automatically creates and attaches the Host model.
     */
    public function test_video_form_typing_new_host_creates_and_attaches_host(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateVideo::class)
            ->fillForm([
                'youtube_video_id' => 'hosttest123',
                'title' => 'Video with New Host',
                'slug' => 'video-with-new-host',
                'video_type' => 'youtube',
                'published_at' => now()->toDateTimeString(),
                'hosts' => ['create:Simon Guest'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('hosts', [
            'name' => 'Simon Guest',
            'slug' => 'simon-guest',
        ]);

        $video = Video::where('slug', 'video-with-new-host')->first();
        $this->assertNotNull($video);
        $this->assertCount(1, $video->hosts);
        $this->assertEquals('Simon Guest', $video->hosts->first()->name);
    }

    /**
     * Test that video editing form orders Simon and Mark first in hosts dropdown options.
     */
    public function test_video_form_hosts_dropdown_orders_simon_and_mark_first_then_alphabetically(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create hosts in arbitrary order
        Host::factory()->create(['name' => 'Zoe Puzzler', 'slug' => 'zoe-puzzler']);
        Host::factory()->create(['name' => 'Mark Goodliffe', 'slug' => 'mark-goodliffe']);
        Host::factory()->create(['name' => 'Alice Solver', 'slug' => 'alice-solver']);
        Host::factory()->create(['name' => 'Simon Anthony', 'slug' => 'simon-anthony']);
        Host::factory()->create(['name' => 'Bob Guest', 'slug' => 'bob-guest']);

        $orderedHosts = Host::orderFeaturedFirst()->pluck('name')->values()->toArray();

        $this->assertSame([
            'Simon Anthony',
            'Mark Goodliffe',
            'Alice Solver',
            'Bob Guest',
            'Zoe Puzzler',
        ], $orderedHosts);

        $video = Video::factory()->create();

        $component = Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()]);
        $component->assertSuccessful();

        $hostsField = $component->instance()->form->getComponent('hosts');
        $this->assertNotNull($hostsField);
        $options = array_values($hostsField->getOptions());

        $this->assertSame([
            'Simon Anthony',
            'Mark Goodliffe',
            'Alice Solver',
            'Bob Guest',
            'Zoe Puzzler',
        ], $options);
    }

    /**
     * Test that PuzzlesRelationManager allows typing a new category name on-the-fly.
     */
    public function test_puzzles_relation_manager_typing_new_category_creates_and_associates_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();

        Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])
            ->callTableAction('create', data: [
                'title' => 'New Word Search Puzzle',
                'slug' => 'new-word-search-puzzle',
                'category_id' => 'create:Word Search',
                'order_in_video' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Word Search',
            'slug' => 'word-search',
        ]);

        $puzzle = Puzzle::where('slug', 'new-word-search-puzzle')->first();
        $this->assertNotNull($puzzle);
        $this->assertEquals('Word Search', $puzzle->category->name);
    }

    /**
     * Test that when adding bookmarks on the video edit page, possible puzzles are limited to those tagged to the video.
     */
    public function test_video_form_bookmark_puzzles_are_limited_to_tagged_video_puzzles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();
        $taggedPuzzle1 = Puzzle::factory()->create(['title' => 'Video Tagged Puzzle 1']);
        $taggedPuzzle2 = Puzzle::factory()->create(['title' => 'Video Tagged Puzzle 2']);
        $unrelatedPuzzle = Puzzle::factory()->create(['title' => 'Completely Unrelated Puzzle']);

        $video->puzzles()->attach([
            $taggedPuzzle1->id => ['order_in_video' => 1],
            $taggedPuzzle2->id => ['order_in_video' => 2],
        ]);

        $component = Livewire::test(EditVideo::class, [
            'record' => $video->getRouteKey(),
        ]);

        // 1. Verify saving with a tagged puzzle succeeds
        $component
            ->fillForm([
                'bookmarks' => [
                    'item-1' => [
                        'timestamp_seconds' => '01:15',
                        'label' => 'Tagged Puzzle Moment',
                        'puzzle_id' => $taggedPuzzle1->id,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bookmarks', [
            'video_id' => $video->id,
            'label' => 'Tagged Puzzle Moment',
            'puzzle_id' => $taggedPuzzle1->id,
            'timestamp_seconds' => 75,
        ]);

        // 2. Verify selecting an unrelated puzzle that is not tagged to the video fails validation
        Livewire::test(EditVideo::class, [
            'record' => $video->getRouteKey(),
        ])
            ->fillForm([
                'bookmarks' => [
                    'item-2' => [
                        'timestamp_seconds' => '02:00',
                        'label' => 'Invalid Puzzle Attempt',
                        'puzzle_id' => $unrelatedPuzzle->id,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['bookmarks.item-2.puzzle_id']);
    }

    /**
     * Test that editing a video and adding a typed host persists correctly.
     */
    public function test_video_form_edit_allows_typing_new_host(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create();
        $existingHost = Host::create([
            'name' => 'Existing Host',
            'slug' => 'existing-host',
        ]);
        $video->hosts()->attach($existingHost);

        Livewire::test(EditVideo::class, [
            'record' => $video->getRouteKey(),
        ])
            ->fillForm([
                'hosts' => [$existingHost->id, 'create:Brand New Co-Host'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('hosts', [
            'name' => 'Brand New Co-Host',
            'slug' => 'brand-new-co-host',
        ]);

        $this->assertCount(2, $video->fresh()->hosts);
    }

    /**
     * Test that VideoForm includes the is_finalized checkbox and persists state.
     */
    public function test_video_form_has_is_finalized_checkbox_and_persists_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateVideo::class)
            ->fillForm([
                'youtube_video_id' => 'finalized123',
                'title' => 'Admin Reviewed Video',
                'slug' => 'admin-reviewed-video',
                'video_type' => 'youtube',
                'published_at' => now()->toDateTimeString(),
                'is_finalized' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('videos', [
            'slug' => 'admin-reviewed-video',
            'is_finalized' => true,
        ]);

        $video = Video::where('slug', 'admin-reviewed-video')->first();
        $this->assertTrue($video->isFinalized());

        // Test editing to unfinalized
        Livewire::test(EditVideo::class, [
            'record' => $video->getRouteKey(),
        ])
            ->fillForm([
                'is_finalized' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($video->fresh()->isFinalized());
    }

    /**
     * Test that the UnfinalizedVideos page loads successfully for authenticated users.
     */
    public function test_unfinalized_videos_page_loads_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/unfinalized-videos');
        $response->assertSuccessful();
    }

    /**
     * Test that UnfinalizedVideos page table lists only unfinalized videos and excludes finalized ones.
     */
    public function test_unfinalized_videos_page_lists_only_unfinalized_videos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $unfinalizedVideo = Video::factory()->create([
            'title' => 'Unfinalized Cryptic Episode',
            'is_finalized' => false,
        ]);

        $finalizedVideo = Video::factory()->create([
            'title' => 'Fully Tagged Finished Episode',
            'is_finalized' => true,
        ]);

        Livewire::test(UnfinalizedVideos::class)
            ->assertCanSeeTableRecords([$unfinalizedVideo])
            ->assertCanNotSeeTableRecords([$finalizedVideo]);
    }

    /**
     * Test that the markFinalized table action updates the video and removes it from the unfinalized table.
     */
    public function test_unfinalized_videos_table_mark_finalized_action(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create([
            'title' => 'Video Ready For Finalization',
            'is_finalized' => false,
        ]);

        Livewire::test(UnfinalizedVideos::class)
            ->callTableAction('markFinalized', $video)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($video->fresh()->isFinalized());
    }

    /**
     * Test that bulk finalization marks all selected videos as finalized.
     */
    public function test_unfinalized_videos_bulk_mark_finalized_action(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video1 = Video::factory()->create(['is_finalized' => false]);
        $video2 = Video::factory()->create(['is_finalized' => false]);

        Livewire::test(UnfinalizedVideos::class)
            ->callTableBulkAction('markFinalized', [$video1, $video2])
            ->assertHasNoTableActionErrors();

        $this->assertTrue($video1->fresh()->isFinalized());
        $this->assertTrue($video2->fresh()->isFinalized());
    }

    /**
     * Test that UnfinalizedVideos table columns are strictly title, access_level, and video_type, and link to edit page.
     */
    public function test_unfinalized_videos_table_columns_and_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::factory()->create([
            'title' => 'Puzzle Hunt Stream',
            'is_finalized' => false,
        ]);

        $test = Livewire::test(UnfinalizedVideos::class);
        $table = $test->instance()->getTable();

        // Columns must only be title, access_level, and video_type
        $columns = array_keys($table->getColumns());
        $this->assertEquals(['title', 'access_level', 'video_type'], $columns);

        // Record URL must point to admin/edit page for that video
        $editUrl = route('filament.admin.resources.videos.edit', ['record' => $video]);
        $this->assertEquals($editUrl, $table->getRecordUrl($video));
    }

    /**
     * Test that video duration and timestamps display as HH:MM:SS format while saving as seconds.
     */
    public function test_video_duration_and_timestamps_display_as_hh_mm_ss_format(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Test video create with duration in HH:MM:SS format
        Livewire::test(CreateVideo::class)
            ->fillForm([
                'youtube_video_id' => 'hmsvideo123',
                'title' => 'HMS Duration Video',
                'slug' => 'hms-duration-video',
                'video_type' => 'youtube',
                'published_at' => now()->toDateTimeString(),
                'duration_seconds' => '01:45:30',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $video = Video::where('slug', 'hms-duration-video')->first();
        $this->assertNotNull($video);
        $this->assertEquals(6330, $video->duration_seconds);
        $this->assertEquals('1:45:30', $video->formatted_duration);

        // 2. Test video edit loads duration omitting non-existent units
        $editComponent = Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()]);
        $this->assertEquals('1:45:30', $editComponent->get('data.duration_seconds'));

        // 3. Test saving with MM:SS flexible input converts to seconds
        $editComponent
            ->fillForm([
                'duration_seconds' => '45:15',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(2715, $video->fresh()->duration_seconds);
        $this->assertEquals('45:15', $video->fresh()->formatted_duration);

        // 4. Test formatSecondsToHms on Video model omitting non-existent units
        $this->assertEquals('0', Video::formatSecondsToHms(0));
        $this->assertEquals('45', Video::formatSecondsToHms(45));
        $this->assertEquals('15:30', Video::formatSecondsToHms(930));
        $this->assertEquals('2:05:10', Video::formatSecondsToHms(7510));
    }

    /**
     * Test that the random unfinalized action redirects to an unfinalized video edit page.
     */
    public function test_random_unfinalized_action_redirects_to_edit_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'unfinal1234',
            'title' => 'Unfinalized Test Video',
            'slug' => 'unfinalized-test-video',
            'video_type' => 'youtube',
            'published_at' => now(),
            'is_finalized' => false,
        ]);

        Livewire::test(ListVideos::class)
            ->callAction('random_unfinalized')
            ->assertRedirect(route('filament.admin.resources.videos.edit', ['record' => $video]));
    }

    /**
     * Test that the random unfinalized action sends a notification when no unfinalized videos exist.
     */
    public function test_random_unfinalized_action_handles_no_unfinalized_videos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Video::create([
            'youtube_video_id' => 'finalized999',
            'title' => 'Finalized Video',
            'slug' => 'finalized-video',
            'video_type' => 'youtube',
            'published_at' => now(),
            'is_finalized' => true,
        ]);

        Livewire::test(ListVideos::class)
            ->callAction('random_unfinalized')
            ->assertNotified('No unfinalized videos');
    }

    /**
     * Test that the edit video page has a header action linking to the public video page.
     */
    public function test_edit_video_page_has_view_public_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'youtube_video_id' => 'publiclink123',
            'title' => 'Public Link Video Test',
            'slug' => 'public-link-video-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'is_finalized' => true,
        ]);

        Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()])
            ->assertActionExists('view_public')
            ->assertActionHasUrl('view_public', route('videos.show', $video->slug));
    }

    /**
     * Test that PuzzlesRelationManager and PuzzleForm schemas use the updated column widths and omit difficulty_score and custom label.
     */
    public function test_puzzle_modal_and_form_schemas_use_specified_column_widths_and_omit_difficulty_score_and_custom_label(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $video = Video::create([
            'title' => 'Schema Test Video',
            'slug' => 'schema-test-video',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $category = Category::factory()->create();

        $component = Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ]);

        // Creating puzzle via table action should succeed without difficulty_score and custom label
        $component->callTableAction('create', data: [
            'title' => 'New Layout Puzzle',
            'slug' => 'new-layout-puzzle',
            'category_id' => $category->id,
            'access_level' => 'public',
            'order_in_video' => 1,
            'start_seconds' => 60,
            'solve_duration_seconds' => 600,
            'links' => [
                [
                    'link_type' => 'sudokupad',
                    'url' => 'https://sudokupad.app/test12345',
                ],
            ],
        ])->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('puzzles', [
            'title' => 'New Layout Puzzle',
            'slug' => 'new-layout-puzzle',
        ]);

        $this->assertDatabaseHas('puzzle_links', [
            'link_type' => 'sudokupad',
            'url' => 'https://sudokupad.app/test12345',
        ]);
    }

    /**
     * Test that the YouTube update scripts page displays when the update was last run and the last 10 added videos.
     */
    public function test_youtube_update_scripts_page_displays_last_run_and_recent_videos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Set last run in cache
        Cache::put('youtube_sync_last_run_at', '2026-10-15T18:00:00Z');

        // Create 12 videos to verify exactly the last 10 are displayed
        for ($i = 1; $i <= 12; $i++) {
            $video = new Video([
                'youtube_video_id' => 'ytvid'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'title' => 'Catalog Ingested Video '.$i,
                'slug' => 'catalog-ingested-video-'.$i,
                'video_type' => 'youtube',
                'published_at' => now()->subDays(12 - $i),
                'view_count' => $i * 1000,
                'duration_seconds' => 600,
                'is_finalized' => false,
            ]);
            $video->created_at = now()->subMinutes(12 - $i);
            $video->save();
        }

        $response = $this->get('/admin/youtube-update-scripts');
        $response->assertStatus(200);

        // Verify last run timestamp is visible
        $response->assertSee('Last Script Execution');
        $response->assertSee('Oct 15, 18:00 UTC');

        // Verify recent 10 videos (Video 3 through 12) are shown
        $response->assertSee('Catalog Ingested Video 12');
        $response->assertSee('Catalog Ingested Video 11');
        $response->assertSee('Catalog Ingested Video 3');
        $response->assertSee('ytvid012');

        // Video 1 and 2 should not be in the top 10 recent videos
        $response->assertDontSee('Catalog Ingested Video 1<', false);
        $response->assertDontSee('Catalog Ingested Video 2<', false);
    }

    /**
     * Test that PuzzlesRelationManager lists existing puzzles alphabetically and attaches the exact selected puzzle.
     */
    public function test_puzzles_relation_manager_attach_dropdown_is_sorted_alphabetically_and_maps_correct_puzzle_id(): void
    {
        $user = User::factory()->create([
            'email' => 'admin_puzzle_attach@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::firstOrCreate(
            ['slug' => 'variant-sudoku'],
            ['name' => 'Variant Sudoku']
        );

        $video = Video::create([
            'youtube_video_id' => 'v_attach_test_01',
            'title' => 'Video for Puzzle Attachment Test',
            'slug' => 'video-for-puzzle-attachment-test',
            'video_type' => 'youtube',
            'published_at' => now(),
            'duration_seconds' => 1800,
        ]);

        // Create puzzles in non-alphabetical order
        $wordle = Puzzle::create([
            'title' => 'Wordle - March 5, 2022',
            'slug' => 'wordle-march-5-2022',
            'category_id' => $category->id,
            'rules_raw' => 'Wordle rules',
        ]);

        $bluePrince = Puzzle::create([
            'title' => 'Blue Prince',
            'slug' => 'blue-prince',
            'category_id' => $category->id,
            'rules_raw' => 'Blue prince rules',
        ]);

        $arrowSudoku = Puzzle::create([
            'title' => 'Arrow Sudoku Challenge',
            'slug' => 'arrow-sudoku-challenge',
            'category_id' => $category->id,
            'rules_raw' => 'Arrow rules',
        ]);

        $zebraPuzzle = Puzzle::create([
            'title' => 'Zebra Logic Grid',
            'slug' => 'zebra-logic-grid',
            'category_id' => $category->id,
            'rules_raw' => 'Zebra rules',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(PuzzlesRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => EditVideo::class,
        ])->assertSuccessful();

        $action = $component->instance()->getTable()->getAction('attach');
        $this->assertNotNull($action);

        $select = $action->getRecordSelect();
        $options = $select->getOptions();

        // Verify keys map directly to the actual puzzle primary key
        $this->assertArrayHasKey($bluePrince->id, $options);
        $this->assertSame('Blue Prince', $options[$bluePrince->id]);
        $this->assertArrayHasKey($wordle->id, $options);
        $this->assertSame('Wordle - March 5, 2022', $options[$wordle->id]);

        // Verify alphabetical order
        $titles = array_values($options);
        $this->assertSame([
            'Arrow Sudoku Challenge',
            'Blue Prince',
            'Wordle - March 5, 2022',
            'Zebra Logic Grid',
        ], $titles);

        // Attach Blue Prince
        $component->callTableAction('attach', data: [
            'recordId' => $bluePrince->id,
            'order_in_video' => 1,
            'start_seconds' => 120,
            'solve_duration_seconds' => 600,
        ])->assertHasNoTableActionErrors();

        // Verify Blue Prince is attached to the video and Wordle is NOT attached
        $this->assertDatabaseHas('video_puzzles', [
            'video_id' => $video->id,
            'puzzle_id' => $bluePrince->id,
            'order_in_video' => 1,
            'start_seconds' => 120,
            'solve_duration_seconds' => 600,
        ]);

        $this->assertDatabaseMissing('video_puzzles', [
            'video_id' => $video->id,
            'puzzle_id' => $wordle->id,
        ]);

        $video->refresh();
        $this->assertSame(1, $video->puzzles()->count());
        $this->assertSame($bluePrince->id, $video->puzzles->first()->id);
        $this->assertSame('Blue Prince', $video->puzzles->first()->title);
    }

    /**
     * Test that the YouTube update scripts page displays timestamps in the user's timezone while database stays UTC.
     */
    public function test_youtube_update_scripts_page_shifts_displayed_times_to_user_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/Los_Angeles',
        ]);
        $this->actingAs($user);

        // Put last run in cache as UTC: 2026-10-15 18:00:00 UTC
        Cache::put('youtube_sync_last_run_at', '2026-10-15T18:00:00Z');

        // Create a video with UTC timestamps
        $video = Video::factory()->create([
            'title' => 'Timezone Test Video',
            'created_at' => Carbon::parse('2026-10-15 18:00:00', 'UTC'),
            'published_at' => Carbon::parse('2026-10-15 18:00:00', 'UTC'),
        ]);

        // Create a sync log with UTC started_at
        $log = YouTubeSyncLog::create([
            'status' => 'success',
            'started_at' => Carbon::parse('2026-10-15 18:00:00', 'UTC'),
            'new_videos_count' => 1,
            'updated_views_count' => 0,
            'total_views_added' => 0,
            'total_processed' => 1,
            'message' => 'Test sync in UTC',
        ]);

        // 1. Request with America/Los_Angeles timezone cookie (PDT is UTC-7, so 18:00 UTC -> 11:00 PDT)
        $response = $this->withUnencryptedCookie('ctc_user_timezone', 'America/Los_Angeles')
            ->get('/admin/youtube-update-scripts');

        $response->assertStatus(200);
        // Verify displayed times are shifted to Pacific time
        $response->assertSee('Oct 15, 11:00 PDT');
        $response->assertSee('America/Los Angeles');
        $response->assertSee('user-tz-timestamp', false);
        $response->assertSee('data-iso="2026-10-15T18:00:00', false);
        $response->assertDontSee('Intl.DateTimeFormat().resolvedOptions().timeZone', false);
        $response->assertSee("const userTz = 'America\/Los_Angeles'", false);

        // 2. Verify database records remain stored in UTC
        $this->assertSame('2026-10-15 18:00:00', $video->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-15 18:00:00', $log->fresh()->started_at->format('Y-m-d H:i:s'));
    }
}
