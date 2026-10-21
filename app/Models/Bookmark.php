<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bookmark model representing timestamps and annotations within videos.
 *
 * Can optionally be associated with a specific puzzle in the video.
 */
class Bookmark extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bookmarks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'video_id',
        'puzzle_id',
        'timestamp_seconds',
        'label',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp_seconds' => 'integer',
    ];

    /**
     * Get the video containing this bookmark.
     *
     * @return BelongsTo<Video, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the optional puzzle tagged to this bookmark.
     *
     * @return BelongsTo<Puzzle, $this>
     */
    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }

    /**
     * Format raw seconds into a timecode string omitting zero units (e.g. HH when < 60m, MM when < 60s).
     *
     * @param  int|null  $seconds  Total duration or timestamp in seconds.
     * @return string Formatted timecode string.
     */
    public static function formatSecondsToTimecode(?int $seconds): string
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
     * Parse a timecode string (HH:MM:SS, MM:SS, or seconds) into total seconds.
     *
     * @param  string|int|null  $value  Input timecode or raw seconds.
     * @return int Total seconds.
     */
    public static function parseTimecodeToSeconds(string|int|null $value): int
    {
        if (blank($value)) {
            return 0;
        }

        $value = trim((string) $value);

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $parts = explode(':', $value);
        $parts = array_map(fn (string $part): int => (int) trim($part), $parts);

        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return 0;
    }

    /**
     * Accessor for formatted timestamp ([HH:][MM:]SS).
     *
     * @return Attribute<string, never>
     */
    protected function formattedTimestamp(): Attribute
    {
        return Attribute::make(
            get: fn (): string => static::formatSecondsToTimecode($this->timestamp_seconds)
        );
    }
}
