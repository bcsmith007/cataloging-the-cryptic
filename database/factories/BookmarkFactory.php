<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Puzzle;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'puzzle_id' => null,
            'timestamp_seconds' => fake()->numberBetween(0, 3600),
            'label' => fake()->words(3, true),
        ];
    }

    /**
     * Associate bookmark with a puzzle.
     */
    public function forPuzzle(Puzzle $puzzle): static
    {
        return $this->state(fn () => [
            'puzzle_id' => $puzzle->id,
        ]);
    }
}
