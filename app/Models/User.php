<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'timezone', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Determine whether the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Check if the user has administrative privileges.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Retrieve or create the system user used for automated tasks and scheduled jobs.
     */
    public static function getSystemUser(): self
    {
        return static::firstOrCreate(
            ['email' => 'system@catalogingthecryptic.com'],
            [
                'name' => 'System',
                'password' => bcrypt(str()->random(32)),
                'is_admin' => false,
            ]
        );
    }

    /**
     * Retrieve the user named "Ben" or create one if none exists.
     */
    public static function getBenUser(): self
    {
        return static::where('name', 'Ben')->first()
            ?? static::where('email', 'benjamin.c.smith@gmail.com')->first()
            ?? static::create([
                'name' => 'Ben',
                'email' => 'benjamin.c.smith@gmail.com',
                'password' => bcrypt(str()->random(32)),
                'is_admin' => true,
            ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
