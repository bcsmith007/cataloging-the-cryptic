<?php

namespace Database\Factories;

use App\Models\PatreonPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatreonPost>
 */
class PatreonPostFactory extends Factory
{
    protected $model = PatreonPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'patreon_url' => 'https://www.patreon.com/posts/'.fake()->unique()->randomNumber(8, true),
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
