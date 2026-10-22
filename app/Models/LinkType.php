<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LinkType model representing a category/destination platform for puzzle solve links.
 *
 * Examples include SudokuPad, Logic Masters Germany, Penpa, Times Puzzles, etc.
 */
class LinkType extends Model
{
    use HasFactory;
    use HasSlugRouteKey;
    use HasUserTracking;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'link_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from name if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (LinkType $linkType): void {
            $source = filled($linkType->slug) ? $linkType->slug : ($linkType->name ?? '');
            if (filled($source)) {
                $linkType->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * Generate a unique URL slug for the link type.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($name, $ignoreId, 'link_type', '_');
    }

    /**
     * Get all puzzle links that belong to this link type.
     *
     * @return HasMany<PuzzleLink, $this>
     */
    public function puzzleLinks(): HasMany
    {
        return $this->hasMany(PuzzleLink::class, 'link_type', 'slug');
    }
}
