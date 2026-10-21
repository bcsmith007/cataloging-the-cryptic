<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PuzzleLink model representing external solve or reference links for a puzzle.
 *
 * Links can point to SudokuPad, Logic Masters Germany, Penpa, Times Puzzles,
 * or other community platforms.
 */
class PuzzleLink extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'puzzle_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'puzzle_id',
        'link_type',
        'url',
        'title',
        'order_column',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_column' => 'integer',
    ];

    /**
     * Available standard and custom link types and their user-friendly display labels.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        $types = LinkType::query()->orderBy('name')->pluck('name', 'slug')->toArray();

        if (empty($types)) {
            return [
                'sudokupad' => 'SudokuPad',
                'logic_masters_germany' => 'Logic Masters Germany',
                'penpa' => 'Penpa',
                'times_puzzles' => 'Times Puzzles',
                'ctc_app' => 'Cracking the Cryptic App',
                'other' => 'Other',
            ];
        }

        return $types;
    }

    /**
     * Get the puzzle that owns this link.
     *
     * @return BelongsTo<Puzzle, $this>
     */
    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }

    /**
     * Get the link type record if available.
     *
     * @return BelongsTo<LinkType, $this>
     */
    public function linkType(): BelongsTo
    {
        return $this->belongsTo(LinkType::class, 'link_type', 'slug');
    }

    /**
     * Get the user-facing display label for this link.
     *
     * Returns custom title if provided, otherwise the standard formatted type label.
     */
    public function getDisplayLabelAttribute(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        if ($this->relationLoaded('linkType') && $this->linkType?->name) {
            return $this->linkType->name;
        }

        return static::typeOptions()[$this->link_type]
            ?? ucwords(str_replace(['_', '-'], ' ', $this->link_type));
    }
}
