<?php

namespace Tests\Feature;

use App\Services\PatreonService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature test for PatreonService post ID extraction and metadata querying.
 */
class PatreonServiceTest extends TestCase
{
    /**
     * Test extraction of numeric post ID from various URL patterns.
     */
    public function test_extracts_post_id_from_urls_and_ids(): void
    {
        $this->assertEquals(
            '170516826',
            PatreonService::extractPostId('https://www.patreon.com/crackingthecryptic/posts/one-impossible-170516826')
        );

        $this->assertEquals(
            '98765432',
            PatreonService::extractPostId('https://www.patreon.com/posts/whispering-shadows-98765432')
        );

        $this->assertEquals(
            '112345678',
            PatreonService::extractPostId('https://www.patreon.com/posts/112345678?utm_source=copy-link')
        );

        $this->assertEquals(
            '54321',
            PatreonService::extractPostId('https://patreon.com/posts/54321')
        );

        $this->assertEquals(
            '998877',
            PatreonService::extractPostId('998877')
        );

        $this->assertNull(PatreonService::extractPostId('https://www.google.com'));
        $this->assertNull(PatreonService::extractPostId(''));
    }

    /**
     * Test successful retrieval and parsing of post details via Patreon public API.
     */
    public function test_fetches_and_parses_patreon_post_details(): void
    {
        Http::fake([
            'https://www.patreon.com/api/posts/170516826' => Http::response([
                'data' => [
                    'id' => '170516826',
                    'type' => 'post',
                    'attributes' => [
                        'title' => 'One Impossible Word after Another',
                        'published_at' => '2026-09-25T07:46:39.000+00:00',
                        'url' => 'https://www.patreon.com/crackingthecryptic/posts/one-impossible-170516826',
                        'teaser_text' => 'An in-depth look at geometric interactions.',
                    ],
                ],
            ], 200),
        ]);

        $service = new PatreonService;
        $details = $service->getPostDetails('https://www.patreon.com/crackingthecryptic/posts/one-impossible-170516826');

        $this->assertNotNull($details);
        $this->assertEquals('170516826', $details['post_id']);
        $this->assertEquals('One Impossible Word after Another', $details['title']);
        $this->assertEquals('2026-09-25 07:46:39', $details['published_at']);
        $this->assertEquals('https://www.patreon.com/crackingthecryptic/posts/one-impossible-170516826', $details['patreon_url']);
        $this->assertEquals('An in-depth look at geometric interactions.', $details['teaser_text']);
    }

    /**
     * Test graceful handling when Patreon API returns not found or error.
     */
    public function test_handles_patreon_api_errors_gracefully(): void
    {
        Http::fake([
            'https://www.patreon.com/api/posts/00000000' => Http::response(null, 404),
        ]);

        $service = new PatreonService;
        $details = $service->getPostDetails('00000000');

        $this->assertNull($details);
    }
}
