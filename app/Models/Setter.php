<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Setter model representing puzzle creators and authors.
 *
 * Puzzles can have multiple setters to accommodate collaborative creations.
 */
class Setter extends Model
{
    use HasFactory;
    use HasSlugRouteKey;

    /**
     * Generate a unique URL slug for the setter.
     */
    public static function generateSlug(string $name, int|string|null $ignoreId = null): string
    {
        return static::generateUniqueSlug($name, $ignoreId, 'setter');
    }

    /**
     * The "booted" method of the model.
     *
     * Ensures slug is automatically generated from name if not provided,
     * and synchronizes patreon_url when links change.
     */
    protected static function booted(): void
    {
        static::creating(function (Setter $setter): void {
            $source = filled($setter->slug) ? $setter->slug : ($setter->name ?? '');
            if (filled($source)) {
                $setter->slug = static::generateSlug($source);
            }
        });

        static::saved(function (Setter $setter): void {
            if ($setter->relationLoaded('links')) {
                $patreonLink = $setter->links->firstWhere('link_type', 'patreon');
                $newPatreonUrl = $patreonLink?->url;
                if ($newPatreonUrl !== $setter->getRawOriginal('patreon_url')) {
                    $setter->updateQuietly(['patreon_url' => $newPatreonUrl]);
                }
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'setters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'bio',
        'patreon_url',
    ];

    /**
     * Backward-compatible accessor for patreon_url.
     */
    public function getPatreonUrlAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return $value;
        }

        if ($this->relationLoaded('links')) {
            return $this->links->firstWhere('link_type', 'patreon')?->url;
        }

        return $this->links()->where('link_type', 'patreon')->value('url');
    }

    /**
     * Get all external links associated with this setter.
     *
     * @return HasMany<SetterLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(SetterLink::class)->orderBy('order_column');
    }

    /**
     * Get the puzzles authored or co-authored by this setter.
     *
     * @return BelongsToMany<Puzzle, $this>
     */
    public function puzzles(): BelongsToMany
    {
        return $this->belongsToMany(Puzzle::class, 'puzzle_setters');
    }
}
