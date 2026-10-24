<?php

namespace Database\Factories;

use App\Models\CtcApp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CtcApp>
 */
class CtcAppFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CtcApp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Sudoku';

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'developer' => 'Studio Goya / Cracking The Cryptic',
            'thumbnail_url' => 'https://via.placeholder.com/300x300.png?text='.urlencode($name),
            'apple_store_url' => 'https://apps.apple.com/app/'.Str::slug($name).'/id'.fake()->numerify('15#######'),
            'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.'.Str::studly($name),
            'steam_url' => 'https://store.steampowered.com/app/'.fake()->numerify('1######').'/'.Str::studly($name).'/',
            'web_url' => null,
            'badge' => fake()->randomElement(['Featured', 'Popular', null]),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
