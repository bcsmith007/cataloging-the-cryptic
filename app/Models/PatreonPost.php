<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PatreonPost model representing a publication on Patreon.
 *
 * A Patreon post can contain one or more videos (public or exclusive),
 * which in turn contain puzzles and timestamps.
 */
class PatreonPost extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patreon_posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'patreon_url',
        'published_at',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from title if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (PatreonPost $post): void {
            $source = filled($post->slug) ? $post->slug : ($post->title ?? '');
            if (filled($source)) {
                $post->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * Get all videos attached to this Patreon post.
     *
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'patreon_post_id')->orderBy('published_at', 'asc');
    }

    /**
     * Get only publicly accessible videos attached to this post.
     *
     * @return HasMany<Video, $this>
     */
    public function publicVideos(): HasMany
    {
        return $this->hasMany(Video::class, 'patreon_post_id')
            ->where('access_level', 'public')
            ->orderBy('published_at', 'asc');
    }

    /**
     * Get only patron-exclusive videos attached to this post.
     *
     * @return HasMany<Video, $this>
     */
    public function exclusiveVideos(): HasMany
    {
        return $this->hasMany(Video::class, 'patreon_post_id')
            ->where('access_level', 'exclusive')
            ->orderBy('published_at', 'asc');
    }

    /**
     * Get a query builder for all puzzles solved across videos in this Patreon post.
     *
     * @return Builder<Puzzle>
     */
    public function puzzles(): Builder
    {
        return Puzzle::query()
            ->whereHas('videos', function ($q): void {
                $q->where('videos.patreon_post_id', $this->id);
            });
    }

    /**
     * Accessor for puzzles collection.
     *
     * @return Collection<int, Puzzle>
     */
    public function getPuzzlesAttribute(): Collection
    {
        return $this->puzzles()->get();
    }

    /**
     * Generate a unique URL slug for a Patreon post from its title.
     */
    public static function generateSlug(string $title, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($title, $ignoreId, 'patreon-post');
    }
}
