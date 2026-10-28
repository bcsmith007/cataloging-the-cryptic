<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CtcApp;
use App\Models\Host;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\PuzzleLink;
use App\Models\Setter;
use App\Models\SetterLink;
use App\Models\Video;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrlSanitizerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $ytRedirect = 'https://www.youtube.com/redirect?event=video_description&redir_token=QUlxb01IWWlDTG44Uk56clpLeGZzQVY5bXp6MXxBSTVJdTV2ZDVVQjd1NW83SUdYTTIzR3BlNkN5aHVrOExJSUZzck9HRUFEXzBUZHFXWENlZHJYNllhRllGUnl0Zk9FYW1maTdCUFR3VHlMUHh6SE9KNDZiQU5GNWZ2QjFydGRM&q=https%3A%2F%2Fwww.thetimes.co.uk%2Fpuzzleclub%2Fcrosswordclub%2Fpuzzles%2Fcrossword%2F45741&v=GeSkpiATWLQ';

    protected string $cleanDestination = 'https://www.thetimes.co.uk/puzzleclub/crosswordclub/puzzles/crossword/45741';

    /**
     * Test that PuzzleLink automatically unwraps YouTube redirect URL when assigned.
     */
    public function test_puzzle_link_unwraps_youtube_redirect_url(): void
    {
        $category = Category::factory()->create();
        $puzzle = Puzzle::factory()->create(['category_id' => $category->id]);

        $link = PuzzleLink::create([
            'puzzle_id' => $puzzle->id,
            'link_type' => 'times_puzzles',
            'url' => $this->ytRedirect,
        ]);

        $this->assertSame($this->cleanDestination, $link->url);
        $this->assertDatabaseHas('puzzle_links', [
            'id' => $link->id,
            'url' => $this->cleanDestination,
        ]);

        // Also test direct property assignment
        $link->url = 'https://m.youtube.com/redirect?q=https%3A%2F%2Fsudokupad.app%2Fctc%2F999';
        $link->save();

        $this->assertSame('https://sudokupad.app/ctc/999', $link->fresh()->url);
    }

    /**
     * Test that SetterLink automatically unwraps YouTube redirect URL when assigned.
     */
    public function test_setter_link_unwraps_youtube_redirect_url(): void
    {
        $setter = Setter::factory()->create(['name' => 'Test Setter']);

        $link = SetterLink::create([
            'setter_id' => $setter->id,
            'link_type' => 'personal_website',
            'url' => $this->ytRedirect,
        ]);

        $this->assertSame($this->cleanDestination, $link->url);
        $this->assertDatabaseHas('setter_links', [
            'id' => $link->id,
            'url' => $this->cleanDestination,
        ]);
    }

    /**
     * Test that Setter patreon_url automatically unwraps YouTube redirect URL.
     */
    public function test_setter_patreon_url_unwraps_youtube_redirect_url(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Patreon Setter',
            'patreon_url' => $this->ytRedirect,
        ]);

        $this->assertSame($this->cleanDestination, $setter->fresh()->patreon_url);
    }

    /**
     * Test that PatreonPost patreon_url automatically unwraps YouTube redirect URL.
     */
    public function test_patreon_post_unwraps_youtube_redirect_url(): void
    {
        $post = PatreonPost::create([
            'title' => 'Exclusive Patreon Puzzle',
            'patreon_url' => $this->ytRedirect,
            'published_at' => now(),
        ]);

        $this->assertSame($this->cleanDestination, $post->fresh()->patreon_url);
    }

    /**
     * Test that Video patreon_post_url automatically unwraps YouTube redirect URL.
     */
    public function test_video_patreon_post_url_unwraps_youtube_redirect_url(): void
    {
        $video = Video::factory()->create([
            'title' => 'Exclusive Video',
            'patreon_post_url' => $this->ytRedirect,
        ]);

        $this->assertSame($this->cleanDestination, $video->fresh()->patreon_post_url);
    }

    /**
     * Test that Host avatar_url automatically unwraps YouTube redirect URL.
     */
    public function test_host_avatar_url_unwraps_youtube_redirect_url(): void
    {
        $host = Host::create([
            'name' => 'Guest Host',
            'slug' => 'guest-host',
            'avatar_url' => $this->ytRedirect,
        ]);

        $this->assertSame($this->cleanDestination, $host->fresh()->avatar_url);
    }

    /**
     * Test that CtcApp store links automatically unwrap YouTube redirect URLs.
     */
    public function test_ctc_app_urls_unwrap_youtube_redirect_urls(): void
    {
        $app = CtcApp::create([
            'name' => 'Test App',
            'slug' => 'test-app',
            'web_url' => $this->ytRedirect,
            'steam_url' => 'https://youtube.com/redirect?q=https%3A%2F%2Fstore.steampowered.com%2Fapp%2F123',
        ]);

        $this->assertSame($this->cleanDestination, $app->fresh()->web_url);
        $this->assertSame('https://store.steampowered.com/app/123', $app->fresh()->steam_url);
    }

    /**
     * Test that Puzzle rules_raw unwraps embedded YouTube redirect URLs.
     */
    public function test_puzzle_rules_raw_unwraps_embedded_youtube_redirect_urls(): void
    {
        $category = Category::factory()->create();
        $puzzle = Puzzle::create([
            'title' => 'Puzzle with Raw Rules',
            'category_id' => $category->id,
            'rules_raw' => "Instructions:\nSolve at {$this->ytRedirect}\nHave fun!",
        ]);

        $this->assertSame("Instructions:\nSolve at {$this->cleanDestination}\nHave fun!", $puzzle->fresh()->rules_raw);
    }

    /**
     * Test that the Filament TextInput macro unwrapRedirects cleans on state update and dehydration.
     */
    public function test_filament_text_input_macro_unwraps_redirects(): void
    {
        $input = TextInput::make('url')->unwrapRedirects();

        // Test dehydration callback
        $dehydrateUsing = (fn () => $this->dehydrateStateUsing)->call($input);
        $this->assertNotNull($dehydrateUsing);
        $this->assertSame($this->cleanDestination, $dehydrateUsing($this->ytRedirect));

        // Test afterStateUpdated callback registration
        $afterStateUpdated = (fn () => $this->afterStateUpdated)->call($input);
        $this->assertNotEmpty($afterStateUpdated);

        // Execute afterStateUpdated callback on unmounted component (exercises safety guard)
        $afterStateUpdated[0]($input, $this->ytRedirect);
    }
}
