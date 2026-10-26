<?php

namespace Tests\Feature;

use App\Models\CtcApp;
use App\Models\User;
use App\Services\AffiliateUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Feature test for the official CTC Apps showcase directory.
 *
 * Verifies public rendering of app cards, store platform links,
 * configurable affiliate token injection, and admin resource accessibility.
 */
class AppsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the public apps page displays active applications and excludes inactive ones.
     */
    public function test_apps_page_displays_active_apps_and_excludes_inactive(): void
    {
        $activeApp = CtcApp::factory()->create([
            'name' => 'Miracle Sudoku',
            'tagline' => 'Features the famous Miracle Sudoku rules.',
            'developer' => 'Studio Goya / Cracking The Cryptic',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactiveApp = CtcApp::factory()->create([
            'name' => 'Unreleased Secret App',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('apps.index'));

        $response->assertOk();
        $response->assertSee('Miracle Sudoku');
        $response->assertSee('Features the famous Miracle Sudoku rules.');
        $response->assertDontSee('Unreleased Secret App');
    }

    /**
     * Test the apps page renders all available store links for an application.
     */
    public function test_apps_page_renders_store_links_for_available_platforms(): void
    {
        $app = CtcApp::factory()->create([
            'name' => 'Chess Sudoku',
            'apple_store_url' => 'https://apps.apple.com/us/app/chess-sudoku/id1527018314',
            'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ChessSudoku',
            'steam_url' => 'https://store.steampowered.com/app/1406830/Chess_Sudoku/',
            'web_url' => null,
            'is_active' => true,
        ]);

        $response = $this->get(route('apps.index'));

        $response->assertOk();
        $response->assertSee('Chess Sudoku');
        $response->assertSee('App Store');
        $response->assertSee('Google Play');
        $response->assertSee('Steam');
        $response->assertSee('https://apps.apple.com/us/app/chess-sudoku/id1527018314', false);
        $response->assertSee('https://play.google.com/store/apps/details?id=com.StudioGoya.ChessSudoku', false);
        $response->assertSee('https://store.steampowered.com/app/1406830/Chess_Sudoku/', false);
    }

    /**
     * Test affiliate parameters are safely appended to store links when configured.
     */
    public function test_affiliate_parameters_are_injected_when_configured(): void
    {
        Config::set('affiliates.apple.pt', '998877');
        Config::set('affiliates.apple.ct', 'ctc_catalog_appstore');
        Config::set('affiliates.google.referrer', 'utm_source=ctc_web');
        Config::set('affiliates.steam.custom_params', ['curator_clanid' => '12345']);

        $app = CtcApp::factory()->create([
            'name' => 'Thermo Sudoku',
            'apple_store_url' => 'https://apps.apple.com/us/app/thermo-sudoku/id1517434114',
            'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ThermoSudoku',
            'steam_url' => 'https://store.steampowered.com/app/1344440/Thermo_Sudoku/',
            'is_active' => true,
        ]);

        $response = $this->get(route('apps.index'));

        $response->assertOk();
        // Verify Apple affiliate tokens
        $response->assertSee('pt=998877', false);
        $response->assertSee('ct=ctc_catalog_appstore', false);
        // Verify Google referrer
        $response->assertSee('referrer=utm_source%3Dctc_web', false);
        // Verify Steam custom parameter
        $response->assertSee('curator_clanid=12345', false);
    }

    /**
     * Test affiliate builder handles URLs that already possess query parameters.
     */
    public function test_affiliate_builder_preserves_existing_query_parameters(): void
    {
        Config::set('affiliates.apple.pt', '112233');

        $built = AffiliateUrlBuilder::build('apple', 'https://apps.apple.com/app/test?platform=iphone#details');

        $this->assertStringContainsString('platform=iphone', $built);
        $this->assertStringContainsString('pt=112233', $built);
        $this->assertStringEndsWith('#details', $built);
    }

    /**
     * Test navigation bar displays the Apps link.
     */
    public function test_navigation_bar_contains_apps_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('apps.index'));
        $response->assertSee('Apps');
    }

    /**
     * Test authenticated admin can access the CtcApp Filament resource.
     */
    public function test_authenticated_admin_can_access_filament_resource(): void
    {
        $admin = User::factory()->create();
        $app = CtcApp::factory()->create(['slug' => 'test-app']);

        $responseIndex = $this->actingAs($admin)->get('/admin/ctc-apps');
        $responseIndex->assertOk();

        $responseCreate = $this->actingAs($admin)->get('/admin/ctc-apps/create');
        $responseCreate->assertOk();

        $responseEdit = $this->actingAs($admin)->get('/admin/ctc-apps/test-app/edit');
        $responseEdit->assertOk();
    }

    /**
     * Test the affiliate disclaimer mentions defraying costs of hosting and maintaining.
     */
    public function test_affiliate_notice_mentions_defraying_hosting_and_maintenance(): void
    {
        $response = $this->get(route('apps.index'));

        $response->assertOk();
        $response->assertSee('Supporting This Website');
        $response->assertSee('defray the costs of hosting, development, and ongoing maintenance');
    }

    /**
     * Test authenticated admins see direct management and edit links on the public apps page.
     */
    public function test_authenticated_admin_sees_quick_edit_links_on_apps_page(): void
    {
        $admin = User::factory()->create();
        $app = CtcApp::factory()->create(['name' => 'Demo App', 'slug' => 'demo-app']);

        // As a guest, administrative controls should be hidden
        $guestResponse = $this->get(route('apps.index'));
        $guestResponse->assertOk();
        $guestResponse->assertDontSee('Add New App');
        $guestResponse->assertDontSee('Manage in Admin');
        $guestResponse->assertDontSee('Edit in Admin');

        // As an authenticated user, administrative controls should be rendered
        $adminResponse = $this->actingAs($admin)->get(route('apps.index'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Add New App');
        $adminResponse->assertSee('Manage in Admin');
        $adminResponse->assertSee('Edit in Admin');
        $adminResponse->assertSee(route('filament.admin.resources.ctc-apps.edit', ['record' => $app]));
    }

    /**
     * Test that the admin dashboard does not render the cluttering apps management widget.
     */
    public function test_admin_dashboard_does_not_render_apps_management_widget(): void
    {
        $admin = User::factory()->create();
        CtcApp::factory()->create(['name' => 'Widget Test App']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Cracking the Cryptic Apps Management');
        $response->assertDontSee('Manage All Apps');
    }

    /**
     * Test that the apps index page renders formatted markdown descriptions.
     */
    public function test_apps_page_renders_markdown_description(): void
    {
        CtcApp::factory()->create([
            'name' => 'Markdown Puzzle App',
            'is_active' => true,
            'description' => "Features include:\n\n* **100+ handcrafted puzzles**\n* Hint system",
        ]);

        $response = $this->get(route('apps.index'));
        $response->assertOk();

        $response->assertSee('ctc-markdown-content', false);
        $response->assertSee('<strong>100+ handcrafted puzzles</strong>', false);
        $response->assertSee('<li>', false);
    }
}
