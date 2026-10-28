<?php

namespace Tests\Feature;

use App\Models\Setter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that security headers are bypassed in non-production environments.
     */
    public function test_security_headers_are_bypassed_in_non_production_environments(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeaderMissing('Strict-Transport-Security');
        $response->assertHeaderMissing('Referrer-Policy');
        $response->assertHeaderMissing('Permissions-Policy');
        $response->assertHeaderMissing('Content-Security-Policy');
    }

    /**
     * Test that public responses contain all required security headers in production.
     */
    public function test_public_responses_contain_required_security_headers(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/');

        $response->assertStatus(200);

        // 1. Strict-Transport-Security (HSTS)
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // 2. Referrer-Policy
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 3. Permissions-Policy
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // 4. Content-Security-Policy
        $this->assertTrue($response->headers->has('Content-Security-Policy'));

        // 5. Additional security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Test that Content-Security-Policy accommodates all front-end requirements in production.
     */
    public function test_content_security_policy_accommodates_frontend_requirements(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);

        // Scripts & styles from Vite/Tailwind builds ('self', 'unsafe-inline')
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);

        // Livewire / Alpine inline evaluation needs
        $this->assertStringContainsString("'unsafe-eval'", $csp);

        // Google Tag / Analytics scripts
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);

        // YouTube embedded iframes and thumbnails
        $this->assertStringContainsString('https://www.youtube.com', $csp);
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $csp);
        $this->assertStringContainsString('https://i.ytimg.com', $csp);
        $this->assertStringContainsString('https://img.youtube.com', $csp);

        // Font loading ('self', Google Fonts data/domains)
        $this->assertStringContainsString("font-src 'self' data: https://fonts.gstatic.com", $csp);
        $this->assertStringContainsString('https://fonts.googleapis.com', $csp);

        // jsDelivr CDN fallback
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);

        // Tailwind CSS CDN is no longer permitted
        $this->assertStringNotContainsString('https://cdn.tailwindcss.com', $csp);
    }

    /**
     * Test that the public layout uses Vite and does not load Tailwind Play CDN.
     */
    public function test_layout_uses_vite_and_removes_tailwind_play_cdn(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('https://cdn.tailwindcss.com');
    }

    /**
     * Test that admin responses also receive security headers in production.
     */
    public function test_admin_responses_contain_security_headers(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    /**
     * Test that PHP_SESSION_UPLOAD_PROGRESS payloads (CVE-2026-109619 / PHP2Shell vector) are rejected with 400.
     */
    public function test_rejects_php_session_upload_progress_requests(): void
    {
        $response = $this->get('/?PHP_SESSION_UPLOAD_PROGRESS=1');

        $response->assertStatus(400);
    }

    /**
     * Test that database-backed Markdown fields strip raw HTML script tags and unsafe links.
     */
    public function test_markdown_rendering_strips_raw_html_and_unsafe_links(): void
    {
        $setter = Setter::factory()->create([
            'name' => 'Safe Markdown Setter',
            'slug' => 'safe-markdown-setter',
            'bio' => 'Hello **World** <script>alert("xss")</script> and [bad link](javascript:alert(1))',
        ]);

        $response = $this->get(route('setters.show', $setter->slug));

        $response->assertStatus(200);
        $response->assertSee('<strong>World</strong>', false);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertDontSee('javascript:alert(1)', false);
    }
}
