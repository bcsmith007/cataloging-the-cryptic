<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Constraint model representing puzzle rules or variants.
 *
 * Examples include Thermo, Arrow, Killer, Fog of War, and German Whispers.
 */
class Constraint extends Model
{
    use HasFactory;
    use HasSlugRouteKey;

    /**
     * Generate a unique URL slug for the constraint variant.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($name, $ignoreId, 'constraint');
    }

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from name if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Constraint $constraint): void {
            $source = filled($constraint->slug) ? $constraint->slug : ($constraint->name ?? '');
            if (filled($source)) {
                $constraint->slug = static::generateSlug($source);
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'constraints';

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
     * Get the puzzles that utilize this constraint variant.
     *
     * @return BelongsToMany<Puzzle, $this>
     */
    public function puzzles(): BelongsToMany
    {
        return $this->belongsToMany(Puzzle::class, 'puzzle_constraints');
    }
}
