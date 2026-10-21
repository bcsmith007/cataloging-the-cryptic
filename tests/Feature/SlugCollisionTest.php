<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\LinkType;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugCollisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that duplicate puzzle titles increment slug sequentially.
     */
    public function test_puzzle_slug_collision_increments_sequentially(): void
    {
        $category = Category::factory()->create();

        $puzzle1 = Puzzle::create(['title' => 'Miracle Sudoku', 'category_id' => $category->id]);
        $puzzle2 = Puzzle::create(['title' => 'Miracle Sudoku', 'category_id' => $category->id]);
        $puzzle3 = Puzzle::create(['title' => 'Miracle Sudoku', 'category_id' => $category->id]);

        $this->assertSame('miracle-sudoku', $puzzle1->slug);
        $this->assertSame('miracle-sudoku-2', $puzzle2->slug);
        $this->assertSame('miracle-sudoku-3', $puzzle3->slug);
    }

    /**
     * Test that explicit colliding slugs increment sequentially.
     */
    public function test_explicit_slug_collision_increments_sequentially(): void
    {
        $category = Category::factory()->create();

        $puzzle1 = Puzzle::create(['title' => 'Puzzle A', 'slug' => 'custom-puzzle', 'category_id' => $category->id]);
        $puzzle2 = Puzzle::create(['title' => 'Puzzle B', 'slug' => 'custom-puzzle', 'category_id' => $category->id]);
        $puzzle3 = Puzzle::create(['title' => 'Puzzle C', 'slug' => 'custom-puzzle', 'category_id' => $category->id]);

        $this->assertSame('custom-puzzle', $puzzle1->slug);
        $this->assertSame('custom-puzzle-2', $puzzle2->slug);
        $this->assertSame('custom-puzzle-3', $puzzle3->slug);
    }

    /**
     * Test that titles ending in a number increment the existing number on collision.
     */
    public function test_slug_with_existing_number_increments_on_collision(): void
    {
        $category = Category::factory()->create();

        $puzzle1 = Puzzle::create(['title' => 'Classic Sudoku 2', 'category_id' => $category->id]);
        $puzzle2 = Puzzle::create(['title' => 'Classic Sudoku 2', 'category_id' => $category->id]);

        $this->assertSame('classic-sudoku-2', $puzzle1->slug);
        $this->assertSame('classic-sudoku-3', $puzzle2->slug);
    }

    /**
     * Test video slug collisions increment sequentially.
     */
    public function test_video_slug_collision_increments_sequentially(): void
    {
        $video1 = Video::create([
            'youtube_video_id' => 'vid11111111',
            'title' => 'Cracking the Cryptic Stream',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $video2 = Video::create([
            'youtube_video_id' => 'vid22222222',
            'title' => 'Cracking the Cryptic Stream',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);
        $video3 = Video::create([
            'youtube_video_id' => 'vid33333333',
            'title' => 'Cracking the Cryptic Stream',
            'video_type' => 'youtube',
            'published_at' => now(),
        ]);

        $this->assertSame('cracking-the-cryptic-stream', $video1->slug);
        $this->assertSame('cracking-the-cryptic-stream-2', $video2->slug);
        $this->assertSame('cracking-the-cryptic-stream-3', $video3->slug);
    }

    /**
     * Test setter slug collisions increment sequentially.
     */
    public function test_setter_slug_collision_increments_sequentially(): void
    {
        $setter1 = Setter::create(['name' => 'Richard Stolk']);
        $setter2 = Setter::create(['name' => 'Richard Stolk']);
        $setter3 = Setter::create(['name' => 'Richard Stolk']);

        $this->assertSame('richard-stolk', $setter1->slug);
        $this->assertSame('richard-stolk-2', $setter2->slug);
        $this->assertSame('richard-stolk-3', $setter3->slug);
    }

    /**
     * Test category, host, constraint, link type, and patreon post collisions.
     */
    public function test_all_models_increment_slugs_on_collision(): void
    {
        $cat1 = Category::create(['name' => 'Sudoku Puzzles', 'slug' => 'sudoku']);
        $cat2 = Category::create(['name' => 'Sudoku Variants', 'slug' => 'sudoku']);
        $this->assertSame('sudoku', $cat1->slug);
        $this->assertSame('sudoku-2', $cat2->slug);

        $host1 = Host::create(['name' => 'Simon Anthony']);
        $host2 = Host::create(['name' => 'Simon Anthony']);
        $this->assertSame('simon-anthony', $host1->slug);
        $this->assertSame('simon-anthony-2', $host2->slug);

        $c1 = Constraint::create(['name' => 'German Whispers', 'slug' => 'whispers']);
        $c2 = Constraint::create(['name' => 'Dutch Whispers', 'slug' => 'whispers']);
        $this->assertSame('whispers', $c1->slug);
        $this->assertSame('whispers-2', $c2->slug);

        $lt1 = LinkType::create(['name' => 'Custom Link Service A', 'slug' => 'custom_link']);
        $lt2 = LinkType::create(['name' => 'Custom Link Service B', 'slug' => 'custom_link']);
        $this->assertSame('custom_link', $lt1->slug);
        $this->assertSame('custom_link_2', $lt2->slug);

        $p1 = PatreonPost::create(['title' => 'Exclusive Sunday Puzzle', 'patreon_url' => 'https://patreon.com/posts/1', 'published_at' => now()]);
        $p2 = PatreonPost::create(['title' => 'Exclusive Sunday Puzzle', 'patreon_url' => 'https://patreon.com/posts/2', 'published_at' => now()]);
        $this->assertSame('exclusive-sunday-puzzle', $p1->slug);
        $this->assertSame('exclusive-sunday-puzzle-2', $p2->slug);
    }

    /**
     * Test that empty or special character titles use the model fallback and increment on collision.
     */
    public function test_fallback_slug_increments_on_collision(): void
    {
        $category = Category::factory()->create();

        $puzzle1 = Puzzle::create(['title' => '???', 'category_id' => $category->id]);
        $puzzle2 = Puzzle::create(['title' => '!!!', 'category_id' => $category->id]);

        $this->assertSame('puzzle', $puzzle1->slug);
        $this->assertSame('puzzle-2', $puzzle2->slug);
    }

    /**
     * Test that incremental slug generator picks the first available number.
     */
    public function test_first_available_incremental_number_is_selected(): void
    {
        $category = Category::factory()->create();

        $puzzle1 = Puzzle::create(['title' => 'Sudoku GP', 'slug' => 'sudoku-gp', 'category_id' => $category->id]);
        $puzzle3 = Puzzle::create(['title' => 'Sudoku GP 3', 'slug' => 'sudoku-gp-3', 'category_id' => $category->id]);

        $puzzle2 = Puzzle::create(['title' => 'Sudoku GP', 'category_id' => $category->id]);

        $this->assertSame('sudoku-gp', $puzzle1->slug);
        $this->assertSame('sudoku-gp-3', $puzzle3->slug);
        $this->assertSame('sudoku-gp-2', $puzzle2->slug);

        $puzzle4 = Puzzle::create(['title' => 'Sudoku GP', 'category_id' => $category->id]);
        $this->assertSame('sudoku-gp-4', $puzzle4->slug);
    }

    /**
     * Test that ignoring current record ID preserves the record slug on update.
     */
    public function test_slug_generation_ignores_current_record_id(): void
    {
        $setter = Setter::create(['name' => 'Mark Goodliffe']);
        $this->assertSame('mark-goodliffe', $setter->slug);

        $generated = Setter::generateSlug('Mark Goodliffe', $setter->id);
        $this->assertSame('mark-goodliffe', $generated);
    }
}
