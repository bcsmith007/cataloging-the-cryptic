<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Puzzle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Puzzle>
 */
class PuzzleFactory extends Factory
{
    protected $model = Puzzle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'access_level' => 'public',
            'rules_raw' => fake()->paragraphs(2, true),
            'difficulty_score' => fake()->randomFloat(1, 1, 5),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Puzzle $puzzle): void {
            if ($puzzle->links()->doesntExist()) {
                $puzzle->links()->create([
                    'link_type' => 'sudokupad',
                    'url' => 'https://sudokupad.app/'.Str::random(8),
                    'order_column' => 1,
                ]);
            }
        });
    }

    /**
     * Set a specific SudokuPad URL for the puzzle.
     */
    public function withSudokupad(string $url): static
    {
        return $this->afterCreating(function (Puzzle $puzzle) use ($url): void {
            $puzzle->links()->where('link_type', 'sudokupad')->delete();
            $puzzle->links()->create([
                'link_type' => 'sudokupad',
                'url' => $url,
                'order_column' => 1,
            ]);
        });
    }

    /**
     * Attach a custom link to the puzzle.
     */
    public function withLink(string $type, string $url, ?string $title = null): static
    {
        return $this->afterCreating(function (Puzzle $puzzle) use ($type, $url, $title): void {
            $puzzle->links()->create([
                'link_type' => $type,
                'url' => $url,
                'title' => $title,
                'order_column' => $puzzle->links()->count() + 1,
            ]);
        });
    }

    /**
     * Indicate that the puzzle is patron-exclusive.
     */
    public function exclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => 'exclusive',
        ]);
    }

    /**
     * Indicate that the puzzle is publicly available.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => 'public',
        ]);
    }
}
