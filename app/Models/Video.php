<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use App\Services\UrlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Video model representing YouTube uploads or Patreon exclusives.
 *
 * Videos are the primary organizational unit of Cataloging the Cryptic.
 */
class Video extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * SQL expression resolving the effective publication timestamp of a video.
     *
     * For Patron Exclusive videos attached to a Patreon post, the publication date
     * of the Patreon post takes precedence over the video's individual upload timestamp.
     */
    public const EFFECTIVE_PUBLISHED_AT_SQL = "COALESCE((SELECT patreon_posts.published_at FROM patreon_posts WHERE patreon_posts.id = videos.patreon_post_id AND videos.access_level = 'exclusive'), videos.published_at)";

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from title if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Video $video): void {
            $source = filled($video->slug) ? $video->slug : ($video->title ?? '');
            if (filled($source)) {
                $video->slug = static::generateSlug($source);
            }
        });

        static::saving(function (Video $video): void {
            if (filled($video->patreon_post_id) && blank($video->patreon_post_url)) {
                $post = $video->relationLoaded('patreonPost')
                    ? $video->patreonPost
                    : PatreonPost::find($video->patreon_post_id);

                if ($post?->patreon_url) {
                    $video->patreon_post_url = $post->patreon_url;
                }
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patreon_post_id',
        'youtube_video_id',
        'video_type',
        'access_level',
        'patreon_post_url',
        'title',
        'slug',
        'published_at',
        'duration_seconds',
        'view_count',
        'stats_synced_at',
        'youtube_thumbnail_url',
        'intro_music',
        'outro_music',
        'is_finalized',
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
        'stats_synced_at' => 'datetime',
        'duration_seconds' => 'integer',
        'view_count' => 'integer',
        'is_finalized' => 'boolean',
    ];

    /**
     * Get the Patreon post this video is attached to, if any.
     *
     * @return BelongsTo<PatreonPost, $this>
     */
    public function patreonPost(): BelongsTo
    {
        return $this->belongsTo(PatreonPost::class, 'patreon_post_id');
    }

    /**
     * Get the hosts featuring in this video.
     *
     * @return BelongsToMany<Host, $this>
     */
    public function hosts(): BelongsToMany
    {
        return $this->belongsToMany(Host::class, 'video_hosts');
    }

    /**
     * Get the puzzles solved in this video.
     *
     * @return BelongsToMany<Puzzle, $this>
     */
    public function puzzles(): BelongsToMany
    {
        return $this->belongsToMany(Puzzle::class, 'video_puzzles')
            ->using(VideoPuzzle::class)
            ->withPivot(['id', 'order_in_video', 'start_seconds', 'solve_duration_seconds'])
            ->withTimestamps()
            ->orderByPivot('order_in_video', 'asc');
    }

    /**
     * Get the intermediate pivot records for contained puzzles.
     *
     * @return HasMany<VideoPuzzle, $this>
     */
    public function videoPuzzles(): HasMany
    {
        return $this->hasMany(VideoPuzzle::class)
            ->orderBy('order_in_video', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Get bookmarks and timestamps for this video, ordered chronologically.
     *
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class)->orderBy('timestamp_seconds', 'asc');
    }

    /**
     * Determine whether the video is publicly accessible.
     */
    public function isPublic(): bool
    {
        return ($this->access_level ?? 'public') === 'public';
    }

    /**
     * Determine whether the video is patron-exclusive.
     */
    public function isExclusive(): bool
    {
        return $this->access_level === 'exclusive';
    }

    /**
     * Determine whether the video is associated with a Patreon post.
     */
    public function hasPatreonPost(): bool
    {
        return ! is_null($this->patreon_post_id);
    }

    /**
     * Determine whether the video is a Patreon-related video (exclusive or attached to a post).
     */
    public function isPatreon(): bool
    {
        return $this->isExclusive() || $this->hasPatreonPost() || $this->video_type === 'patreon';
    }

    /**
     * Determine whether the video is standard YouTube.
     */
    public function isYouTube(): bool
    {
        return $this->video_type === 'youtube';
    }

    /**
     * Determine whether the video is a YouTube Short.
     */
    public function isYouTubeShort(): bool
    {
        return $this->video_type === 'youtube_short';
    }

    /**
     * Determine whether the video is a Live Stream.
     */
    public function isLiveStream(): bool
    {
        return $this->video_type === 'live_stream';
    }

    /**
     * Get the watch URL (YouTube video/short/live URL or Patreon post URL).
     */
    public function getWatchUrl(): ?string
    {
        if ($this->isExclusive()) {
            $postUrl = $this->relationLoaded('patreonPost') ? $this->patreonPost?->patreon_url : null;

            return $postUrl ?? $this->patreon_post_url;
        }

        if ($this->youtube_video_id) {
            if ($this->isYouTubeShort()) {
                return 'https://www.youtube.com/shorts/'.$this->youtube_video_id;
            }

            if ($this->isLiveStream()) {
                return 'https://www.youtube.com/live/'.$this->youtube_video_id;
            }

            return 'https://www.youtube.com/watch?v='.$this->youtube_video_id;
        }

        $postUrl = $this->relationLoaded('patreonPost') ? $this->patreonPost?->patreon_url : null;

        return $postUrl ?? $this->patreon_post_url;
    }

    /**
     * Format total seconds into a timecode string omitting zero units (e.g. HH when < 60m, MM when < 60s).
     *
     * @param  int|string|null  $seconds  Total duration or timestamp in seconds.
     * @return string Formatted timecode string.
     */
    public static function formatSecondsToTimecode(int|string|null $seconds): string
    {
        $seconds = max(0, (int) $seconds);
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs = (int) ($seconds % 60);

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $secs);
        }

        return (string) $secs;
    }

    /**
     * Format total seconds into a timecode string, omitting units that do not exist.
     *
     * @param  int|string|null  $seconds  Total duration or timestamp in seconds.
     * @return string Formatted timecode string.
     */
    public static function formatSecondsToHms(int|string|null $seconds): string
    {
        return static::formatSecondsToTimecode($seconds);
    }

    /**
     * Accessor for formatted duration, omitting non-existent units (e.g. HH when < 60m, MM when < 60s).
     *
     * @return Attribute<string, never>
     */
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: fn (): string => static::formatSecondsToTimecode($this->duration_seconds)
        );
    }

    /**
     * Convert a YouTube thumbnail URL (JPEG/PNG) to YouTube's CDN WebP equivalent.
     *
     * Example:
     * https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg
     * becomes:
     * https://i.ytimg.com/vi_webp/dQw4w9WgXcQ/hqdefault.webp
     *
     * Non-YouTube URLs or already-WebP URLs are returned unchanged.
     */
    public static function convertToWebpThumbnailUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Match YouTube thumbnail URLs across i.ytimg.com, img.youtube.com, etc.
        // Matches pattern: {protocol}//{host}/vi/{videoId}/{quality}.{ext}{query}
        if (preg_match('/^((?:https?:)?\/\/[^\/]+)\/vi\/([^\/]+)\/([^\/?#]+?)(?:\.(?:jpg|jpeg|png))?(\?[^#]*)?$/i', $url, $matches)) {
            $host = $matches[1];
            $videoId = $matches[2];
            $quality = $matches[3];
            $queryString = $matches[4] ?? '';

            return "{$host}/vi_webp/{$videoId}/{$quality}.webp{$queryString}";
        }

        return $url;
    }

    /**
     * Get the thumbnail URL for the video (stored thumbnail converted to WebP or fallback to YouTube hqdefault.webp).
     */
    public function getThumbnailUrl(): ?string
    {
        if (! empty($this->youtube_thumbnail_url)) {
            return static::convertToWebpThumbnailUrl($this->youtube_thumbnail_url);
        }

        if ($this->youtube_video_id) {
            return "https://i.ytimg.com/vi_webp/{$this->youtube_video_id}/hqdefault.webp";
        }

        return null;
    }

    /**
     * Interact with the video's stored thumbnail URL attribute.
     *
     * Automatically converts any YouTube thumbnail URLs to WebP format.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function youtubeThumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => static::convertToWebpThumbnailUrl($value),
            set: fn (?string $value): ?string => static::convertToWebpThumbnailUrl($value),
        );
    }

    /**
     * Interact with the direct Patreon post URL attribute.
     *
     * Automatically unwraps YouTube redirect URLs.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function patreonPostUrl(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => UrlSanitizer::unwrapYouTubeRedirect($value),
        );
    }

    /**
     * Accessor for thumbnail URL.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getThumbnailUrl()
        );
    }

    /**
     * Generate a unique URL slug for a video from its title.
     */
    public static function generateSlug(string $title, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($title, $ignoreId, 'video');
    }

    /**
     * Scope a query to only include finalized videos.
     */
    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('is_finalized', true);
    }

    /**
     * Scope a query to only include unfinalized videos.
     */
    public function scopeUnfinalized(Builder $query): Builder
    {
        return $query->where('is_finalized', false);
    }

    /**
     * Check if the video is marked as finalized.
     */
    public function isFinalized(): bool
    {
        return (bool) $this->is_finalized;
    }

    /**
     * Resolve the effective publication timestamp used for display and sorting.
     *
     * When the video is Patron Exclusive and attached to a Patreon post, returns
     * the Patreon post's published_at timestamp; otherwise returns the video's published_at.
     */
    public function getEffectivePublishedAt(): ?Carbon
    {
        if ($this->isExclusive() && $this->patreon_post_id !== null) {
            $post = $this->relationLoaded('patreonPost')
                ? $this->patreonPost
                : $this->patreonPost()->first();

            if ($post?->published_at !== null) {
                return $post->published_at;
            }
        }

        return $this->published_at;
    }

    /**
     * Accessor for the effective publication timestamp (`$video->effective_published_at`).
     *
     * @return Attribute<Carbon|null, never>
     */
    protected function effectivePublishedAt(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Carbon => $this->getEffectivePublishedAt()
        );
    }

    /**
     * Scope a query to order videos by their effective publication date.
     */
    public function scopeOrderByEffectivePublishedAt(Builder $query, string $direction = 'desc'): Builder
    {
        $dir = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        return $query->orderByRaw(self::EFFECTIVE_PUBLISHED_AT_SQL.' '.$dir);
    }

    /**
     * Get the chronologically previous video in the catalog (published immediately before).
     */
    public function previousVideo(): ?self
    {
        $effectiveDate = $this->effective_published_at;
        if (! $effectiveDate) {
            return null;
        }

        $dateValue = $this->fromDateTime($effectiveDate);

        return static::query()
            ->with('patreonPost')
            ->where('videos.id', '!=', $this->id)
            ->where(function (Builder $query) use ($dateValue): void {
                $query->whereRaw(self::EFFECTIVE_PUBLISHED_AT_SQL.' < ?', [$dateValue])
                    ->orWhere(function (Builder $q) use ($dateValue): void {
                        $q->whereRaw(self::EFFECTIVE_PUBLISHED_AT_SQL.' = ?', [$dateValue])
                            ->where('videos.id', '<', $this->id);
                    });
            })
            ->orderByEffectivePublishedAt('desc')
            ->orderByDesc('videos.id')
            ->first();
    }

    /**
     * Get the chronologically next video in the catalog (published immediately after).
     */
    public function nextVideo(): ?self
    {
        $effectiveDate = $this->effective_published_at;
        if (! $effectiveDate) {
            return null;
        }

        $dateValue = $this->fromDateTime($effectiveDate);

        return static::query()
            ->with('patreonPost')
            ->where('videos.id', '!=', $this->id)
            ->where(function (Builder $query) use ($dateValue): void {
                $query->whereRaw(self::EFFECTIVE_PUBLISHED_AT_SQL.' > ?', [$dateValue])
                    ->orWhere(function (Builder $q) use ($dateValue): void {
                        $q->whereRaw(self::EFFECTIVE_PUBLISHED_AT_SQL.' = ?', [$dateValue])
                            ->where('videos.id', '>', $this->id);
                    });
            })
            ->orderByEffectivePublishedAt('asc')
            ->orderBy('videos.id', 'asc')
            ->first();
    }
}
