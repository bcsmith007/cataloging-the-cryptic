<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownGuidePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /markdown page loads successfully with 200 status and key tutorial content.
     */
    public function test_markdown_guide_page_loads_successfully(): void
    {
        $response = $this->get('/markdown');

        $response->assertStatus(200);
        $response->assertSee('Markdown Formatting Guide');
        $response->assertSee('Quick-Copy Puzzle Symbols &amp; Arrows', false);
        $response->assertSee('Interactive Live Markdown Playground');
        $response->assertSee('ctc-markdown-content');
        $response->assertSee('&rarr;', false);
        $response->assertSee('&amp;rarr;', false);
        $response->assertSee('German Whispers');
        $response->assertSee('Inline Math &amp; LaTeX Formulas', false);
        $response->assertSee('KaTeX');
        $response->assertSee('r_1c_1 + r_2c_2 = 10');

        // Trailing slash /markdown/
        $trailingResponse = $this->get('/markdown/');
        $this->assertContains($trailingResponse->getStatusCode(), [200, 301, 302]);
    }

    /**
     * Test that the footer contains a link to the markdown guide.
     */
    public function test_footer_contains_markdown_guide_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('markdown.guide'));
        $response->assertSee('Markdown Guide');
    }
}
