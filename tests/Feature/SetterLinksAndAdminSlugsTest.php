<?php

namespace Tests\Feature;

use App\Filament\Resources\Setters\Pages\EditSetter;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\LinkType;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\SetterLink;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for Setter multiple links and slug-based edit URLs across admin resources.
 */
class SetterLinksAndAdminSlugsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the edit setter page uses the slug in its URL route.
     */
    public function test_setter_edit_url_uses_slug(): void
    {
        $user = User::factory()->create();
        $setter = Setter::factory()->create([
            'name' => 'Phistomefel Master',
            'slug' => 'phistomefel-master',
        ]);

        $url = route('filament.admin.resources.setters.edit', ['record' => $setter]);
        $this->assertStringContainsString('/admin/setters/phistomefel-master/edit', $url);

        $response = $this->actingAs($user)->get($url);
        $response->assertStatus(200);
        $response->assertSee('Phistomefel Master');
    }

    /**
     * Test that the edit setter page can also resolve by numeric ID for fallback compatibility.
     */
    public function test_setter_edit_page_resolves_numeric_id_fallback(): void
    {
        $user = User::factory()->create();
        $setter = Setter::factory()->create([
            'name' => 'BremSter',
            'slug' => 'bremster',
        ]);

        $response = $this->actingAs($user)->get("/admin/setters/{$setter->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('BremSter');
    }

    /**
     * Test that multiple links and link types can be added and persisted on a setter.
     */
    public function test_setter_form_allows_adding_multiple_links_with_link_types(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Clover Puzzles',
            'slug' => 'clover-puzzles',
        ]);

        // Add multiple links with different link types
        Livewire::test(EditSetter::class, ['record' => $setter->slug])
            ->fillForm([
                'name' => 'Clover Puzzles',
                'slug' => 'clover-puzzles',
                'bio' => 'Constructor of high-quality constraint and variant puzzles.',
                'links' => [
                    [
                        'link_type' => 'patreon',
                        'url' => 'https://www.patreon.com/cloverpuzzles',
                        'title' => 'Clover on Patreon',
                    ],
                    [
                        'link_type' => 'personal_website',
                        'url' => 'https://cloverpuzzles.com',
                        'title' => 'Personal Portfolio',
                    ],
                    [
                        'link_type' => 'logic_masters_germany',
                        'url' => 'https://logic-masters.de/user/clover',
                        'title' => 'LMG Profile',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('setter_links', 3);

        $this->assertDatabaseHas('setter_links', [
            'setter_id' => $setter->id,
            'link_type' => 'patreon',
            'url' => 'https://www.patreon.com/cloverpuzzles',
            'title' => 'Clover on Patreon',
        ]);

        $this->assertDatabaseHas('setter_links', [
            'setter_id' => $setter->id,
            'link_type' => 'personal_website',
            'url' => 'https://cloverpuzzles.com',
            'title' => 'Personal Portfolio',
        ]);

        $this->assertDatabaseHas('setter_links', [
            'setter_id' => $setter->id,
            'link_type' => 'logic_masters_germany',
            'url' => 'https://logic-masters.de/user/clover',
            'title' => 'LMG Profile',
        ]);

        // Verify backward compatibility accessor for patreon_url
        $setter->refresh();
        $this->assertEquals('https://www.patreon.com/cloverpuzzles', $setter->patreon_url);
    }

    /**
     * Test that setter links display on the public search results page.
     */
    public function test_setter_links_render_on_search_page(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Kite Constructor',
            'slug' => 'kite-constructor',
        ]);

        SetterLink::create([
            'setter_id' => $setter->id,
            'link_type' => 'patreon',
            'url' => 'https://www.patreon.com/kiteconstructor',
            'title' => 'Support on Patreon',
            'order_column' => 1,
        ]);

        SetterLink::create([
            'setter_id' => $setter->id,
            'link_type' => 'sudokupad',
            'url' => 'https://sudokupad.app/kite',
            'title' => 'SudokuPad Archive',
            'order_column' => 2,
        ]);

        $response = $this->get(route('search', ['q' => 'Kite Constructor', 'type' => 'setters']));
        $response->assertStatus(200);
        $response->assertSee('Kite Constructor');
        $response->assertSee('Support on Patreon');
        $response->assertSee('https://www.patreon.com/kiteconstructor');
        $response->assertSee('SudokuPad Archive');
        $response->assertSee('https://sudokupad.app/kite');
    }

    /**
     * Test that all admin resources use slugs in their edit URLs.
     */
    public function test_admin_resources_use_slugs_in_edit_urls(): void
    {
        $user = User::factory()->create();

        // 1. Puzzle
        $category = Category::factory()->create(['slug' => 'variant-sudoku']);
        $puzzle = Puzzle::factory()->create([
            'title' => 'Miracle Mystery Loop',
            'slug' => 'miracle-mystery-loop',
            'category_id' => $category->id,
        ]);
        $puzzleUrl = route('filament.admin.resources.puzzles.edit', ['record' => $puzzle]);
        $this->assertStringContainsString('/admin/puzzles/miracle-mystery-loop/edit', $puzzleUrl);
        $this->actingAs($user)->get($puzzleUrl)->assertStatus(200);

        // 2. Constraint (Variant)
        $constraint = Constraint::factory()->create([
            'name' => 'Dutch Whispers',
            'slug' => 'dutch-whispers',
        ]);
        $constraintUrl = route('filament.admin.resources.constraints.edit', ['record' => $constraint]);
        $this->assertStringContainsString('/admin/constraints/dutch-whispers/edit', $constraintUrl);
        $this->actingAs($user)->get($constraintUrl)->assertStatus(200);

        // 3. Category (Puzzle Type)
        $categoryUrl = route('filament.admin.resources.categories.edit', ['record' => $category]);
        $this->assertStringContainsString('/admin/categories/variant-sudoku/edit', $categoryUrl);
        $this->actingAs($user)->get($categoryUrl)->assertStatus(200);

        // 4. Host
        $host = Host::factory()->create([
            'name' => 'Mark Goodliffe',
            'slug' => 'mark-goodliffe',
        ]);
        $hostUrl = route('filament.admin.resources.hosts.edit', ['record' => $host]);
        $this->assertStringContainsString('/admin/hosts/mark-goodliffe/edit', $hostUrl);
        $this->actingAs($user)->get($hostUrl)->assertStatus(200);

        // 5. Video
        $video = Video::factory()->create([
            'title' => 'An Epic Diagonal Solve',
            'slug' => 'an-epic-diagonal-solve',
        ]);
        $videoUrl = route('filament.admin.resources.videos.edit', ['record' => $video]);
        $this->assertStringContainsString('/admin/videos/an-epic-diagonal-solve/edit', $videoUrl);
        $this->actingAs($user)->get($videoUrl)->assertStatus(200);

        // 6. Link Type
        $linkType = LinkType::firstOrCreate(['slug' => 'twitch'], ['name' => 'Twitch']);
        $linkTypeUrl = route('filament.admin.resources.link-types.edit', ['record' => $linkType]);
        $this->assertStringContainsString('/admin/link-types/twitch/edit', $linkTypeUrl);
        $this->actingAs($user)->get($linkTypeUrl)->assertStatus(200);

        // 7. Patreon Post
        $post = PatreonPost::factory()->create([
            'title' => 'Special Exclusive Post',
            'slug' => 'special-exclusive-post',
        ]);
        $postUrl = route('filament.admin.resources.patreon-posts.edit', ['record' => $post]);
        $this->assertStringContainsString('/admin/patreon-posts/special-exclusive-post/edit', $postUrl);
        $this->actingAs($user)->get($postUrl)->assertStatus(200);
    }

    /**
     * Test that the puzzle edit page expands the puzzle information section across the full content width.
     */
    public function test_puzzle_edit_page_renders_expanded_full_width_puzzle_information_box(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'variant-sudoku']);
        $puzzle = Puzzle::factory()->create([
            'title' => 'Wide Screen Sudoku',
            'slug' => 'wide-screen-sudoku',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('filament.admin.resources.puzzles.edit', ['record' => $puzzle]));

        $response->assertStatus(200);
        $response->assertSee('fi-width-full');
        $response->assertSee('Puzzle Information');
        $response->assertSee('--col-span-default: 1 / -1;', false);
    }
}
