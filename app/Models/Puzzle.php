<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use App\Services\UrlSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Puzzle model representing cryptic puzzles, variant sudokus, and crosswords.
 *
 * Puzzles are linked to categories, setters, variants/constraints, and videos.
 */
class Puzzle extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * The "booted" method of the model.
     *
     * Ensures critical non-null attributes have valid fallbacks when creating records.
     */
    protected static function booted(): void
    {
        static::creating(function (Puzzle $puzzle): void {
            $source = filled($puzzle->slug) ? $puzzle->slug : ($puzzle->title ?? '');
            if (filled($source)) {
                $puzzle->slug = static::generateSlug($source);
            }
            if (empty($puzzle->category_id)) {
                $category = Category::where('slug', 'variant-sudoku')->first()
                    ?? Category::query()->first()
                    ?? Category::create([
                        'name' => 'Variant Sudoku',
                        'slug' => 'variant-sudoku',
                    ]);

                $puzzle->category_id = $category->id;
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'puzzles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'access_level',
        'rules_raw',
        'difficulty_score',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Mutator to unwrap any embedded YouTube redirect links in raw rules.
     */
    public function setRulesRawAttribute(?string $value): void
    {
        $this->attributes['rules_raw'] = UrlSanitizer::unwrapAllInText($value);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'difficulty_score' => 'decimal:1',
    ];

    /**
     * Get the category of this puzzle.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the setters who authored this puzzle.
     *
     * @return BelongsToMany<Setter, $this>
     */
    public function setters(): BelongsToMany
    {
        return $this->belongsToMany(Setter::class, 'puzzle_setters');
    }

    /**
     * Get the constraint variants applied to this puzzle.
     *
     * @return BelongsToMany<Constraint, $this>
     */
    public function constraints(): BelongsToMany
    {
        return $this->belongsToMany(Constraint::class, 'puzzle_constraints')
            ->orderBy('name', 'asc');
    }

    /**
     * Get the videos featuring this puzzle.
     *
     * @return BelongsToMany<Video, $this>
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_puzzles')
            ->using(VideoPuzzle::class)
            ->withPivot(['id', 'order_in_video', 'start_seconds', 'solve_duration_seconds'])
            ->withTimestamps();
    }

    /**
     * Get bookmarks linked to this puzzle.
     *
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Get external solve and platform links for this puzzle.
     *
     * @return HasMany<PuzzleLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(PuzzleLink::class)->orderBy('order_column');
    }

    /**
     * Get the primary SudokuPad URL if available (backward compatibility).
     */
    public function getPlayUrlSudokupadAttribute(): ?string
    {
        return $this->links->firstWhere('link_type', 'sudokupad')?->url;
    }

    /**
     * Get the primary Penpa URL if available (backward compatibility).
     */
    public function getPlayUrlPenpaAttribute(): ?string
    {
        return $this->links->firstWhere('link_type', 'penpa')?->url;
    }

    /**
     * Generate the standardized slug for a puzzle: {puzzle_title}-{primary_setter_name}.
     *
     * @param  string  $title  Puzzle title.
     * @param  string|null  $setterName  Primary setter name.
     */
    public static function generateDefaultSlug(string $title, ?string $setterName = null): string
    {
        $base = $setterName ? "{$title} {$setterName}" : $title;

        return Str::slug($base);
    }

    /**
     * Generate a unique slug for a puzzle.
     */
    public static function generateSlug(string $title, ?string $setterName = null, int|string|null $ignoreId = null): string
    {
        $base = static::generateDefaultSlug($title, $setterName);

        return static::generateUniqueSlug($base, $ignoreId, 'puzzle');
    }

    /**
     * Determine whether the puzzle is publicly accessible.
     */
    public function isPublic(): bool
    {
        return ($this->access_level ?? 'public') === 'public';
    }

    /**
     * Determine whether the puzzle is patron-exclusive.
     */
    public function isExclusive(): bool
    {
        return $this->access_level === 'exclusive';
    }
}
