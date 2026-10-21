<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test for Google Analytics tag integration.
 *
 * Verifies that the Google tag is included on public pages,
 * omitted from admin pages, and configurable via services config.
 */
class GoogleAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that public facing pages include the Google tag script and configuration.
     */
    public function test_public_facing_pages_include_google_analytics_tag(): void
    {
        $publicRoutes = [
            '/',
            '/videos',
            '/stats',
            '/search',
            '/setters',
            '/privacy',
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);

            $response->assertStatus(200);
            $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-R88SRKQ41E', false);
            $response->assertSee("gtag('config', 'G-R88SRKQ41E');", false);
        }
    }

    /**
     * Test that admin pages do not include the Google Analytics tag.
     */
    public function test_admin_pages_do_not_include_google_analytics_tag(): void
    {
        // 1. Unauthenticated admin login page
        $loginResponse = $this->get('/admin/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertDontSee('googletagmanager.com');
        $loginResponse->assertDontSee('G-R88SRKQ41E');

        // 2. Authenticated admin dashboard
        $user = User::factory()->create();
        $adminResponse = $this->actingAs($user)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('googletagmanager.com');
        $adminResponse->assertDontSee('G-R88SRKQ41E');

        // 3. Authenticated admin resource page
        $videosResponse = $this->actingAs($user)->get('/admin/videos');
        $videosResponse->assertStatus(200);
        $videosResponse->assertDontSee('googletagmanager.com');
        $videosResponse->assertDontSee('G-R88SRKQ41E');
    }

    /**
     * Test that Google Analytics is omitted when the tag ID is not configured.
     */
    public function test_google_analytics_is_omitted_when_tag_id_is_empty(): void
    {
        config(['services.google.tag_id' => null]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('googletagmanager.com');
        $response->assertDontSee('G-R88SRKQ41E');
    }
}
