<?php

namespace App\Models;

use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SetterLink model representing external profile, support, or portfolio links for a setter.
 *
 * Links can point to Patreon, personal websites, Logic Masters Germany,
 * SudokuPad, social media, or custom platforms.
 */
class SetterLink extends Model
{
    use HasFactory;
    use HasUserTracking;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'setter_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'setter_id',
        'link_type',
        'url',
        'title',
        'order_column',
        'created_by_id',
        'updated_by_id',
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
                'patreon' => 'Patreon',
                'personal_website' => 'Personal Website',
                'logic_masters_germany' => 'Logic Masters Germany',
                'sudokupad' => 'SudokuPad',
                'twitter' => 'Twitter / X',
                'youtube' => 'YouTube',
                'discord' => 'Discord',
                'twitch' => 'Twitch',
                'bluesky' => 'Bluesky',
                'other' => 'Other',
            ];
        }

        return $types;
    }

    /**
     * Get the setter that owns this link.
     *
     * @return BelongsTo<Setter, $this>
     */
    public function setter(): BelongsTo
    {
        return $this->belongsTo(Setter::class);
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
            ?? ucwords(str_replace(['_', '-'], ' ', (string) $this->link_type));
    }
}
