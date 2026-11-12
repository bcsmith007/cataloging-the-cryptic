<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\HasUserTracking;
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
    use HasUserTracking;

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
        'created_by_id',
        'updated_by_id',
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

    /**
     * Split the constraint's markdown description into standard and extended sections.
     *
     * @return array{standard: string, extended: ?string}
     */
    public function splitDescriptionSections(): array
    {
        $description = trim((string) ($this->description ?? ''));
        if ($description === '') {
            return [
                'standard' => '',
                'extended' => null,
            ];
        }

        $pattern = '/^(?:#{1,6}\s*|\*\*\s*)(?:Extended\s+Description|Advanced\s+Mechanics)\b[^\r\n]*$/im';

        if (preg_match($pattern, $description, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $matches[0][1];
            $standard = trim(substr($description, 0, $offset));
            $extended = trim(substr($description, $offset));

            return [
                'standard' => $standard,
                'extended' => $extended !== '' ? $extended : null,
            ];
        }

        return [
            'standard' => $description,
            'extended' => null,
        ];
    }
}
