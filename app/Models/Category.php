<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category model representing the primary classification of a puzzle.
 *
 * Examples include Variant Sudoku, Classic Sudoku, Crossword, and GAS.
 */
class Category extends Model
{
    use HasFactory;
    use HasSlugRouteKey;

    /**
     * Generate a unique URL slug for the category.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($name, $ignoreId, 'category');
    }

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from name if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            $source = filled($category->slug) ? $category->slug : ($category->name ?? '');
            if (filled($source)) {
                $category->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the puzzles assigned to this category.
     *
     * @return HasMany<Puzzle, $this>
     */
    public function puzzles(): HasMany
    {
        return $this->hasMany(Puzzle::class);
    }
}
