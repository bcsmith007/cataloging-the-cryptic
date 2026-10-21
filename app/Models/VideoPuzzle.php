<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * VideoPuzzle pivot model connecting videos to their contained puzzles.
 *
 * Stores order in video, start timestamps, and solving duration.
 */
class VideoPuzzle extends Pivot
{
    /**
     * The table associated with the pivot model.
     *
     * @var string
     */
    protected $table = 'video_puzzles';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'video_id',
        'puzzle_id',
        'order_in_video',
        'start_seconds',
        'solve_duration_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_in_video' => 'integer',
        'start_seconds' => 'integer',
        'solve_duration_seconds' => 'integer',
    ];

    /**
     * Get the associated video.
     *
     * @return BelongsTo<Video, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the associated puzzle.
     *
     * @return BelongsTo<Puzzle, $this>
     */
    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }
}
