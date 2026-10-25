<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Models\Video;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for user profile timezone preference and timestamp display across the catalog.
 */
class UserProfileTimezoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can access the Filament profile page.
     */
    public function test_user_can_access_profile_page(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/Chicago',
        ]);

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertSuccessful()
            ->assertSee('Timezone');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $component = Livewire::test(EditProfile::class);
        $component->assertSuccessful();
        $this->assertSame('America/Chicago', $component->get('data.timezone'));
    }

    /**
     * Test that a user can update their timezone on their profile page.
     */
    public function test_user_can_update_timezone_in_profile(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'timezone' => 'America/New_York',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('America/New_York', $user->fresh()->timezone);
    }

    /**
     * Test that TimezoneService resolves timezones according to priority rules.
     */
    public function test_timezone_service_resolution_hierarchy(): void
    {
        // 1. Unauthenticated guest defaults to config timezone
        $this->assertSame(config('app.timezone', 'UTC'), TimezoneService::getUserTimezone());

        // 2. Cookie fallback for guest requests
        $cookieResponse = $this->withUnencryptedCookie('ctc_user_timezone', 'Europe/London')
            ->get('/');
        $cookieResponse->assertSuccessful();

        // 3. Authenticated user takes top precedence
        $user = User::factory()->create([
            'timezone' => 'Asia/Tokyo',
        ]);
        $this->assertSame('Asia/Tokyo', TimezoneService::getUserTimezone($user));

        $this->actingAs($user);
        $this->assertSame('Asia/Tokyo', TimezoneService::getUserTimezone());
    }

    /**
     * Test that public video show and index pages render timestamps converted into user timezone.
     */
    public function test_public_pages_render_timestamps_in_user_timezone(): void
    {
        // Create a video published at 01:30:00 UTC on June 1, 2026
        // In America/New_York (EDT, UTC-4), this is 21:30:00 on May 31, 2026.
        // In UTC, this is Jun 1, 2026.
        $video = Video::factory()->create([
            'published_at' => Carbon::parse('2026-06-01 01:30:00', 'UTC'),
        ]);

        // When viewed as UTC user
        $utcUser = User::factory()->create(['timezone' => 'UTC']);
        $this->actingAs($utcUser)
            ->get(route('videos.show', $video->slug))
            ->assertSuccessful()
            ->assertSee('Jun 1, 2026');

        // When viewed as New York user (date boundary shift!)
        $nyUser = User::factory()->create(['timezone' => 'America/New_York']);
        $this->actingAs($nyUser)
            ->get(route('videos.show', $video->slug))
            ->assertSuccessful()
            ->assertSee('May 31, 2026');

        // Check video index page
        $this->actingAs($nyUser)
            ->get(route('videos.index'))
            ->assertSuccessful()
            ->assertSee('May 31, 2026');

        // Check home page
        $this->actingAs($nyUser)
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSee('May 31, 2026');
    }

    /**
     * Test that admin user management allows administrators to set and update timezones for users.
     */
    public function test_admin_can_manage_user_timezones(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'timezone' => 'UTC',
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // 1. User listing table shows timezone
        $targetUser = User::factory()->create([
            'name' => 'Timezone Test User',
            'email' => 'tzuser@example.com',
            'timezone' => 'Europe/Paris',
        ]);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$targetUser]);

        // 2. Admin creates a new user with timezone
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Guy',
                'email' => 'newguy@example.com',
                'password' => 'Secret123!',
                'timezone' => 'Australia/Sydney',
                'is_admin' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'newguy@example.com',
            'timezone' => 'Australia/Sydney',
        ]);

        // 3. Admin edits user timezone
        Livewire::test(EditUser::class, ['record' => $targetUser->getRouteKey()])
            ->fillForm([
                'timezone' => 'America/Denver',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('America/Denver', $targetUser->fresh()->timezone);
    }

    /**
     * Test that TimezoneService options contain all regions and timezones without being capped.
     */
    public function test_timezone_options_are_grouped_and_cover_all_world_regions(): void
    {
        $options = TimezoneService::getTimezoneOptions();

        // Must contain major regions
        $this->assertArrayHasKey('UTC / Universal', $options);
        $this->assertArrayHasKey('America', $options);
        $this->assertArrayHasKey('Europe', $options);
        $this->assertArrayHasKey('Asia', $options);
        $this->assertArrayHasKey('Africa', $options);
        $this->assertArrayHasKey('Australia', $options);
        $this->assertArrayHasKey('Pacific', $options);

        // Flatten all grouped options to ensure all 400+ timezones are present
        $totalTimezones = 0;
        foreach ($options as $group) {
            $totalTimezones += count($group);
        }

        $this->assertGreaterThanOrEqual(400, $totalTimezones);
        $this->assertArrayHasKey('America/New_York', $options['America']);
        $this->assertArrayHasKey('Europe/London', $options['Europe']);
        $this->assertArrayHasKey('UTC', $options['UTC / Universal']);
    }
}
