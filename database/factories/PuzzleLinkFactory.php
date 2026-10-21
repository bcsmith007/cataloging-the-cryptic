<?php

namespace Database\Factories;

use App\Models\Puzzle;
use App\Models\PuzzleLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PuzzleLink>
 */
class PuzzleLinkFactory extends Factory
{
    protected $model = PuzzleLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'puzzle_id' => Puzzle::factory(),
            'link_type' => 'sudokupad',
            'url' => 'https://sudokupad.app/'.Str::random(8),
            'title' => null,
            'order_column' => 1,
        ];
    }

    /**
     * Indicate that the link is for Logic Masters Germany.
     */
    public function logicMastersGermany(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => 'logic_masters_germany',
            'url' => 'https://logic-masters.de/Raetselportal/Raetsel/zeigen.php?id='.fake()->numberBetween(1000, 99999),
        ]);
    }

    /**
     * Indicate that the link is for Penpa.
     */
    public function penpa(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => 'penpa',
            'url' => 'https://penpa-edit.appspot.com/'.Str::random(8),
        ]);
    }

    /**
     * Indicate that the link is for Times Puzzles.
     */
    public function timesPuzzles(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => 'times_puzzles',
            'url' => 'https://www.thetimes.co.uk/puzzles/'.Str::random(8),
        ]);
    }
}
