<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use App\Services\UrlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Host model representing video presenters and solvers.
 *
 * Each host features in one or more videos on the channel.
 */
class Host extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * Generate a unique URL slug for the host.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($name, $ignoreId, 'host');
    }

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from name if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Host $host): void {
            $source = filled($host->slug) ? $host->slug : ($host->name ?? '');
            if (filled($source)) {
                $host->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hosts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'avatar_url',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Mutator to ensure YouTube redirect URLs are unwrapped before saving.
     */
    public function setAvatarUrlAttribute(?string $value): void
    {
        $this->attributes['avatar_url'] = UrlSanitizer::unwrapYouTubeRedirect($value);
    }

    /**
     * Get the videos featured by this host.
     *
     * @return BelongsToMany<Video, $this>
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_hosts');
    }

    /**
     * Scope query to order Simon and Mark first, followed by everyone else alphabetically.
     * Includes the ordering expression in SELECT to ensure compatibility with PostgreSQL SELECT DISTINCT.
     *
     * @param  Builder<Host>  $query
     * @return Builder<Host>
     */
    public function scopeOrderFeaturedFirst(Builder $query): Builder
    {
        return $query
            ->selectRaw("hosts.*, (CASE WHEN slug = 'simon-anthony' OR LOWER(name) LIKE 'simon%' THEN 1 WHEN slug = 'mark-goodliffe' OR LOWER(name) LIKE 'mark%' THEN 2 ELSE 3 END) AS host_order")
            ->orderBy('host_order', 'asc')
            ->orderBy('hosts.name', 'asc');
    }
}
