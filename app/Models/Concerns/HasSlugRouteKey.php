<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Trait to bind models by slug in URL routes while gracefully supporting primary key fallbacks,
 * and provide centralized, collision-safe incremental slug generation.
 */
trait HasSlugRouteKey
{
    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value, allowing fallback to primary key when numeric.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        $field = $field ?? $this->getRouteKeyName();

        return $query->where(function (Builder $q) use ($field, $value): void {
            $q->where($field, $value);

            if (is_numeric($value)) {
                $q->orWhere($this->getQualifiedKeyName(), (int) $value);
            }
        });
    }

    /**
     * Generate a unique slug for this model, incrementing if a collision occurs.
     *
     * @param  string  $source  The raw title/name or pre-formed slug.
     * @param  int|string|null  $ignoreId  Model ID to ignore (for updates).
     * @param  string  $fallback  Fallback slug when source generates an empty slug.
     * @param  string  $separator  Word separator ('-' by default, '_' for snake_case).
     */
    public static function generateUniqueSlug(
        string $source,
        int|string|null $ignoreId = null,
        string $fallback = 'item',
        string $separator = '-'
    ): string {
        $base = Str::slug($source, $separator);
        if ($base === '') {
            $base = $fallback;
        }

        $keyName = (new static)->getKeyName();

        $exists = static::query()
            ->where('slug', $base)
            ->when($ignoreId !== null, fn ($query) => $query->where($keyName, '!=', $ignoreId))
            ->exists();

        if (! $exists) {
            return $base;
        }

        $escapedSeparator = preg_quote($separator, '/');
        if (preg_match('/^(.*)'.$escapedSeparator.'(\d+)$/', $base, $matches) && $matches[1] !== '') {
            $root = $matches[1];
            $counter = (int) $matches[2] + 1;
        } else {
            $root = $base;
            $counter = 2;
        }

        do {
            $candidate = "{$root}{$separator}{$counter}";
            $counter++;
        } while (
            static::query()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($query) => $query->where($keyName, '!=', $ignoreId))
                ->exists()
        );

        return $candidate;
    }
}
