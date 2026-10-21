<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Model representing an execution log of the YouTube synchronization command.
 *
 * @property int $id
 * @property string $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property int $new_videos_count
 * @property int $updated_views_count
 * @property int $total_views_added
 * @property int $total_processed
 * @property string|null $message
 * @property array<string, mixed>|null $details
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class YouTubeSyncLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'new_videos_count',
        'updated_views_count',
        'total_views_added',
        'total_processed',
        'message',
        'details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'new_videos_count' => 'integer',
        'updated_views_count' => 'integer',
        'total_views_added' => 'integer',
        'total_processed' => 'integer',
        'details' => 'array',
    ];

    /**
     * Get the cumulative view delta across all updated videos.
     * Falls back to summing view deltas from details JSON if column is 0 or null.
     *
     * @param  mixed  $value
     */
    public function getTotalViewsAddedAttribute($value): int
    {
        if ($value !== null && (int) $value !== 0) {
            return (int) $value;
        }

        if (empty($this->details) || ! is_array($this->details)) {
            return (int) ($value ?? 0);
        }

        $sum = 0;
        foreach ($this->details as $item) {
            if (($item['type'] ?? '') === 'view_update') {
                $sum += (int) ($item['diff'] ?? 0);
            }
        }

        return $sum;
    }

    /**
     * Format cumulative views added with a positive/negative sign (e.g., "+6,241 Views").
     */
    public function getFormattedViewsAddedAttribute(): string
    {
        $views = $this->total_views_added;

        if ($views > 0) {
            return '+'.number_format($views).' Views';
        }

        if ($views < 0) {
            return number_format($views).' Views';
        }

        return '0 Views';
    }

    /**
     * Prune older log records, keeping only the most recent $keep entries.
     *
     * @return int Number of deleted records.
     */
    public static function pruneOldLogs(int $keep = 100): int
    {
        $cutoffId = static::orderByDesc('id')
            ->skip($keep)
            ->value('id');

        if ($cutoffId) {
            return static::where('id', '<=', $cutoffId)->delete();
        }

        return 0;
    }
}
