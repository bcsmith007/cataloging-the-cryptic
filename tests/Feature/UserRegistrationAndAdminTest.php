<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature test for user registration, admin authorization, and user management.
 */
class UserRegistrationAndAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the registration page is accessible to guests.
     */
    public function test_registration_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/admin/register');

        $response->assertSuccessful();
        $response->assertSee('Register');
        $response->assertSee('Sign up');
    }

    /**
     * Test that guest users can register and are assigned non-admin status by default.
     */
    public function test_guest_user_can_register_and_defaults_to_non_admin(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Jane Puzzle Solver',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertRedirect(route('home'));

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Jane Puzzle Solver', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->isAdmin());

        // Verify status flash notice is stored in session
        $this->assertEquals(
            'Your account has been created. An administrator must grant you admin privileges before you can access the admin panel.',
            session('status')
        );
    }

    /**
     * Test that a non-admin user is denied access to the admin panel.
     */
    public function test_non_admin_user_is_denied_access_to_admin_panel(): void
    {
        $regularUser = User::factory()->regular()->create();

        // Direct GET requests to admin panel routes are forbidden
        $this->actingAs($regularUser)->get('/admin')->assertForbidden();
        $this->actingAs($regularUser)->get('/admin/users')->assertForbidden();
        $this->actingAs($regularUser)->get('/admin/videos')->assertForbidden();
    }

    /**
     * Test that an admin user can access the admin panel and the user management resource.
     */
    public function test_admin_user_can_access_admin_panel_and_users_resource(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/users')->assertSuccessful();
        $this->actingAs($admin)->get('/admin/users/create')->assertSuccessful();
    }

    /**
     * Test that an admin can promote a standard user to admin and revoke admin rights.
     */
    public function test_admin_can_promote_and_demote_user_via_table_action(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Super Admin']);
        $standardUser = User::factory()->regular()->create(['name' => 'Aspiring Moderator']);

        $this->assertFalse($standardUser->is_admin);

        // 1. Promote to admin
        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleAdmin', $standardUser);

        $this->assertTrue($standardUser->fresh()->is_admin);
        $this->assertTrue($standardUser->fresh()->isAdmin());

        // Now the newly promoted user can access the admin panel
        $this->actingAs($standardUser->fresh())->get('/admin')->assertSuccessful();

        // 2. Demote back to standard user
        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleAdmin', $standardUser);

        $this->assertFalse($standardUser->fresh()->is_admin);
        $this->actingAs($standardUser->fresh())->get('/admin')->assertForbidden();
    }

    /**
     * Test that an admin can manually create a new user with or without admin rights.
     */
    public function test_admin_can_manually_create_new_user(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Staff Member',
                'email' => 'staff@example.com',
                'password' => 'StaffPass123!',
                'is_admin' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newUser = User::where('email', 'staff@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('New Staff Member', $newUser->name);
        $this->assertTrue($newUser->is_admin);
        $this->assertTrue(Hash::check('StaffPass123!', $newUser->password));
    }

    /**
     * Test that an admin cannot revoke their own admin rights or delete themselves.
     */
    public function test_admin_cannot_toggle_or_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('toggleAdmin', $admin)
            ->assertTableActionHidden('delete', $admin);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $admin->getKey()])
            ->assertActionHidden('delete');
    }

    /**
     * Test that the login page displays the 30-day "Keep me logged in" toggle.
     */
    public function test_login_page_displays_keep_me_logged_in_toggle(): void
    {
        $response = $this->get('/admin/login');

        $response->assertSuccessful();
        $response->assertSee('Keep me logged in');
        $response->assertSee('30 days');
    }

    /**
     * Test that logging in with the remember toggle active sets a 30-day cookie.
     */
    public function test_logging_in_with_remember_toggle_sets_30_day_cookie(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin.remember@example.com',
            'password' => Hash::make('SecretPass123!'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin.remember@example.com',
                'password' => 'SecretPass123!',
                'remember' => true,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($admin->id, Auth::id());

        // Verify the remember duration is configured to 30 days (43,200 minutes)
        $guard = Auth::guard('web');
        $ref = new \ReflectionProperty($guard, 'rememberDuration');
        $ref->setAccessible(true);
        $this->assertEquals(43200, $ref->getValue($guard));

        // Verify the recaller cookie has been queued for 43,200 minutes (30 days)
        $recallerCookie = collect(Cookie::getQueuedCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNotNull($recallerCookie, 'Remember-me recaller cookie was not queued.');
        // Symfony/Laravel cookie expiresTime is a timestamp; difference should be ~30 days (43200 minutes)
        $minutesUntilExpiration = (int) round(($recallerCookie->getExpiresTime() - time()) / 60);
        $this->assertEqualsWithDelta(43200, $minutesUntilExpiration, 5);
    }

    /**
     * Test that logging in without the remember toggle does not queue a remember cookie.
     */
    public function test_logging_in_without_remember_toggle_does_not_queue_remember_cookie(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin.noremember@example.com',
            'password' => Hash::make('SecretPass123!'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin.noremember@example.com',
                'password' => 'SecretPass123!',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $recallerCookie = collect(Cookie::getQueuedCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNull($recallerCookie);
    }

    /**
     * Test that an unauthenticated request with a valid remember cookie is automatically re-authenticated.
     */
    public function test_user_with_remember_cookie_is_reauthenticated_after_session_expires(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin.reauth@example.com',
            'password' => Hash::make('SecretPass123!'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin.reauth@example.com',
                'password' => 'SecretPass123!',
                'remember' => true,
            ])
            ->call('authenticate');

        $recallerCookie = collect(Cookie::getQueuedCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNotNull($recallerCookie);

        // Clear active session and in-memory auth state to simulate expired session
        Auth::forgetGuards();
        session()->flush();
        $this->assertFalse(Auth::check());

        // Send request with recaller cookie
        $response = $this->withCookie($recallerCookie->getName(), $recallerCookie->getValue())
            ->get('/admin');

        $response->assertSuccessful();
        $this->assertTrue(Auth::check());
        $this->assertEquals($admin->id, Auth::id());
    }
}
