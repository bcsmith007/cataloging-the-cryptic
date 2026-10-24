<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use App\Services\AffiliateUrlBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Model representing an official application released by Cracking the Cryptic and partner studios.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property string|null $description
 * @property string $developer
 * @property string|null $thumbnail_url
 * @property string|null $apple_store_url
 * @property string|null $google_play_url
 * @property string|null $steam_url
 * @property string|null $web_url
 * @property array<int, array<string, string>>|null $extra_links
 * @property string|null $badge
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $created_by_id
 * @property int|null $updated_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CtcApp extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ctc_apps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'developer',
        'thumbnail_url',
        'apple_store_url',
        'google_play_url',
        'steam_url',
        'web_url',
        'extra_links',
        'badge',
        'sort_order',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_links' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (CtcApp $app): void {
            $source = filled($app->slug) ? $app->slug : ($app->name ?? '');
            if (filled($source)) {
                $app->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * Generate a unique slug for an application.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug(Str::slug($name), $ignoreId, 'ctc_app');
    }

    /**
     * Scope a query to only include active applications.
     *
     * @param  Builder<CtcApp>  $query
     * @return Builder<CtcApp>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the Apple App Store URL enriched with affiliate parameters.
     */
    public function getAppleStoreUrlWithAffiliateAttribute(): ?string
    {
        return AffiliateUrlBuilder::build('apple', $this->apple_store_url);
    }

    /**
     * Get the Google Play Store URL enriched with affiliate parameters.
     */
    public function getGooglePlayUrlWithAffiliateAttribute(): ?string
    {
        return AffiliateUrlBuilder::build('google', $this->google_play_url);
    }

    /**
     * Get the Steam Store URL enriched with affiliate parameters.
     */
    public function getSteamUrlWithAffiliateAttribute(): ?string
    {
        return AffiliateUrlBuilder::build('steam', $this->steam_url);
    }

    /**
     * Return all available store links with affiliate parameters and platform styling.
     *
     * @return array<int, array{platform: string, label: string, sublabel: string, url: string, is_primary: bool}>
     */
    public function getStoreLinksAttribute(): array
    {
        $links = [];

        if (filled($this->apple_store_url)) {
            $links[] = [
                'platform' => 'apple',
                'label' => 'App Store',
                'sublabel' => 'iOS / iPadOS / macOS',
                'url' => $this->apple_store_url_with_affiliate,
                'is_primary' => false,
            ];
        }

        if (filled($this->google_play_url)) {
            $links[] = [
                'platform' => 'google',
                'label' => 'Google Play',
                'sublabel' => 'Android',
                'url' => $this->google_play_url_with_affiliate,
                'is_primary' => false,
            ];
        }

        if (filled($this->steam_url)) {
            $links[] = [
                'platform' => 'steam',
                'label' => 'Steam',
                'sublabel' => 'PC / Mac',
                'url' => $this->steam_url_with_affiliate,
                'is_primary' => false,
            ];
        }

        if (filled($this->web_url)) {
            $links[] = [
                'platform' => 'web',
                'label' => 'Web App',
                'sublabel' => 'Browser Player',
                'url' => $this->web_url,
                'is_primary' => false,
            ];
        }

        if (! empty($this->extra_links) && is_array($this->extra_links)) {
            foreach ($this->extra_links as $extra) {
                if (! empty($extra['url']) && ! empty($extra['label'])) {
                    $links[] = [
                        'platform' => 'other',
                        'label' => $extra['label'],
                        'sublabel' => $extra['sublabel'] ?? 'Store',
                        'url' => $extra['url'],
                        'is_primary' => false,
                    ];
                }
            }
        }

        return $links;
    }
}
