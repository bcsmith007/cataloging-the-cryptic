<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature test for GDPR, CCPA, and privacy compliance standards.
 *
 * Verifies privacy policy availability, local font hosting, and two-click YouTube player facades.
 */
class PrivacyComplianceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the privacy policy route renders successfully with necessary compliance disclosures.
     */
    public function test_privacy_policy_page_renders_with_compliance_disclosures(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('General Data Protection Regulation');
        $response->assertSee('GDPR');
        $response->assertSee('California Consumer Privacy Act');
        $response->assertSee('CCPA');
        $response->assertSee('Strictly Necessary Cookies');
        $response->assertSee('Two-Click Privacy Façade', false);
        $response->assertSee('Notice of Non-Sale and Non-Sharing');
        $response->assertSee('privacy@catalogingthecryptic.com');
    }

    /**
     * Test that the application layout links to the privacy policy in the site footer.
     */
    public function test_layout_footer_contains_privacy_policy_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'));
        $response->assertSee('Privacy Policy');
    }

    /**
     * Test that the application layout avoids external Google Fonts CDN links and uses local fonts.
     */
    public function test_layout_uses_self_hosted_fonts_and_avoids_google_fonts_cdn(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('https://fonts.googleapis.com');
        $response->assertDontSee('https://fonts.gstatic.com');
        $response->assertSee('/fonts/fonts.css');
    }

    /**
     * Test that the home page featured video renders a two-click privacy façade.
     */
    public function test_home_page_featured_video_uses_privacy_facade(): void
    {
        $host = Host::factory()->create(['name' => 'Simon Anthony']);
        $video = Video::factory()->create([
            'title' => 'Privacy Façade Test Video',
            'video_type' => 'youtube',
            'youtube_video_id' => 'privacy1234',
            'published_at' => now(),
            'access_level' => 'public',
        ]);
        $video->hosts()->attach($host->id);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="hero-yt-facade"', false);
        $response->assertSee('onclick="loadHeroYouTubePlayer()"', false);
        $response->assertSee('aria-label="Play Video"', false);
        $response->assertDontSee('Clicking play loads video content from YouTube (privacy-enhanced mode)');
    }

    /**
     * Test that the public video detail page renders a two-click privacy façade before playback.
     */
    public function test_video_detail_page_uses_two_click_privacy_facade(): void
    {
        $video = Video::factory()->create([
            'title' => 'Video Detail Privacy Test',
            'video_type' => 'youtube',
            'youtube_video_id' => 'detail1234',
            'published_at' => now(),
            'access_level' => 'public',
        ]);

        $response = $this->get(route('videos.show', $video->slug));

        $response->assertStatus(200);
        $response->assertSee('id="yt-facade"', false);
        $response->assertSee('onclick="loadYouTubePlayer()"', false);
        $response->assertSee('aria-label="Play Video"', false);
        $response->assertDontSee('Clicking play loads video and player resources from YouTube (privacy-enhanced mode)');
    }

    /**
     * Test that session:prune prunes expired sessions and purges sessions by IP under CCPA/GDPR erasure requests.
     */
    public function test_session_prune_removes_expired_sessions_and_supports_ip_erasure(): void
    {
        // 1. Seed sessions: active, expired, and targeted IP
        DB::table('sessions')->insert([
            [
                'id' => 'active_session',
                'user_id' => null,
                'ip_address' => '10.0.0.1',
                'user_agent' => 'TestBrowser/1.0',
                'payload' => 'payload_active',
                'last_activity' => now()->getTimestamp(),
            ],
            [
                'id' => 'expired_session',
                'user_id' => null,
                'ip_address' => '10.0.0.2',
                'user_agent' => 'TestBrowser/1.0',
                'payload' => 'payload_expired',
                'last_activity' => now()->subMinutes(240)->getTimestamp(),
            ],
            [
                'id' => 'target_ip_session',
                'user_id' => null,
                'ip_address' => '198.51.100.45',
                'user_agent' => 'TestBrowser/1.0',
                'payload' => 'payload_target',
                'last_activity' => now()->getTimestamp(),
            ],
        ]);

        // 2. Test targeted IP erasure (CCPA/GDPR erasure request)
        $this->artisan('session:prune', ['--ip' => '198.51.100.45'])
            ->expectsOutput('Deleted 1 session(s) for IP address 198.51.100.45.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('sessions', ['id' => 'target_ip_session']);
        $this->assertDatabaseHas('sessions', ['id' => 'active_session']);
        $this->assertDatabaseHas('sessions', ['id' => 'expired_session']);

        // 3. Test scheduled pruning of expired sessions
        $this->artisan('session:prune')
            ->expectsOutput('Pruned 1 expired session(s) older than 120 minutes.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('sessions', ['id' => 'expired_session']);
        $this->assertDatabaseHas('sessions', ['id' => 'active_session']);
    }
}
